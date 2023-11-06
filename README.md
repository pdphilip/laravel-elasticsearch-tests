# Laravel X Elasticsearch plugin Tests (PEST)

## Pre-requisites

- Laravel 10
- Elasticsearch 8.10
- MongoDB 5+

Docker environment [https://github.com/pdphilip/docker-LAMP-MR](https://github.com/pdphilip/docker-LAMP-MR)

```
- "laravel/framework": "^10.10"
- "pdphilip/elasticsearch": "^2.10"
- "mongodb/laravel-mongodb": "^4.0"
```

## Installation

```bash
git checkout pdphilip/laravel-elasticsearch-tests my_tests
cd my_tests
composer install

cp .env.example .env
php artisan key:generate

```

### Edit .env file to add database credentials

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
ES_SSL_CERT=
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
