# Upgrade from v2.x to v3.0

1. Upgrade setup

```shell
composer setup
```

2. Upgrade schema

```shell
php artisan migrate
```

This may take some time for tables with a lot of data.

> **Note**
> Running `composer mfs` (alias for `php artisan migrate:refresh --seed`) will remove all columns
> and data that might have been migrate from then on.
> The V1 base tables however remain protected and will not be dropped/truncated.
> It's advised to not use `composer mfs` if you don't want to lose any data.
 
To roll back the migration (e.g. to switch back to a pre-v3 branch):

```shell
php artisan migrate:rollback
```

3. Migrate data

Run the sync command below to populate the new columns and tables: 

```shell
# TODO
php artisan ra:sync:...
```
