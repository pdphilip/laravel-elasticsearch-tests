# Laravel X Elasticsearch plugin Tests (PEST)

## Pre-requisites

- Laravel 10
- Elasticsearch 8.10
- MongoDB 5+

Docker environment [https://github.com/pdphilip/docker-LAMP-MR](https://github.com/pdphilip/docker-LAMP-MR)

```
- "laravel/framework": "^10.10"
```

### Clone

```bash
git clone https://github.com/pdphilip/laravel-elasticsearch-tests.git my_tests
cd my_tests
rm -rf .git
```
### Select laravel version
Laravel 8
```bash
cp 8.composer.json composer.json 
```
Laravel 9
```bash
cp 7.composer.json composer.json 
```
Laravel 10
```bash
cp 10.composer.json composer.json 
```
Laravel 11
```bash
cp 11.composer.json composer.json 
```

### Install

```bash
composer install
cp .env.example .env
php artisan key:generate
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

### Install Elasticsearch

```bash
composer require pdphilip/elasticsearch
```
It will pull the current 3.x version for your laravel version

### Install Mongo (optional)
Laravel 8 and 9
```bash
composer require jenssegers/mongodb
```
Laravel 10+
```bash
composer require mongodb/laravel-mongodb
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
