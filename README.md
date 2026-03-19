# DirectAdmin Storage Plugin for MyAdmin

[![Tests](https://github.com/detain/myadmin-directadmin-storage/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-directadmin-storage/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/detain/myadmin-directadmin-storage/branch/master/graph/badge.svg)](https://codecov.io/gh/detain/myadmin-directadmin-storage)

A MyAdmin plugin that provides backup storage management through the DirectAdmin control panel API. This package handles the full lifecycle of DirectAdmin storage accounts including provisioning, suspension, reactivation, and termination.

## Features

- **Account Provisioning** - Automatically creates DirectAdmin user or reseller accounts for backup storage
- **Suspension / Reactivation** - Suspends and unsuspends accounts via the DirectAdmin API
- **Termination** - Cleanly removes accounts when services are cancelled
- **IP Management** - Supports changing IP addresses on existing accounts
- **Hook-based Architecture** - Integrates with MyAdmin's Symfony EventDispatcher-based plugin system
- **HTTPSocket Client** - Includes a curl-based HTTP client tailored for DirectAdmin API communication

## Requirements

- PHP >= 5.0
- ext-curl
- Symfony EventDispatcher ^5.0

## Installation

```bash
composer require detain/myadmin-directadmin-storage
```

## Usage

The plugin registers itself with MyAdmin's event system. Hook registration is handled automatically:

```php
use Detain\MyAdminDirectAdminStorage\Plugin;

// Get all event hooks this plugin provides
$hooks = Plugin::getHooks();

// Register hooks with the event dispatcher
foreach ($hooks as $event => $callable) {
    $dispatcher->addListener($event, $callable);
}
```

The `HTTPSocket` class can also be used standalone for DirectAdmin API calls:

```php
use Detain\MyAdminDirectAdminStorage\HTTPSocket;

$sock = new HTTPSocket();
$sock->connect('ssl://your-server.com', 2222);
$sock->set_login('admin', 'your-api-key');
$sock->query('/CMD_API_SHOW_ALL_USERS');
$users = $sock->fetch_parsed_body();
```

## Running Tests

```bash
composer install
vendor/bin/phpunit
```

To generate a coverage report:

```bash
vendor/bin/phpunit --coverage-text
```

## API Reference

For DirectAdmin API documentation, see:
- https://www.directadmin.com/api.php
- HTTPSocket originally from http://files.directadmin.com/services/all/httpsocket/httpsocket.php

## License

This package is licensed under the [LGPL-2.1](LICENSE).
