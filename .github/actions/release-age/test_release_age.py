#!/usr/bin/env python3
"""New upstream references must be dated by their registry and 72 hours old."""

import datetime as dt
import json
import tempfile
import unittest
import urllib.error
from pathlib import Path

import check_release_age as age

NOW = dt.datetime(2026, 11, 11, 12, tzinfo=dt.UTC)
OLD = "2026-11-08T11:00:00Z"
NEW = "2026-11-10T12:00:00Z"
DIGEST = "sha256:" + "a" * 64
OTHER = "sha256:" + "b" * 64


def report(*deps):
    files = [{"packageFile": "x.yaml", "deps": list(deps)}]
    return {"repositories": {"local": {"packageFiles": {"regex": files}}}}


def dep(name, tag, digest=DIGEST, **extra):
    return {
        "datasource": "docker",
        "depName": name,
        "currentValue": tag,
        "currentDigest": digest,
        **extra,
    }


class FakeRegistries:
    """Serves the registry answers a test sets, and records every request."""

    def __init__(self, answers):
        self.answers = answers
        self.requests = []

    def __call__(self, url, headers=None):
        self.requests.append((url, headers or {}))
        for prefix, answer in self.answers.items():
            if url.startswith(prefix):
                if isinstance(answer, Exception):
                    raise answer
                return answer
        raise AssertionError("requête inattendue " + url)


def verdict(answers, name, tag, digest=DIGEST, exemptions=()):
    fake = FakeRegistries(answers)
    registries = age.Registries(fake, "token")
    lines = age.verdicts(
        set(), {(name, tag, digest)}, registries, list(exemptions), NOW
    )
    return lines[0], fake


HUB = "https://hub.docker.com/v2/namespaces/library/repositories/traefik/tags/v3.7.13"
QUAY = "https://quay.io/api/v1/repository/backube/volsync/tag/"
GHCR = (
    "https://api.github.com/orgs/cloudnative-pg/packages/container/postgresql/versions"
)
K8S = "https://registry.k8s.io/v2/cpa/cluster-proportional-autoscaler/tags/list"


class References(unittest.TestCase):
    def test_only_pinned_docker_dependencies_count(self):
        found = age.references(
            report(
                dep("traefik", "v3.7.13"),
                dep("ghcr.io/x/y", "1", None),
                dep("ghcr.io/x/z", "1", skipReason="ignored"),
                {
                    "datasource": "helm",
                    "depName": "chart",
                    "currentValue": "1",
                    "currentDigest": DIGEST,
                },
            )
        )
        self.assertEqual(found, {("docker.io/library/traefik", "v3.7.13", DIGEST)})

    def test_mirrors_resolve_to_the_registry_that_dates_them(self):
        self.assertEqual(
            age.canonical("mirror.gcr.io/aquasec/trivy"), "docker.io/aquasec/trivy"
        )
        self.assertEqual(
            age.canonical("index.docker.io/collabora/code"), "docker.io/collabora/code"
        )
        self.assertEqual(
            age.canonical("docker.dragonflydb.io/dragonflydb/operator"),
            "ghcr.io/dragonflydb/operator",
        )
        self.assertEqual(age.canonical("rclone/rclone"), "docker.io/rclone/rclone")

    def test_unchanged_references_are_not_checked(self):
        fake = FakeRegistries({})
        same = {("docker.io/library/traefik", "v3.7.13", DIGEST)}
        self.assertEqual(
            age.verdicts(same, same, age.Registries(fake, None), [], NOW), []
        )
        self.assertEqual(fake.requests, [])


class Registries(unittest.TestCase):
    def test_docker_hub_dates_the_tag_that_serves_the_digest(self):
        (refused, line), _ = verdict(
            {HUB: {"digest": DIGEST, "tag_last_pushed": OLD}},
            "docker.io/library/traefik",
            "v3.7.13",
        )
        self.assertFalse(refused, line)
        (refused, line), _ = verdict(
            {HUB: {"digest": DIGEST, "tag_last_pushed": NEW}},
            "docker.io/library/traefik",
            "v3.7.13",
        )
        self.assertTrue(refused)
        self.assertIn("trop récent", line)

    def test_docker_hub_refuses_a_tag_that_now_serves_another_digest(self):
        (refused, line), _ = verdict(
            {HUB: {"digest": OTHER, "tag_last_pushed": OLD}},
            "docker.io/library/traefik",
            "v3.7.13",
        )
        self.assertTrue(refused)
        self.assertIn("non daté", line)

    def test_quay_dates_the_first_tag_that_served_the_digest(self):
        tags = {
            "tags": [
                {"manifest_digest": DIGEST, "start_ts": 1762862400},
                {"manifest_digest": OTHER, "start_ts": 1},
            ]
        }
        (refused, line), fake = verdict(
            {QUAY: tags}, "quay.io/backube/volsync", "0.15.0"
        )
        self.assertFalse(refused, line)
        self.assertIn("specificTag=0.15.0", fake.requests[0][0])

    def test_quay_searches_recent_tags_for_a_digest_without_tag(self):
        pages = {
            QUAY: {
                "tags": [{"manifest_digest": OTHER, "start_ts": 1}],
                "has_additional": False,
            }
        }
        (refused, line), _ = verdict(pages, "quay.io/backube/volsync", None)
        self.assertTrue(refused)
        self.assertIn("non daté", line)

    def test_ghcr_dates_the_package_version_named_by_the_digest(self):
        versions = [
            {"name": OTHER, "created_at": NEW},
            {"name": DIGEST, "created_at": OLD},
        ]
        (refused, line), fake = verdict(
            {GHCR: versions}, "ghcr.io/cloudnative-pg/postgresql", "18.6"
        )
        self.assertFalse(refused, line)
        self.assertEqual(fake.requests[0][1], {"Authorization": "Bearer token"})

    def test_ghcr_falls_back_to_a_user_owner(self):
        missing = urllib.error.HTTPError(GHCR, 404, "Not Found", None, None)  # type: ignore[arg-type]
        user = "https://api.github.com/users/cloudnative-pg/packages/container/postgresql/versions"
        (refused, line), _ = verdict(
            {GHCR: missing, user: [{"name": DIGEST, "created_at": OLD}]},
            "ghcr.io/cloudnative-pg/postgresql",
            "18.6",
        )
        self.assertFalse(refused, line)

    def test_ghcr_refuses_a_digest_it_does_not_list(self):
        (refused, line), _ = verdict(
            {GHCR: [{"name": OTHER, "created_at": OLD}]},
            "ghcr.io/cloudnative-pg/postgresql",
            "18.6",
        )
        self.assertTrue(refused)
        self.assertIn("non daté", line)

    def test_ghcr_failures_other_than_a_missing_owner_stay_errors(self):
        forbidden = urllib.error.HTTPError(GHCR, 403, "Forbidden", None, None)  # type: ignore[arg-type]
        with self.assertRaises(urllib.error.HTTPError):
            verdict({GHCR: forbidden}, "ghcr.io/cloudnative-pg/postgresql", "18.6")

    def test_registry_k8s_io_dates_the_upload(self):
        listing = {"manifest": {DIGEST: {"timeUploadedMs": "1762603200000"}}}
        (refused, line), _ = verdict(
            {K8S: listing},
            "registry.k8s.io/cpa/cluster-proportional-autoscaler",
            "v1.11.0",
        )
        self.assertFalse(refused, line)

    def test_an_undated_registry_is_refused(self):
        (refused, line), fake = verdict({}, "gcr.io/example/image", "1")
        self.assertTrue(refused)
        self.assertIn("ne date pas", line)
        self.assertEqual(fake.requests, [])


class Exemptions(unittest.TestCase):
    def test_an_exemption_needs_a_reason_and_skips_the_registry(self):
        rule = {
            "package": "ghcr.io/fouteox/*",
            "reason": "image construite par notre CI",
        }
        (refused, line), fake = verdict(
            {}, "ghcr.io/fouteox/footeox", "run-1", exemptions=[rule]
        )
        self.assertFalse(refused)
        self.assertIn("exempté", line)
        self.assertEqual(fake.requests, [])
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "exemptions.json"
            path.write_text(json.dumps({"exemptions": [{"package": "x"}]}))
            with self.assertRaises(ValueError):
                age.load_exemptions(path)


if __name__ == "__main__":
    unittest.main()
