# RAWeb

RAWeb is RetroAchievements.org's site and server backend.
It is a "flat" PHP project; individual php files within the `public` folder handle server requests.

## Requirements

- Local web server
- [PHP 7.4](http://php.net/manual/en/)
- [Composer](https://getcomposer.org/) PHP dependency manager
- [MySQL 8](https://dev.mysql.com/doc/refman/8.0/en/)
- [Node.js 12](https://nodejs.org/)

**[XAMPP](https://www.apachefriends.org/download.html)** provides an easy way to run an Apache web server, MySQL/MariaDB, and PHP on your system.

- Note that XAMPP comes packaged with PHP 7.2. You will need to update to PHP 7.4 or errors will occur when you open the application. To update PHP, do the following:

1.  Download the update from the link above for your platform.
2.  Rename the `/php` folder in your `xampp` path to something else, like `/php_7_2`.
3.  Extract the downloaded update to a new `/php` folder in your `xampp` path.
4.  Copy the contents of your old `php_*version_number*` folder to the new `/php` folder, but don't overwrite files. This will place important files like `php.ini` into your updated PHP folder.
5.  Verify that your PHP version has updated! Start up your Apache/MySQL servers in XAMPP and click on the `Admin` button for MySQL to see the PHP version on the right side of the phpMyAdmin homepage.

Alternatively, **[Docker Compose](https://docs.docker.com/compose/install/)** can be used to run MySQL and PHPMyAdmin. See `docker-compose.yml` for details.
Follow the `.env` file instructions below to configure your environment, then run:

    $ docker-compose up -d

You might have to enable some extensions in `php.ini`:
```
extension=curl
extension=mysqli
extension=pdo_mysql
```

## Setup

After installing all required software the site needs to be configured for your local needs.
The environment configuration file (`.env`) contains a sensible set of default values.

1. Copy `.env.example` to `.env`.

    Linux/MacOS:

        $ cp .env.example .env

    Windows:

        $ copy .env.example .env

2. Adjust the contents of `.env` to match your local setup:

    - Enter the credentials of you local database instance (`DB_*`).
    - URL to where `index.php` can be found (`APP_URL`).
    - URL to where static assets, like images, are stored (`APP_STATIC_URL`). Most likely the same as `APP_URL` in a local environment.

3. Add image assets:

    [Download the media archive](https://retroachievements.org/bin/ra-web-v1-media.zip) and add its files to the respective folders in `public`.

4. Install dependencies:

    Use composer provided in this repository...

        $ php composer.phar install

    ...or your globally installed instance.

        $ composer install

5. Build the dummy database using the SQL commands in the `/database` folder. You can use the MySQL CLI (recommended) or the phpMyAdmin GUI.

6. Open the application in your browser.

## Contributing

See the [Contribution Guidelines](CONTRIBUTING.md).

## License

RAWeb is open-sourced software licensed under the [GPL-3.0 License](LICENSE).
