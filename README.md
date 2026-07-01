# Fadogen - Build and deploy applications easily.

Fadogen is a tool that allows you to create applications and deploy them very easily. Think of it as the shadcn of deployment. No tools are required (except ddev).

## Building & deployment

Container images are built and pushed to GHCR by
[`.github/workflows/build.yml`](.github/workflows/build.yml) on every merge to
`main` (a `-app` image and a `-ssr` image).

Front-end assets are pre-built on the CI runner (`bun run build:ssr`), so the
Dockerfile `builder` stage only installs PHP dependencies and **no `.env` secret
is ever baked into the image**.

The workflow needs a single repository secret, **`ENV_FILE_BASE64`** — the
base64 of a **build-only** `.env` (see [`.env.production.dist`](.env.production.dist)).
It holds only what the asset build needs (app name/URL and `VITE_*`); the
`APP_KEY` is generated on the fly during the build, and **no runtime secret**
(database, cache, mail…) belongs here. Runtime configuration is provided by the
deployment platform (a mounted, encrypted `.env` plus environment overrides).

Regenerate the secret after editing the template:

```sh
base64 -i .env.production | gh secret set ENV_FILE_BASE64 -R fouteox/fadogen
```
