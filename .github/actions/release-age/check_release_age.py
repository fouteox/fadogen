#!/usr/bin/env python3
"""Refuse any upstream image or OCI chart reference younger than 72 hours.

Renovate cannot age references from registries that do not date their tags
(GHCR, Quay, registry.k8s.io: renovatebot/renovate#39064), so the policy lets
them through without a cooldown. This check closes that gap for every change,
from Renovate or not: it compares the Docker dependencies Renovate extracts on
the base and on the head, and dates each new digest with its own registry. A
reference the registry cannot date is refused as well.
"""

import argparse
import datetime as dt
import fnmatch
import json
import os
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

MINIMUM_AGE = dt.timedelta(hours=72)
GHCR_PAGES = 10
QUAY_PAGES = 10
# Registries that serve another registry's index under the same digest.
ALIASES = {
    "index.docker.io/": "docker.io/",
    "mirror.gcr.io/": "docker.io/",
    "docker.dragonflydb.io/dragonflydb/": "ghcr.io/dragonflydb/",
}


class Undated(Exception):
    """The registry gives no publication date for this reference."""


def canonical(name):
    """Fully qualified repository name, as the registry serves it."""
    first = name.split("/", 1)[0]
    if "." not in first and ":" not in first and first != "localhost":
        name = "docker.io/" + (name if "/" in name else "library/" + name)
    for source, target in ALIASES.items():
        if name.startswith(source):
            return target + name[len(source) :]
    return name


def references(report):
    """Docker references of a Renovate extract report: name, tag, digest."""
    found = set()
    for repository in report["repositories"].values():
        for files in repository["packageFiles"].values():
            for package_file in files:
                for dep in package_file["deps"]:
                    if dep.get("datasource") != "docker" or dep.get("skipReason"):
                        continue
                    digest = dep.get("currentDigest")
                    name = dep.get("packageName") or dep.get("depName")
                    if name and digest:
                        found.add((canonical(name), dep.get("currentValue"), digest))
    return found


def parse_time(value):
    if isinstance(value, (int, float)) or (isinstance(value, str) and value.isdigit()):
        seconds = int(value)
        return dt.datetime.fromtimestamp(
            seconds / 1000 if seconds > 10**11 else seconds, dt.UTC
        )
    if not value:
        raise Undated("date absente")
    return dt.datetime.fromisoformat(value)


class Registries:
    """Publication date of a digest, read from the registry that serves it."""

    def __init__(self, fetch, github_token):
        self.fetch = fetch
        self.github_token = github_token

    def published(self, name, tag, digest):
        registry, path = name.split("/", 1)
        reader = {
            "docker.io": self.docker_hub,
            "quay.io": self.quay,
            "ghcr.io": self.ghcr,
            "registry.k8s.io": self.k8s,
        }.get(registry)
        if reader is None:
            raise Undated(f"le registre {registry} ne date pas ses images")
        return reader(path, tag, digest)

    def docker_hub(self, path, tag, digest):
        if not tag:
            raise Undated("Docker Hub ne date qu'un tag")
        namespace, repository = path.split("/", 1)
        body = self.fetch(
            "https://hub.docker.com/v2/namespaces/"
            f"{namespace}/repositories/{repository}/tags/{urllib.parse.quote(tag)}"
        )
        if body.get("digest") != digest:
            raise Undated(f"Docker Hub sert un autre digest sous {tag}")
        return parse_time(body.get("tag_last_pushed"))

    def quay(self, path, tag, digest):
        # Quay dates tags, not manifests: the first tag that served the digest
        # dates it. Without a tag, the recent tag history is searched.
        if tag:
            pages = [f"specificTag={urllib.parse.quote(tag)}&onlyActiveTags=false"]
        else:
            pages = [
                f"onlyActiveTags=false&limit=100&page={n}"
                for n in range(1, QUAY_PAGES + 1)
            ]
        starts = []
        for query in pages:
            body = self.fetch(f"https://quay.io/api/v1/repository/{path}/tag/?{query}")
            tags = body.get("tags", [])
            starts += [
                t["start_ts"] for t in tags if t.get("manifest_digest") == digest
            ]
            if not body.get("has_additional"):
                break
        if not starts:
            raise Undated("Quay n'a jamais servi ce digest sous un tag récent")
        return parse_time(min(starts))

    def k8s(self, path, _tag, digest):
        body = self.fetch(f"https://registry.k8s.io/v2/{path}/tags/list")
        manifest = body.get("manifest", {}).get(digest)
        if not manifest:
            raise Undated("registry.k8s.io ne liste pas ce digest")
        return parse_time(manifest.get("timeUploadedMs"))

    def ghcr(self, path, _tag, digest):
        owner, package = path.split("/", 1)
        quoted = urllib.parse.quote(package, safe="")
        headers = (
            {"Authorization": f"Bearer {self.github_token}"}
            if self.github_token
            else {}
        )
        for kind in ("orgs", "users"):
            for page in range(1, GHCR_PAGES + 1):
                url = (
                    f"https://api.github.com/{kind}/{owner}/packages/container/{quoted}"
                    f"/versions?per_page=100&page={page}"
                )
                try:
                    versions = self.fetch(url, headers)
                except urllib.error.HTTPError as error:
                    if error.code == 404 and page == 1:
                        break
                    raise
                for version in versions:
                    if version.get("name") == digest:
                        return parse_time(version.get("created_at"))
                if len(versions) < 100:
                    raise Undated("GHCR ne liste pas ce digest")
        raise Undated(
            f"GHCR ne liste pas ce digest dans ses {GHCR_PAGES} pages récentes"
        )


def fetch_json(url, headers=None):
    request = urllib.request.Request(
        url,
        headers={
            "Accept": "application/json",
            "User-Agent": "release-age-check",
            **(headers or {}),
        },
    )
    with urllib.request.urlopen(request, timeout=30) as response:
        return json.load(response)


def exempt(name, exemptions):
    return next(
        (e for e in exemptions if fnmatch.fnmatchcase(name, e["package"])), None
    )


def verdicts(base, head, registries, exemptions, now):
    """One line per new reference; the boolean says whether it is refused."""
    lines = []
    for name, tag, digest in sorted(
        head - base, key=lambda r: (r[0], r[1] or "", r[2])
    ):
        label = f"{name}:{tag}@{digest[:19]}"
        rule = exempt(name, exemptions)
        if rule:
            lines.append((False, f"{label} exempté : {rule['reason']}"))
            continue
        try:
            published = registries.published(name, tag, digest)
        except Undated as error:
            lines.append((True, f"{label} non daté : {error}"))
            continue
        age = now - published
        hours = int(age.total_seconds() // 3600)
        refused = age < MINIMUM_AGE
        state = "trop récent" if refused else "mûr"
        lines.append(
            (
                refused,
                f"{label} {state} : publié le {published:%Y-%m-%d %H:%M} UTC, il y a {hours} h",
            )
        )
    return lines


def load_exemptions(path):
    entries = json.loads(path.read_text())["exemptions"]
    for entry in entries:
        if not entry.get("package") or not entry.get("reason"):
            raise ValueError("Chaque exemption nomme un paquet et sa raison")
    return entries


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("base_report", type=Path)
    parser.add_argument("head_report", type=Path)
    parser.add_argument("--exemptions", type=Path, required=True)
    args = parser.parse_args(argv)
    base = references(json.loads(args.base_report.read_text()))
    head = references(json.loads(args.head_report.read_text()))
    registries = Registries(fetch_json, os.environ.get("GH_TOKEN"))
    lines = verdicts(
        base,
        head,
        registries,
        load_exemptions(args.exemptions),
        dt.datetime.now(dt.UTC),
    )
    for refused, line in lines:
        print(("REFUSÉ " if refused else "OK     ") + line)
    if not lines:
        print("Aucune nouvelle référence d'image ou de chart OCI.")
    return 1 if any(refused for refused, _ in lines) else 0


if __name__ == "__main__":
    sys.exit(main())
