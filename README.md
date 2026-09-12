# Fadogen - Build and deploy applications easily.

Fadogen generates a DDEV configuration and an installation script for Laravel projects.

## Development and checks

Use the PHP version required by `composer.json` and install the frontend toolchain with Vite+ (the Node and Bun versions are pinned in `package.json`).

```sh
composer install
vp install --frozen-lockfile
php artisan wayfinder:generate --no-interaction
composer test:types
composer test:unit
vp run test
vp run types
vp run lint:check
vp run format:check
vp run build:ssr
```

The PHP coverage gate is 90% and requires PCOV or Xdebug. Tests use SQLite in memory, fake external HTTP requests and isolate generated archives; they do not install generated projects with DDEV.

Axios is retained as a development dependency because the Inertia Core 3.7.0 declarations export its optional adapter types. Application requests use Inertia’s native HTTP client, and TypeScript checks dependency declarations with `skipLibCheck: false`.

## Generated archives

Web and API creation share a limit of 60 requests per minute per IP address. Completed, downloaded and failed templates are pruned daily after `TEMPLATE_RETENTION_DAYS` (default: 7 days). Pending jobs are retained. Run Laravel's scheduler in the deployment so the cleanup takes place. Downloads require a signed URL.

Custom starter detection inspects Composer metadata, the source revision and environment/lock files. It suggests a PHP minor only when the entire minor range satisfies the declared constraints. Otherwise it leaves the selected version unchanged and reports the uncertainty. Detection uses the latest stable release, or the default development branch for packages without a stable release; the generated Composer command resolves its version at installation time.

## Building and deployment

[The build workflow](.github/workflows/build.yml) calls [the reusable image workflow](.github/workflows/build-image.yml). It publishes one image used by both the web process and the Inertia SSR process.

Assets and the SSR bundle are built on the CI runner using the versioned `.env.example` and a temporary application key. The build does not consume production environment secrets. Runtime configuration is supplied by the deployment platform.

The CI workflow checks PHP and frontend regressions, types, formatting, the production build and the web/SSR processes using the same container image.
