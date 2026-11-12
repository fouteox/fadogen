# Fadogen - Build and deploy applications easily.

Fadogen is a tool that allows you to create applications and deploy them very easily. Think of it as the shadcn of deployment. No tools are required (except ddev).

## Building & deployment

A single container image, serving both the web application and the Inertia SSR
process, is built and pushed to GHCR by
[`.github/workflows/build.yml`](.github/workflows/build.yml) on every merge to
`main`, and rebuilt weekly to pick up base image fixes. The build itself runs in
the reusable workflow
[`.github/workflows/build-image.yml`](.github/workflows/build-image.yml), which
signs and attests the image.

Front-end assets are pre-built on the CI runner (`vp run build:ssr`) from the
versioned [`.env.example`](.env.example), with an application key generated for
the job and discarded afterwards. **CI never receives the production
environment and no `.env` is baked into the image**: runtime configuration is
provided by the deployment platform.
