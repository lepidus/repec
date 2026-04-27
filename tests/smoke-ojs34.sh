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
