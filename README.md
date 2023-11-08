### Instructions

Follow the instructions to get the project running (starts in project root)

1. copy `.env.example` as `.env` and fill in your values
1. `cd docker`
1. `docker compose --env-file ../.env up`
1. `docker exec -it logio-product-cs-app bash`
1. (in container bash) `composer install`
1. Go to `localhost::8000` in browser, log in as `root` with password in line with `.env` `DB_PASSWORD` and create dabases in line with `.env` `SQL_DATABASE_NAME` and `ES_DATABASE_NAME`, respectively.
1. (in container bash) `php create_tables.php`
1. Go to `localhost::3000` in browser.