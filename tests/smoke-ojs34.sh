#!/usr/bin/env bash
set -euo pipefail

base_url="${OJS34_BASE_URL:-http://127.0.0.1:8034/index.php}"
context_path="${OJS34_CONTEXT_PATH:-publicknowledge}"
test_user="${OJS_TEST_USER:-admin}"
test_password="${OJS_TEST_PASSWORD:-admin}"
test_archive_code="${OJS_REPEC_TEST_ARCHIVE_CODE:-tst}"
test_series_code="${OJS_REPEC_TEST_SERIES_CODE:-public}"
test_issue_file="${OJS_REPEC_TEST_ISSUE_FILE:-v1i2y2014.redif}"
test_maintainer_email="${OJS_REPEC_TEST_MAINTAINER_EMAIL:-repec-test@example.org}"
tmp_dir="${TMPDIR:-/tmp}"
headers_file="$(mktemp "${tmp_dir}/repec-smoke-headers.XXXXXX")"
body_file="$(mktemp "${tmp_dir}/repec-smoke-body.XXXXXX")"
cookie_jar="$(mktemp "${tmp_dir}/repec-smoke-cookies.XXXXXX")"

cleanup() {
	rm -f "$headers_file" "$body_file" "$cookie_jar"
}
trap cleanup EXIT

print_recent_log() {
	if [ "${OJS_SMOKE_READ_LOG:-0}" != "1" ]; then
		return
	fi

	echo "Recent PHP error log:" >&2
	docker exec "${OJS34_DOCKER_CONTAINER:-frankenphp-ojs34}" \
		sh -lc "tail -120 ${OJS34_PHP_ERROR_LOG:-/app/php-error.log}" >&2 || true
}

fail_with_response() {
	echo "$1" >&2
	echo "Response headers:" >&2
	cat "$headers_file" >&2
	echo "Response body first 4000 bytes:" >&2
	head -c 4000 "$body_file" >&2
	echo >&2
	print_recent_log
	exit 1
}

assert_no_php_errors() {
	if grep -Eiq 'Fatal error|Parse error|Exception|Stack trace|Warning:|Call to undefined|Class .* not found|Failed Ajax request' "$body_file"; then
		fail_with_response "Unexpected PHP/Ajax error output in $1"
	fi
}

extract_settings_form_value() {
	php -r '
		$json = json_decode(file_get_contents($argv[1]), true);
		$content = is_array($json) && isset($json["content"]) ? $json["content"] : "";
		$content = html_entity_decode($content, ENT_QUOTES, "UTF-8");
		$patterns = [
			"action" => "/<form[^>]*id=\"repecSettingsForm\"[^>]*action=\"([^\"]*)\"/i",
			"csrf" => "/name=\"csrfToken\" value=\"([^\"]*)\"/i",
		];
		if (!isset($patterns[$argv[2]]) || !preg_match($patterns[$argv[2]], $content, $matches)) {
			exit(1);
		}
		echo html_entity_decode($matches[1], ENT_QUOTES, "UTF-8");
	' "$body_file" "$1"
}

curl -sS -L -D "$headers_file" -o "$body_file" "${base_url%/}/index/repec/"

if ! grep -q ' 200 ' "$headers_file"; then
	fail_with_response "Expected /index/repec/ to return HTTP 200"
fi

if ! grep -q '<title>RePEc archives</title>' "$body_file"; then
	fail_with_response "Expected /index/repec/ to return the RePEc archive index HTML"
fi

assert_no_php_errors "/index/repec/"

echo "OJS 3.4 RePEc smoke passed: ${base_url%/}/index/repec/"

login_page="${base_url%/}/${context_path}/login"

curl -sS -L -c "$cookie_jar" -D "$headers_file" -o "$body_file" "$login_page"

if ! grep -q ' 200 ' "$headers_file"; then
	fail_with_response "Expected OJS login page to return HTTP 200"
fi

csrf_token="$(sed -n 's/.*name="csrfToken" value="\([^"]*\)".*/\1/p' "$body_file" | head -n 1)"
login_post="$(sed -n 's/.*<form[^>]*id="login"[^>]*action="\([^"]*\)".*/\1/p' "$body_file" | head -n 1)"
login_post="$(php -r 'echo html_entity_decode($argv[1], ENT_QUOTES, "UTF-8");' "$login_post")"

if [ -z "$csrf_token" ]; then
	fail_with_response "Could not find csrfToken in OJS login page"
fi

if [ -z "$login_post" ]; then
	fail_with_response "Could not find login form action in OJS login page"
fi

curl -sS -L -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" \
	-X POST "$login_post" \
	--data-urlencode "csrfToken=$csrf_token" \
	--data-urlencode "username=$test_user" \
	--data-urlencode "password=$test_password" \
	--data-urlencode "source=" \
	--data-urlencode "remember=1"

assert_no_php_errors "OJS login"

settings_url="${base_url%/}/${context_path}/\$\$\$call\$\$\$/grid/settings/plugins/settings-plugin-grid/manage?verb=settings&plugin=repecplugin&category=generic&_=0"

curl -sS -D "$headers_file" -o "$body_file" \
	-H 'X-Requested-With: XMLHttpRequest' \
	-H 'Accept: application/json, text/javascript, */*; q=0.01' \
	-b "$cookie_jar" \
	"$settings_url"

if ! head -n 1 "$headers_file" | grep -q ' 200 '; then
	fail_with_response "Expected RePEc settings modal endpoint to return HTTP 200"
fi

if ! grep -Eiq 'content-type: application/json|content-type: text/json' "$headers_file"; then
	fail_with_response "Expected RePEc settings modal endpoint to return JSON"
fi

assert_no_php_errors "RePEc settings modal endpoint"

if ! grep -q '"status":true' "$body_file"; then
	fail_with_response "Expected RePEc settings modal JSONMessage with status=true"
fi

echo "OJS 3.4 RePEc settings smoke passed: $settings_url"

settings_action="$(extract_settings_form_value action || true)"
settings_csrf_token="$(extract_settings_form_value csrf || true)"

if [ -z "$settings_action" ] || [ -z "$settings_csrf_token" ]; then
	fail_with_response "Could not extract RePEc settings form action and csrfToken"
fi

curl -sS -D "$headers_file" -o "$body_file" \
	-H 'X-Requested-With: XMLHttpRequest' \
	-H 'Accept: application/json, text/javascript, */*; q=0.01' \
	-b "$cookie_jar" \
	-X POST "$settings_action" \
	--data-urlencode "csrfToken=$settings_csrf_token" \
	--data-urlencode "archiveCode=$test_archive_code" \
	--data-urlencode "seriesCode=$test_series_code" \
	--data-urlencode "maintainerEmail=$test_maintainer_email" \
	--data-urlencode "legacyHandlesJson="

if ! head -n 1 "$headers_file" | grep -q ' 200 '; then
	fail_with_response "Expected RePEc settings save endpoint to return HTTP 200"
fi

if ! grep -Eiq 'content-type: application/json|content-type: text/json' "$headers_file"; then
	fail_with_response "Expected RePEc settings save endpoint to return JSON"
fi

assert_no_php_errors "RePEc settings save endpoint"

if ! grep -q '"status":true' "$body_file"; then
	fail_with_response "Expected RePEc settings save JSONMessage with status=true"
fi

echo "OJS 3.4 RePEc settings configured: archive=$test_archive_code series=$test_series_code"

journal_repec_url="${base_url%/}/${context_path}/repec"

curl -sS -L -D "$headers_file" -o "$body_file" "$journal_repec_url"

if ! head -n 1 "$headers_file" | grep -Eq ' 200 | 30[12378] '; then
	fail_with_response "Expected journal /repec endpoint to return HTTP 200 or redirect"
fi

if ! grep -q "<title>RePEc ${test_archive_code}</title>" "$body_file"; then
	fail_with_response "Expected journal /repec endpoint to publish the configured RePEc archive"
fi

if ! grep -q "${test_archive_code}arch.redif" "$body_file"; then
	fail_with_response "Expected journal /repec endpoint to link the archive template"
fi

if ! grep -q "${test_archive_code}seri.redif" "$body_file"; then
	fail_with_response "Expected journal /repec endpoint to link the series template"
fi

if ! grep -q "${test_series_code}/" "$body_file"; then
	fail_with_response "Expected journal /repec endpoint to link the configured series"
fi

assert_no_php_errors "journal /repec endpoint"

echo "OJS 3.4 RePEc configured archive smoke passed: $journal_repec_url"

series_repec_url="${base_url%/}/${context_path}/repec/${test_archive_code}/${test_series_code}"

curl -sS -L -D "$headers_file" -o "$body_file" "$series_repec_url"

if ! grep -q ' 200 ' "$headers_file"; then
	fail_with_response "Expected RePEc series endpoint to return HTTP 200"
fi

if ! grep -q "$test_issue_file" "$body_file"; then
	fail_with_response "Expected RePEc series endpoint to list $test_issue_file"
fi

assert_no_php_errors "RePEc series endpoint"

issue_repec_url="${series_repec_url}/${test_issue_file}"

curl -sS -L -D "$headers_file" -o "$body_file" "$issue_repec_url"

if ! grep -q ' 200 ' "$headers_file"; then
	fail_with_response "Expected RePEc issue file endpoint to return HTTP 200"
fi

if ! grep -Eiq 'content-type: text/plain' "$headers_file"; then
	fail_with_response "Expected RePEc issue file endpoint to return text/plain"
fi

if ! grep -q 'Template-Type: ReDIF-Article 1.0' "$body_file"; then
	fail_with_response "Expected RePEc issue file endpoint to publish article ReDIF templates"
fi

if ! grep -q "Handle: RePEc:${test_archive_code}:${test_series_code}:" "$body_file"; then
	fail_with_response "Expected RePEc issue file endpoint to use the configured archive and series in article handles"
fi

assert_no_php_errors "RePEc issue file endpoint"

echo "OJS 3.4 RePEc issue file smoke passed: $issue_repec_url"
