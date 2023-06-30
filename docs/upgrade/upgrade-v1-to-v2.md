# Upgrade from v1.x to v2.0

1. Upgrade setup

```shell
composer setup
```

Make sure to update your local [`.env`](.env) file by comparing it to [`.env.example`](.env.example).

2. Upgrade schema

```shell
php artisan migrate
```
