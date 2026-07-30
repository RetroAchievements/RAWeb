<p align="center" dir="auto"><a href="https://retroachievements.org" rel="nofollow"><img src="https://raw.githubusercontent.com/RetroAchievements/RAWeb/master/public/assets/images/ra-icon.webp" width="200" alt="RetroAchievements Logo" style="max-width: 100%;"></a></p>

**RAWeb** is [RetroAchievements.org](https://retroachievements.org)'s platform application.
It is a Laravel application using [Inertia.js](https://inertiajs.com/) with React, TypeScript, and server-side rendering (SSR). The frontend is actively migrating from Blade/Livewire to React. Back-office administration is built with [Filament](https://filamentphp.com/).

## Requirements

- [Docker](https://docs.docker.com/get-docker/) (Windows, macOS, Linux)
- [Composer](https://getcomposer.org/) PHP dependency manager

RAWeb runs in Docker via [Laravel Sail](https://laravel.com/docs/sail), which provides PHP, MariaDB, Node.js, and all other services in containers. Prefer to run the application directly on your host machine instead? See the [alternative environments guide](docs/guides/alternative-environments.md).

## Installation

```shell
composer install
```

### Run the setup script

```shell
composer setup
```

Among other things, this creates your environment configuration file (`.env`) by copying `.env.example`, which contains a sensible set of default values. The configuration automatically detects whether it's running via the Laravel Sail application container and adjusts hosts and ports accordingly. You might want to adjust the forwarded container port numbers to your liking (`APP_PORT`, `FORWARD_*`).

### Start the containers

Sail forwards commands to Docker Compose:

```shell
./vendor/bin/sail up

# Daemonize:
./vendor/bin/sail up -d
```

> **Note**
> Subsequent mentions of `sail` commands assume it has been aliased to `./vendor/bin/sail` according to [Sail's docs](https://laravel.com/docs/sail#configuring-a-shell-alias).
> ie: run `./vendor/bin/sail pnpm dev` if you haven't aliased it.

### Build frontend assets

```shell
sail pnpm install
sail pnpm build
```

### Create filesystem symlinks

```shell
sail artisan ra:storage:link --relative
```

### Setup database

```shell
sail artisan migrate
```

Seed your database with additional test data:

```shell
sail artisan db:seed
```

### Open the application in your browser

The application runs at http://localhost:64000 by default.

## Development workflow

For local development with hot module replacement:

```shell
sail pnpm dev
```

Before submitting a pull request, verify your changes pass all checks:

```shell
# Frontend
sail pnpm verify  # Runs linting, TypeScript checks, and Vitest tests

# Backend
sail composer fix                            # Fix code style issues
sail composer analyse                        # Run PHPStan static analysis
sail composer test -- --filter=TestFileName  # Run an individual test suite
sail composer test -- --parallel             # Run all back-end tests in parallel
```

To run specific frontend tests:

```shell
sail pnpm test:run SomeComponent  # Run tests matching "SomeComponent"
```

## Bundled services

Sail also provides supporting services out of the box:

- Connect a database client of your choice to MariaDB using the forwarded port (64010 by default).
- Mailpit catches local outbound mail and runs at http://localhost:64050.
- MinIO acts as an AWS S3 drop-in replacement. Set `AWS_MINIO=true` in `.env` and create a `local` bucket manually at http://localhost:64041/buckets/add-bucket.

## Usage

### Developing achievements locally

Add a `host.txt` file next to `RAIntegration.dll` in your local RALibRetro's directory.
The file should contain the URL to your local RAServer instance:

- `http://localhost:64000` when running the server via Docker, `composer start` or `artisan serve`.

## Security Vulnerabilities

Please see our [Security Policy](docs/SECURITY.md).

## Contributing

Please see our [Contribution Guidelines](docs/CONTRIBUTING.md), [Translations Guide](docs/TRANSLATIONS.md) and [Code of Conduct](docs/CODE_OF_CONDUCT.md).

## License

RAWeb is open-sourced software licensed under the [GPL-3.0 License](LICENSE).

Console Icons by [yspixel.jpn.org](http://yspixel.jpn.org/icon/game/index.htm) and [Tatohead](https://github.com/Tatohead/Console-Iconset).
