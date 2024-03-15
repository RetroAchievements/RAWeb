<p align="center" dir="auto"><a href="https://retroachievements.org" rel="nofollow"><img src="https://raw.githubusercontent.com/RetroAchievements/RAWeb/master/public/assets/images/ra-icon.webp" width="200" alt="RetroAchievements Logo" style="max-width: 100%;"></a></p>

**RAWeb** is [RetroAchievements.org](https://retroachievements.org)'s platform application.
It is a Laravel application ([TALL stack](https://tallstack.dev/)).

## Requirements

- Local web server
- [PHP 8.2](http://php.net/manual/en/)
- [Composer](https://getcomposer.org/) PHP dependency manager
- [MariaDB 10](https://mariadb.com/docs/server/)
- [Node.js 20](https://nodejs.org/)
 
Validated to run on Windows, macOS, and Linux with any of the setup options below (Docker via Laravel Sail, VM with either nginx or Apache, Laravel Valet on macOS).

### **[Docker Compose](https://docs.docker.com/compose/install/)** (Windows, Linux, macOS)

See [Laravel Sail documentation](https://laravel.com/docs/sail).

### **[XAMPP](https://www.apachefriends.org/download.html)** (Windows, Linux, macOS)

Install the XAMPP version packaged with PHP 8.2 to run an Apache web server, MySQL/MariaDB, and PHP on your system.

You might have to enable some extensions in `php.ini` (see the `ext-*` requirements in [composer.json](composer.json)):
```
extension=curl
extension=gmp
extension=mysqli
extension=pdo_mysql
extension=gd
extension=intl
extension=sockets
```

### **[Laravel Valet](https://laravel.com/docs/valet)** (macOS only)

A [local valet driver](LocalValetDriver.php) is provided.

## Upgrade

Refer to the [upgrade guides](docs/upgrade) to upgrade your existing installation.

## Installation

```shell
composer install
```

### Run setup script

```shell
composer setup
```

> **Note**
> In case you want to rely on the shipped `composer.phar` instead of a global installation read all mentions of `composer` within commands as `php composer.phar`.
> I.e. run `php composer.phar setup` if you haven't aliased it.

### Configure

The environment configuration file (`.env`) contains a sensible set of default values.

**Docker/Laravel Sail**

No additional configuration is needed; the configuration automatically detects whether it's running the application via the Laravel Sail application container and adjusts hosts and ports accordingly.

However, you might want to adjust the forwarded container port numbers to your liking (`APP_PORT`, `FORWARD_*`).

Now is a good time to create the containers. Sail forwards commands to Docker Compose:

```shell
sail up
# Daemonize:
sail up -d
```

> **Note**
> Mentions of `sail` commands assume that it has been aliased to the `./sail` executable according to Sail's docs.
> I.e. run `./sail up` if you haven't aliased it.

**XAMPP/Valet** 

Adjust the local environment configuration (`.env`):

- Enter the credentials of your local database instance (`DB_*`)
- Change the application URL (`APP_URL`) - static assets URL (`ASSET_URL`) should be the same as `APP_URL`

> **Note**
> `APP_URL` varies depending on your setup. By default it's configured to use the forwarded application Docker container port.
> E.g. using an Apache vhost or linking a domain via Laravel Valet this should be adjusted accordingly:

```dotenv
APP_URL=https://raweb.test
ASSET_URL=https://raweb.test
```

**Hybrid Docker setup**

When running the application locally (i.e. web server and PHP via XAMPP/Valet) it's possible to use the provided Docker services, too.

Use database and redis services:

```dotenv
DB_PORT=${FORWARD_DB_PORT}
REDIS_PORT=${FORWARD_REDIS_PORT}
```

> **Note**
> Connect with a database client of your choice using the forwarded ports
> or use phpMyAdmin which runs at http://localhost:64080 by default. 

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

### Build frontend assets

```shell
npm install
npm run build
# Using Sail:
sail npm install
sail npm run build
```

### Create filesystem symlinks

```shell
php artisan ra:storage:link --relative
# Using Sail:
sail artisan ra:storage:link --relative
```

### Setup database
 
```shell
php artisan migrate
# Using Sail:
sail artisan migrate
```

Seed your database with additional test data:

```shell
php artisan db:seed
# Using Sail:
sail artisan db:seed
```

### Open the application in your browser.

Depending on the setup you chose the application should run.

- Docker: http://localhost:64000
- XAMPP: depending on Apache vhost configuration
- Laravel Valet: e.g. https://raweb.test - depending on link / parked location and whether you chose to secure it or not 

## Usage

### Developing achievements locally

Add a `host.txt` file next to `RAIntegration.dll` in your local RALibRetro's directory.
The file should contain the URL to your local RAServer instance. Any of the following will work:

- `http://localhost:64000` when running the server via Docker, `composer start` or `artisan serve`.
- `https://raweb.test` (example) when running the server via Valet
- `http://raweb.test` (example) as a configured vhost

## Security Vulnerabilities

Please see our [Security Policy](docs/SECURITY.md).

## Contributing

Please see our [Contribution Guidelines](docs/CONTRIBUTING.md) and [Code of Conduct](docs/CODE_OF_CONDUCT.md).

## License

RAWeb is open-sourced software licensed under the [GPL-3.0 License](LICENSE).

Console Icons by [yspixel.jpn.org](http://yspixel.jpn.org/icon/game/index.htm) and [Tatohead](https://github.com/Tatohead/Console-Iconset).
