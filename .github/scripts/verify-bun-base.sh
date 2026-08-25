#!/usr/bin/env bash

set -Eeuo pipefail

dockerfile=${1:-Dockerfile}
image_ref=${IMAGE_REF:-}

if [[ -z "$image_ref" ]]; then
    image_ref=$(awk '
        $1 == "FROM" && $2 ~ /^oven\/bun:[^@[:space:]]+@sha256:[0-9a-f]{64}$/ {
            references[++count] = $2
        }
        END {
            if (count != 1) {
                printf "Expected exactly one digest-pinned Bun base, found %d.\n", count > "/dev/stderr"
                exit 1
            }

            print references[1]
        }
    ' "$dockerfile")
fi

if [[ ! "$image_ref" =~ ^oven/bun:([0-9]+)\.([0-9]+)-distroless@(sha256:[0-9a-f]{64})$ ]]; then
    echo "Expected one digest-pinned oven/bun:<major>.<minor>-distroless base in $dockerfile." >&2
    exit 1
fi

declared_major=${BASH_REMATCH[1]}
declared_minor=${BASH_REMATCH[2]}
declared_index_digest=${BASH_REMATCH[3]}
repository=oven/bun
work_dir=$(mktemp -d)

cleanup() {
    rm -rf "$work_dir"
}

trap cleanup EXIT

index_file="$work_dir/index.json"
docker buildx imagetools inspect "$image_ref" --raw > "$index_file"

actual_index_digest="sha256:$(sha256sum "$index_file" | awk '{ print $1 }')"

if [[ "$actual_index_digest" != "$declared_index_digest" ]]; then
    echo "Index digest mismatch: expected $declared_index_digest, got $actual_index_digest." >&2
    exit 1
fi

jq -e '
    .mediaType == "application/vnd.oci.image.index.v1+json"
    and ([
        .manifests[]
        | select(
            .platform.os == "linux"
            and (.platform.architecture == "amd64" or .platform.architecture == "arm64")
        )
    ] | length == 2)
    and ([
        .manifests[]
        | select(.annotations["vnd.docker.reference.type"] == "attestation-manifest")
    ] | length == 2)
' "$index_file" >/dev/null

registry_token=$(curl \
    --fail \
    --silent \
    --show-error \
    --location \
    --retry 3 \
    --retry-all-errors \
    --get \
    --data-urlencode service=registry.docker.io \
    --data-urlencode "scope=repository:$repository:pull" \
    https://auth.docker.io/token | jq -er .token)

release_version=
source_sha=
run_id=
run_attempt=

for architecture in amd64 arm64; do
    platform_digest=$(jq -er \
        --arg architecture "$architecture" \
        '.manifests[]
            | select(.platform.os == "linux" and .platform.architecture == $architecture)
            | .digest' \
        "$index_file")

    attestation_digest=$(jq -er \
        --arg platform_digest "$platform_digest" \
        '.manifests[]
            | select(
                .annotations["vnd.docker.reference.type"] == "attestation-manifest"
                and .annotations["vnd.docker.reference.digest"] == $platform_digest
            )
            | .digest' \
        "$index_file")

    attestation_file="$work_dir/attestation-$architecture.json"
    docker buildx imagetools inspect "$repository@$attestation_digest" --raw > "$attestation_file"

    actual_attestation_digest="sha256:$(sha256sum "$attestation_file" | awk '{ print $1 }')"

    if [[ "$actual_attestation_digest" != "$attestation_digest" ]]; then
        echo "Attestation manifest digest mismatch for linux/$architecture." >&2
        exit 1
    fi

    jq -e \
        --arg platform_digest "$platform_digest" \
        '
        .mediaType == "application/vnd.oci.image.manifest.v1+json"
        and .artifactType == "application/vnd.docker.attestation.manifest.v1+json"
        and .subject.digest == $platform_digest
        and ([
            .layers[]
            | select(
                .mediaType == "application/vnd.in-toto+json"
                and .annotations["in-toto.io/predicate-type"] == "https://slsa.dev/provenance/v1"
            )
        ] | length == 1)
        ' \
        "$attestation_file" >/dev/null

    provenance_digest=$(jq -er '
        .layers[]
        | select(
            .mediaType == "application/vnd.in-toto+json"
            and .annotations["in-toto.io/predicate-type"] == "https://slsa.dev/provenance/v1"
        )
        | .digest
    ' "$attestation_file")

    provenance_file="$work_dir/provenance-$architecture.json"
    curl \
        --fail \
        --silent \
        --show-error \
        --location \
        --retry 3 \
        --retry-all-errors \
        --header "Authorization: Bearer $registry_token" \
        "https://registry-1.docker.io/v2/$repository/blobs/$provenance_digest" \
        --output "$provenance_file"

    actual_provenance_digest="sha256:$(sha256sum "$provenance_file" | awk '{ print $1 }')"

    if [[ "$actual_provenance_digest" != "$provenance_digest" ]]; then
        echo "Provenance blob digest mismatch for linux/$architecture." >&2
        exit 1
    fi

    platform_digest_hex=${platform_digest#sha256:}
    architecture_version=$(jq -er \
        '.predicate.buildDefinition.externalParameters.request.args["build-arg:BUN_VERSION"]
            | capture("^bun-v(?<version>[0-9]+\\.[0-9]+\\.[0-9]+)$")
            | .version' \
        "$provenance_file")

    if [[ "$architecture_version" != "$declared_major.$declared_minor."* ]]; then
        echo "Bun provenance version $architecture_version does not match the declared $declared_major.$declared_minor tag." >&2
        exit 1
    fi

    architecture_source_sha=$(jq -er \
        '.predicate.buildDefinition.externalParameters.request.args["label:org.opencontainers.image.revision"]' \
        "$provenance_file")
    architecture_run_id=$(jq -er \
        '.predicate.buildDefinition.internalParameters.github_run_id | tostring' \
        "$provenance_file")
    architecture_run_attempt=$(jq -er \
        '.predicate.buildDefinition.internalParameters.github_run_attempt | tostring' \
        "$provenance_file")

    jq -e \
        --arg architecture "$architecture" \
        --arg platform_digest "$platform_digest_hex" \
        --arg release_version "$architecture_version" \
        --arg source_sha "$architecture_source_sha" \
        --arg run_id "$architecture_run_id" \
        --arg run_attempt "$architecture_run_attempt" \
        '
        ._type == "https://in-toto.io/Statement/v1"
        and .predicateType == "https://slsa.dev/provenance/v1"
        and any(
            .subject[]?;
            .digest.sha256 == $platform_digest
            and (.name | endswith("-distroless?platform=linux%2F" + $architecture))
        )
        and .predicate.buildDefinition.buildType == "https://github.com/moby/buildkit/blob/master/docs/attestations/slsa-definitions.md"
        and .predicate.buildDefinition.externalParameters.configSource.path == "Dockerfile"
        and .predicate.buildDefinition.externalParameters.request.args["build-arg:BUN_VERSION"] == ("bun-v" + $release_version)
        and .predicate.buildDefinition.externalParameters.request.args["label:org.opencontainers.image.revision"] == $source_sha
        and .predicate.buildDefinition.externalParameters.request.args["label:org.opencontainers.image.source"] == "https://github.com/oven-sh/bun"
        and .predicate.buildDefinition.externalParameters.request.args["label:org.opencontainers.image.version"] == ($release_version + "-distroless")
        and .predicate.buildDefinition.internalParameters.github_event_name == "release"
        and .predicate.buildDefinition.internalParameters.github_ref == ("refs/tags/bun-v" + $release_version)
        and .predicate.buildDefinition.internalParameters.github_repository == "oven-sh/bun"
        and (.predicate.buildDefinition.internalParameters.github_run_id | tostring) == $run_id
        and (.predicate.buildDefinition.internalParameters.github_run_attempt | tostring) == $run_attempt
        and .predicate.runDetails.builder.id == ("https://github.com/oven-sh/bun/actions/runs/" + $run_id + "/attempts/" + $run_attempt)
        and .predicate.runDetails.metadata.startedOn != null
        and .predicate.runDetails.metadata.finishedOn != null
        and .predicate.runDetails.metadata.buildkit_metadata.vcs["localdir:context"] == "dockerhub/distroless"
        and .predicate.runDetails.metadata.buildkit_metadata.vcs["localdir:dockerfile"] == "dockerhub/distroless"
        and .predicate.runDetails.metadata.buildkit_metadata.vcs.revision == $source_sha
        and .predicate.runDetails.metadata.buildkit_metadata.vcs.source == "https://github.com/oven-sh/bun"
        ' \
        "$provenance_file" >/dev/null

    if [[ -z "$release_version" ]]; then
        release_version=$architecture_version
        source_sha=$architecture_source_sha
        run_id=$architecture_run_id
        run_attempt=$architecture_run_attempt
    elif [[
        "$architecture_version" != "$release_version"
        || "$architecture_source_sha" != "$source_sha"
        || "$architecture_run_id" != "$run_id"
        || "$architecture_run_attempt" != "$run_attempt"
    ]]; then
        echo "Bun provenance is inconsistent across architectures." >&2
        exit 1
    fi

    echo "Verified linux/$architecture Bun provenance from run $architecture_run_id attempt $architecture_run_attempt."
done

release_file="$work_dir/release.json"
gh api "repos/oven-sh/bun/releases/tags/bun-v$release_version" > "$release_file"

jq -e \
    --arg release_version "$release_version" \
    --arg source_sha "$source_sha" \
    '
    .tag_name == ("bun-v" + $release_version)
    and .target_commitish == $source_sha
    and .draft == false
    and .prerelease == false
    and .published_at != null
    ' \
    "$release_file" >/dev/null

run_file="$work_dir/run.json"
gh api "repos/oven-sh/bun/actions/runs/$run_id" > "$run_file"

jq -e \
    --arg release_version "$release_version" \
    --arg source_sha "$source_sha" \
    --arg run_attempt "$run_attempt" \
    '
    .status == "completed"
    and .event == "release"
    and .head_sha == $source_sha
    and .head_branch == ("bun-v" + $release_version)
    and .repository.full_name == "oven-sh/bun"
    and .head_repository.full_name == "oven-sh/bun"
    and .path == ".github/workflows/release.yml"
    and (.run_attempt | tostring) == $run_attempt
    ' \
    "$run_file" >/dev/null

jobs_file="$work_dir/jobs.json"
gh api --paginate "repos/oven-sh/bun/actions/runs/$run_id/jobs" > "$jobs_file"

jq -s -e \
    --arg source_sha "$source_sha" \
    '
    [.[].jobs[]
        | select(
            .name == "Release to Dockerhub (distroless, -distroless)"
            and .status == "completed"
            and .conclusion == "success"
            and .head_sha == $source_sha
        )
    ] | length == 1
    ' \
    "$jobs_file" >/dev/null

echo "Verified pinned Bun index $declared_index_digest from official release bun-v$release_version."
