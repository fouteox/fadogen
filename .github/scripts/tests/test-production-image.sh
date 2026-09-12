#!/usr/bin/env bash

set -Eeuo pipefail

image=${1:?Usage: test-production-image.sh IMAGE}
network=
ssr_container=
app_container=

cleanup() {
    local status=$?
    for container in "$app_container" "$ssr_container"; do
        if [[ -n "$container" ]]; then
            if (( status != 0 )); then
                docker logs "$container" >&2 || true
            fi
            docker rm --force "$container" >/dev/null
        fi
    done
    if [[ -n "$network" ]]; then
        docker network rm "$network" >/dev/null
    fi
}
trap cleanup EXIT

runtime_options=(
    --user 33:33
    --cap-drop ALL
    --security-opt no-new-privileges:true
    --env APP_ENV=production
    --env APP_DEBUG=false
    --env APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
    --env AUTORUN_ENABLED=false
    --env LOG_CHANNEL=stderr
    --env CACHE_STORE=array
    --env DB_CONNECTION=sqlite
    --env DB_DATABASE=:memory:
    --env QUEUE_CONNECTION=sync
    --env SESSION_DRIVER=array
    --env INERTIA_SSR_ENABLED=true
    --env INERTIA_SSR_RUNTIME=bun
    --env INERTIA_SSR_ENSURE_RUNTIME_EXISTS=true
    --env INERTIA_SSR_THROW_ON_ERROR=true
)

docker run --rm --network none "${runtime_options[@]}" \
    --entrypoint sh "$image" -euc '
        test "$(id -u)" = 33
        test -s public/build/manifest.json
        test -s bootstrap/ssr/app.js
        test ! -d node_modules
        bun --version
        php artisan about --only=environment
        php -r '\''
            foreach (["bcmath", "pdo_sqlite"] as $extension) {
                if (! extension_loaded($extension)) {
                    fwrite(STDERR, "Missing PHP extension: {$extension}\n");
                    exit(1);
                }
            }
        '\''
    '

wait_for_http() {
    local container=$1 url=$2
    for _ in {1..30}; do
        if docker exec "$container" curl --fail --silent --max-time 2 "$url" >/dev/null; then
            return 0
        fi
        if [[ "$(docker inspect --format '{{.State.Running}}' "$container")" != true ]]; then
            break
        fi
        sleep 1
    done
    echo "Container did not become ready: $url" >&2
    return 1
}

network=$(docker network create --internal "fadogen-image-test-$$-$RANDOM")
ssr_container=$(docker run --detach --network "$network" --network-alias ssr \
    "${runtime_options[@]}" --read-only --tmpfs /tmp:rw,nosuid,nodev,uid=33,gid=33 \
    --env INERTIA_SSR_URL=http://127.0.0.1:13714 \
    "$image" php /var/www/html/artisan inertia:start-ssr --runtime=bun)
wait_for_http "$ssr_container" http://127.0.0.1:13714/health

app_container=$(docker run --detach --network "$network" "${runtime_options[@]}" \
    --env INERTIA_SSR_URL=http://ssr:13714 \
    "$image" php /var/www/html/artisan octane:start --server=frankenphp --port=8080 --workers=1)
wait_for_http "$app_container" http://127.0.0.1:8080/up

html=$(docker exec "$app_container" curl --fail --silent --show-error --max-time 15 http://127.0.0.1:8080/)
if ! grep -Fq 'data-server-rendered="true"' <<< "$html"; then
    echo 'The web response was not server-rendered by the SSR container.' >&2
    exit 1
fi

docker stop --time 15 "$ssr_container" >/dev/null
if [[ "$(docker inspect --format '{{.State.ExitCode}}' "$ssr_container")" = 137 ]]; then
    echo 'The SSR process required SIGKILL instead of shutting down gracefully.' >&2
    exit 1
fi

echo 'Unified image: rootless web and SSR, real HTML rendering, graceful SSR shutdown passed.'
