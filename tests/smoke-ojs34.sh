#!/usr/bin/env bash
set -euo pipefail

base_url="${OJS34_BASE_URL:-http://127.0.0.1:8034/index.php}"
tmp_dir="${TMPDIR:-/tmp}"
headers_file="$(mktemp "${tmp_dir}/repec-smoke-headers.XXXXXX")"
body_file="$(mktemp "${tmp_dir}/repec-smoke-body.XXXXXX")"

cleanup() {
	rm -f "$headers_file" "$body_file"
}
trap cleanup EXIT

curl -sS -D "$headers_file" -o "$body_file" "${base_url%/}/index/repec/"

if ! head -n 1 "$headers_file" | grep -q ' 200 '; then
	echo "Expected /index/repec/ to return HTTP 200" >&2
	cat "$headers_file" >&2
	exit 1
fi

if ! grep -q '<title>RePEc archives</title>' "$body_file"; then
	echo "Expected /index/repec/ to return the RePEc archive index HTML" >&2
	cat "$body_file" >&2
	exit 1
fi

if grep -Eiq 'Fatal error|Exception|Stack trace|Warning:' "$body_file"; then
	echo "Unexpected PHP error output in /index/repec/" >&2
	cat "$body_file" >&2
	exit 1
fi

echo "OJS 3.4 RePEc smoke passed: ${base_url%/}/index/repec/"

if [ -z "${OJS34_COOKIE:-}" ]; then
	echo "OJS 3.4 RePEc settings smoke skipped: set OJS34_COOKIE to test the authenticated settings modal."
	exit 0
fi

context_path="${OJS34_CONTEXT_PATH:-publicknowledge}"
settings_url="${base_url%/}/${context_path}/%24%24%24call%24%24%24/grid/settings/plugins/plugin-grid/manage?verb=settings&plugin=repecplugin&category=generic&_=0"

curl -sS -D "$headers_file" -o "$body_file" \
	-H 'X-Requested-With: XMLHttpRequest' \
	-H 'Accept: application/json, text/javascript, */*; q=0.01' \
	-H "Cookie: ${OJS34_COOKIE}" \
	"$settings_url"

if ! head -n 1 "$headers_file" | grep -q ' 200 '; then
	echo "Expected RePEc settings modal endpoint to return HTTP 200" >&2
	cat "$headers_file" >&2
	cat "$body_file" >&2
	exit 1
fi

if ! grep -Eiq 'content-type: application/json|content-type: text/json' "$headers_file"; then
	echo "Expected RePEc settings modal endpoint to return JSON" >&2
	cat "$headers_file" >&2
	cat "$body_file" >&2
	exit 1
fi

if grep -Eiq 'Fatal error|Exception|Stack trace|Warning:|Failed Ajax request' "$body_file"; then
	echo "Unexpected PHP/Ajax error output in RePEc settings modal endpoint" >&2
	cat "$body_file" >&2
	exit 1
fi

if ! grep -q '"status":true' "$body_file"; then
	echo "Expected RePEc settings modal JSONMessage with status=true" >&2
	cat "$body_file" >&2
	exit 1
fi

echo "OJS 3.4 RePEc settings smoke passed: $settings_url"
