# Alternative local environments

The recommended way to run RAWeb locally is Docker via [Laravel Sail](https://laravel.com/docs/sail), as described in the root-level [README.md](../../README.md). If you'd rather run the application directly on your host machine, this guide covers the alternatives.

If you prefer to keep your host machine untouched entirely, there is also a step-by-step guide for running everything inside an [Ubuntu 24 desktop virtual machine](ubuntu24-desktop-vm.md).

## Requirements

Running RAWeb without Sail means providing these yourself:

- Local web server
- [PHP 8.4](http://php.net/manual/en/)
- [Composer](https://getcomposer.org/) PHP dependency manager
- [MariaDB 10](https://mariadb.com/docs/server/)
- [Node.js 24](https://nodejs.org/)
- [pnpm 10](https://pnpm.io/)

## XAMPP (Windows, Linux, macOS)

Install the [XAMPP](https://www.apachefriends.org/download.html) version packaged with PHP 8.4 to run an Apache web server, MariaDB, and PHP on your system.

You might have to enable some extensions in `php.ini` (see the `ext-*` requirements in [composer.json](../../composer.json)):

```
extension=curl
extension=gmp
extension=pdo_mysql
extension=gd
extension=intl
extension=sockets
```

## Laravel Valet (macOS only)

A [local valet driver](../../LocalValetDriver.php) is provided. See the [Laravel Valet documentation](https://laravel.com/docs/valet).

## Configuration

Follow the installation steps in the README (`composer install`, `composer setup`), then adjust the local environment configuration (`.env`):

- Enter the credentials of your local database instance (`DB_*`)
- Change the application URL (`APP_URL`) - the static assets URL (`ASSET_URL`) should be the same as `APP_URL`

> **Note**
> `APP_URL` varies depending on your setup. By default it's configured to use the forwarded application Docker container port.
> E.g. using an Apache vhost or linking a domain via Laravel Valet this should be adjusted accordingly:

```dotenv
APP_URL=https://raweb.test
ASSET_URL=https://raweb.test
```

Run the remaining README steps (frontend assets, symlinks, database) directly on your host, dropping the `sail` prefix:

```shell
pnpm install
pnpm build
php artisan ra:storage:link --relative
php artisan migrate
php artisan db:seed
```

The application URL depends on your setup:

- XAMPP: depending on Apache vhost configuration
- Laravel Valet: e.g. https://raweb.test - depending on link / parked location and whether you chose to secure it or not

## Hybrid Docker setup

When running the application locally (i.e. web server and PHP via XAMPP/Valet) it's possible to use the provided Docker services, too.

Use database and redis services:

```dotenv
DB_PORT=${FORWARD_DB_PORT}
REDIS_PORT=${FORWARD_REDIS_PORT}
```

> **Note**
> Connect with a database client of your choice using the forwarded ports.

Use mailpit as SMTP server for local mails testing:

```dotenv
MAIL_MAILER=smtp
```

> **Note**
> Runs at http://localhost:64050 by default.

Use minio as an AWS S3 drop-in replacement:

```dotenv
AWS_MINIO=true
```

> **Note**
> In order to use S3 features you'll have to create a `local` bucket manually first.
> Runs at http://localhost:64041/buckets/add-bucket by default.
