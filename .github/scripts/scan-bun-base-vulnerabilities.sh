#!/usr/bin/env bash

set -Eeuo pipefail

extract_image_ref() {
    awk '
        $1 == "FROM" && $2 ~ /^oven\/bun:[^@[:space:]]+@sha256:[0-9a-f]{64}$/ {
            references[++count] = $2
        }
        END {
            if (count != 1) {
                printf "Expected exactly one digest-pinned Bun base in %s, found %d.\n", FILENAME, count > "/dev/stderr"
                exit 1
            }

            print references[1]
        }
    ' "$1"
}

current_ref=${CURRENT_IMAGE_REF:-}
work_dir=$(mktemp -d)
cache_dir=${TRIVY_CACHE_DIR:-"$work_dir/trivy-cache"}

run_trivy() {
    if [[ -z "${TRIVY_IMAGE_REF:-}" ]]; then
        trivy "$@"
        return
    fi

    mkdir -p "$cache_dir"
    docker run --rm \
        --read-only \
        --tmpfs /tmp:rw,nosuid,nodev,noexec \
        --cap-drop ALL \
        --security-opt no-new-privileges:true \
        --user "$(id -u):$(id -g)" \
        --volume "$work_dir:$work_dir" \
        --volume "$cache_dir:$cache_dir" \
        "$TRIVY_IMAGE_REF" "$@"
}

cleanup() {
    rm -rf "$work_dir"
}

trap cleanup EXIT

if [[ -z "$current_ref" ]]; then
    current_ref=$(extract_image_ref Dockerfile)
fi

for architecture in amd64 arm64; do
    report="$work_dir/current-$architecture.json"

    run_trivy image \
        --cache-dir "$cache_dir" \
        --quiet \
        --scanners vuln \
        --severity HIGH,CRITICAL \
        --ignore-unfixed \
        --format json \
        --output "$report" \
        --platform "linux/$architecture" \
        "$current_ref"

    if ! jq -e '[.Results[]?.Vulnerabilities[]?] | length == 0' "$report" >/dev/null; then
        echo "::error title=Fixable HIGH/CRITICAL vulnerabilities in Bun::linux/$architecture contains fixable HIGH/CRITICAL vulnerabilities."
        jq -r '
            .Results[]?.Vulnerabilities[]?
            | "\(.Severity) \(.VulnerabilityID) \(.PkgName) \(.InstalledVersion) -> \(.FixedVersion)"
        ' "$report"
        exit 1
    fi

    echo "No fixable HIGH/CRITICAL vulnerability in the Bun base on linux/$architecture."
done
