Laravel Horizon RabbitMQ
===============

[![Latest Stable Version](http://img.shields.io/github/release/designmynight/laravel-horizon-rabbitmq.svg)](https://packagist.org/packages/designmynight/laravel-horizon-rabbitmq) [![Total Downloads](http://img.shields.io/packagist/dm/designmynight/laravel-horizon-rabbitmq.svg)](https://packagist.org/packages/designmynight/laravel-horizon-rabbitmq)
[![StyleCI](https://github.styleci.io/repos/147424037/shield?branch=master)](https://github.styleci.io/repos/147424037)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A service provider to add support for a [RabbitMQ](https://github.com/vyuldashev/laravel-queue-rabbitmq) queue driver to [Laravel Horizon](https://github.com/laravel/horizon).

Table of contents
-----------------
* [Installation](#installation)
* [Configuration](#configuration)

Installation
------------

Installation using composer:

```sh
composer require designmynight/laravel-horizon-rabbitmq
```

### Laravel version Compatibility

 Laravel  | Package |
:---------|:--------|
 5.6.x    | 0.1.x   |

And add the service provider in `config/app.php`:

```php
DesignMyNight\Laravel\Horizon\HorizonServiceProvider::class,
```

Configuration
------------

Be sure to set the connection of your supervisor queue to `rabbitmq` in `config/horizon.php`:

```php
'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'rabbitmq',
                'queue' => ['default'],
                'balance' => 'simple',
                'processes' => 8,
                'tries' => 3,
            ],
        ],
```


The service provider will overide the default laravel horizon redis queue and redis job classes with a RabbitMQ implentation in order to trigger the necessary events for horizon to function correctly.