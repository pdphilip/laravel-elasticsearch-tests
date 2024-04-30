# Laravel-Elasticsearch Package Tests (PEST)

This is a test suite for the [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) package. Feel free to use it as a reference for your own tests or as a guide to understanding the package's features.

PRs are welcome!

## Install Laravel

```bash
composer create-project laravel/laravel html "^8"
composer create-project laravel/laravel html "^9"
composer create-project laravel/laravel html "^10"
composer create-project laravel/laravel html "^11"

```

## Config


```bash
cd html
```
#### Require deps:
```bash
composer require pestphp/pest
composer require pestphp/pest-plugin-laravel
composer require rkondratuk/geo-math-php 
composer require pdphilip/laravel-elasticsearch
```

#### Require Mongo (optional)
Laravel 10+
```bash
composer require mongodb/laravel-mongodb
```

### Add tests and config
```bash
rm -rf tests
rm phpunit.xml
git clone https://github.com/pdphilip/laravel-elasticsearch-tests.git
mv laravel-elasticsearch-tests/phpunit.xml phpunit.xml
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

Add database connections to `config/database.php`

```php
'elasticsearch' => [
    'driver'       => 'elasticsearch',
    'auth_type'    => env('ES_AUTH_TYPE', 'http'), //http, cloud or api
    'hosts'        => explode(',', env('ES_HOSTS', 'http://localhost:9200')),
    'username'     => env('ES_USERNAME', ''),
    'password'     => env('ES_PASSWORD', ''),
    'cloud_id'     => env('ES_CLOUD_ID', ''),
    'api_id'       => env('ES_API_ID', ''),
    'api_key'      => env('ES_API_KEY', ''),
    'ssl_cert'     => env('ES_SSL_CA', ''),
    'ssl'          => [
        'cert'          => env('ES_SSL_CERT', ''),
        'cert_password' => env('ES_SSL_CERT_PASSWORD', ''),
        'key'           => env('ES_SSL_KEY', ''),
        'key_password'  => env('ES_SSL_KEY_PASSWORD', ''),
    ],
    'index_prefix' => env('ES_INDEX_PREFIX', false),
    'options'      => [
        'allow_id_sort'    => env('ES_OPT_ID_SORTABLE', false),
        'ssl_verification' => env('ES_OPT_VERIFY_SSL', true),
        'retires'          => env('ES_OPT_RETRIES', null),
        'meta_header'      => env('ES_OPT_META_HEADERS', true),
    ],
    'query_log'    => [
        'index'      => false, //Or provide a name for the logging index ex: 'laravel_query_logs'
        'error_only' => true, //If false, then all queries are logged if the query_log index is set
    ],
],
'mongodb'       => [
    'driver'   => 'mongodb',
    'host'     => env('MONGO_DB_HOST', 'localhost'),
    'port'     => env('MONGO_DB_PORT', 27017),
    'database' => env('MONGO_DB_DATABASE'),
    'username' => env('MONGO_DB_USERNAME'),
    'password' => env('MONGO_DB_PASSWORD'),
    'options'  => [
        'database' => 'admin', // sets the authentication database required by mongo 3
    ],
],
```

Add packages

```php
//config/app.php
'providers' => [
...
...
PDPhilip\Elasticsearch\ElasticServiceProvider::class,
...
```

For Laravel 11:
```php
//bootstrap/providers.php
<?php
return [
    App\Providers\AppServiceProvider::class,
    PDPhilip\Elasticsearch\ElasticServiceProvider::class,
];

```


### Run tests

```bash

php artisan test

php artisan test --group=eloquent
php artisan test --group=schema
php artisan test --group=relationships


```
