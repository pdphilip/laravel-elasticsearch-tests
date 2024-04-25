# Laravel X Elasticsearch plugin Tests (PEST)

Docker environment [https://github.com/pdphilip/docker-LAMP-MR](https://github.com/pdphilip/docker-LAMP-MR)

## Install Laravel

```bash
composer create-project laravel/laravel laravel_tests "^8"
composer create-project laravel/laravel laravel_tests "^9"
composer create-project laravel/laravel laravel_tests "^10"
composer create-project laravel/laravel laravel_tests "^11"

```

## Config

#### Require deps:
```bash
cd laravel_tests
composer require pestphp/pest-plugin-laravel rkondratuk/geo-math-php
```

#### Require Elasticsearch
```bash
composer require pdphilip/elasticsearch
```

#### Require Mongo (optional)
Laravel 8 and 9
```bash
composer require jenssegers/mongodb
```
Laravel 10+
```bash
composer require mongodb/laravel-mongodb
```

### Add tests and config
```bash
rm -rf tests
rm phpunit.xml
tm config/database.php
git clone https://github.com/pdphilip/laravel-elasticsearch-tests.git
mv laravel-elasticsearch-tests/phpunit.xml phpunit.xml
mv laravel-elasticsearch-tests/database.php config/database.php
mv laravel-elasticsearch-tests/tests tests
rm -rf laravel-elasticsearch-tests
```



### Edit .env file to add database credentials (mongodb optional)

```dotenv

DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MONGO_DB_HOST=
MONGO_DB_PORT=
MONGO_DB_DATABASE=
MONGO_DB_USERNAME=
MONGO_DB_PASSWORD=

ES_AUTH_TYPE=http
ES_HOSTS="http://elasticsearch:9200"
ES_USERNAME=
ES_PASSWORD=
ES_CLOUD_ID=
ES_API_ID=
ES_API_KEY=
ES_SSL_CA=
ES_INDEX_PREFIX=laravel_es_test

```


### Run migrations

```bash

php artisan migrate

```

### Run tests

```bash

php artisan test

php artisan test --group=eloquent
php artisan test --group=schema
php artisan test --group=relationships


```
