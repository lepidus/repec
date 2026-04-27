#!/usr/bin/env bash
set -euo pipefail

base_url="${OJS34_BASE_URL:-http://127.0.0.1:8034/index.php}"
context_path="${OJS34_CONTEXT_PATH:-publicknowledge}"
test_user="${OJS_TEST_USER:-admin}"
test_password="${OJS_TEST_PASSWORD:-admin}"
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

curl -sS -D "$headers_file" -o "$body_file" "${base_url%/}/index/repec/"

if ! head -n 1 "$headers_file" | grep -q ' 200 '; then
	fail_with_response "Expected /index/repec/ to return HTTP 200"
fi

if ! grep -q '<title>RePEc archives</title>' "$body_file"; then
	fail_with_response "Expected /index/repec/ to return the RePEc archive index HTML"
fi

assert_no_php_errors "/index/repec/"

echo "OJS 3.4 RePEc smoke passed: ${base_url%/}/index/repec/"

login_page="${base_url%/}/${context_path}/login"
login_post="${base_url%/}/${context_path}/login/signIn"

curl -sS -c "$cookie_jar" -D "$headers_file" -o "$body_file" "$login_page"

if ! head -n 1 "$headers_file" | grep -q ' 200 '; then
	fail_with_response "Expected OJS login page to return HTTP 200"
fi

csrf_token="$(sed -n 's/.*name="csrfToken" value="\([^"]*\)".*/\1/p' "$body_file" | head -n 1)"

if [ -z "$csrf_token" ]; then
	fail_with_response "Could not find csrfToken in OJS login page"
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
