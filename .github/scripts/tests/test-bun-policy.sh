#!/usr/bin/env bash

set -Eeuo pipefail

test_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
scripts_dir=$(cd -- "$test_dir/.." && pwd)
fixtures_dir="$test_dir/fixtures"
mocks_dir="$test_dir/mocks"
test_count=0

fixture_digest() {
    sha256sum "$1" | awk '{ print "sha256:" $1 }'
}

index_fixture() {
    case "$1" in
        valid)
            printf '%s\n' "$fixtures_dir/index-valid.json"
            ;;
        malformed-index)
            printf '%s\n' "$fixtures_dir/index-malformed.json"
            ;;
        mismatched-attestation)
            printf '%s\n' "$fixtures_dir/index-mismatched-attestation.json"
            ;;
        inconsistent-architectures)
            printf '%s\n' "$fixtures_dir/index-inconsistent.json"
            ;;
        *)
            echo "Unknown test scenario: $1" >&2
            exit 1
            ;;
    esac
}

run_provenance_scenario() {
    local scenario=$1
    local index_digest

    index_digest=$(fixture_digest "$(index_fixture "$scenario")")

    PATH="$mocks_dir:$PATH" \
        FIXTURES_DIR="$fixtures_dir" \
        SCENARIO="$scenario" \
        IMAGE_REF="oven/bun:1.4-distroless@$index_digest" \
        "$scripts_dir/verify-bun-base.sh"
}

run_scan_scenario() {
    local scenario=$1

    PATH="$mocks_dir:$PATH" \
        FIXTURES_DIR="$fixtures_dir" \
        SCENARIO="$scenario" \
        CURRENT_IMAGE_REF="oven/bun:1.4-distroless@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" \
        TRIVY_IMAGE_REF="ghcr.io/aquasecurity/trivy:fixture@sha256:bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb" \
        "$scripts_dir/scan-bun-base-vulnerabilities.sh"
}

expect_success() {
    local name=$1
    shift
    local output

    if ! output=$("$@" 2>&1); then
        echo "FAIL: $name" >&2
        printf '%s\n' "$output" >&2
        exit 1
    fi

    test_count=$((test_count + 1))
    echo "PASS: $name"
}

expect_failure() {
    local name=$1
    local expected_message=$2
    shift 2
    local output

    if output=$("$@" 2>&1); then
        echo "FAIL: $name unexpectedly succeeded" >&2
        printf '%s\n' "$output" >&2
        exit 1
    fi

    if [[ -n "$expected_message" ]] && ! grep -Fq "$expected_message" <<< "$output"; then
        echo "FAIL: $name did not emit the expected diagnostic" >&2
        printf '%s\n' "$output" >&2
        exit 1
    fi

    test_count=$((test_count + 1))
    echo "PASS: $name"
}

expect_success \
    "valid multi-architecture Bun provenance" \
    run_provenance_scenario valid
expect_failure \
    "malformed OCI index" \
    "" \
    run_provenance_scenario malformed-index
expect_failure \
    "attestation subject mismatch" \
    "" \
    run_provenance_scenario mismatched-attestation
expect_failure \
    "cross-architecture provenance inconsistency" \
    "Bun provenance is inconsistent across architectures." \
    run_provenance_scenario inconsistent-architectures
expect_success \
    "clean Trivy reports" \
    run_scan_scenario scan-clean
expect_failure \
    "fixable HIGH vulnerability" \
    "CVE-2099-0001" \
    run_scan_scenario scan-vulnerable

echo "All $test_count Bun policy regression tests passed."
