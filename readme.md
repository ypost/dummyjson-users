# DummyJSON Users

It is a client library for retrieving and creating users using the DummyJSON API: https://dummyjson.com/docs/users

## Requirements

- PHP 8.4+
- PSR-18 compatible HTTP client, e.g. `GuzzleHttp\Client`
- PSR-17 compatible request and stream factories, e.g. `GuzzleHttp\Psr7\HttpFactory`

The package does not provide a specific HTTP client implementation.
If you want to use Guzzle as in the examples below, install it separately:
```bash
composer require guzzlehttp/guzzle guzzlehttp/psr7
```

## Installation
Project is not on Packagist so you need to add its repository 
```
composer config repositories.dummyjson-users vcs https://github.com/ypost/dummyjson-users

composer require ypost/dummyjson-users:dev-main
```

## Usage examples
### Initialize the service
```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use YPost\DummyJsonUsers\UsersService;

$client = new Client(['timeout' => 10.0]);
$httpFactory = new HttpFactory();
$service = new UsersService($client, $httpFactory, $httpFactory);
```

### Fetch users list
Get first 5 users
```php
$users = $service->getUsers(5);
```

To get the third page with 10 users per page: 
```php
$users = $service->getUsersPage(3, 10);
// or
$users = $service->getUsers(10, 20);
```

### Fetch a user
Fetches a user with ID 1
```php
$user = $service->getUser(1);
```

### Add new user
Create a user and get its ID
```php
$id = $service->addUser('John', 'Doe', 'john@example.com');
```

## Error handling
All package exceptions implement `UsersException`, so all errors can be caught with a single catch
```php
use YPost\DummyJsonUsers\Exception\UsersException;

try {
    $user = $service->getUser(1);
} catch (UsersException $e) {
    // Handler
}
```

More specific exceptions:

- `UserNotFoundException` - user not found (404).
- `UsersInvalidArgumentException` - invalid method arguments
- `RemoteApiException` - remote API or HTTP error (exposes HTTP status code and response body)
- `InvalidApiResponseException` - remote API returned invalid data

## Retries
`getUser` and `getUsers` requests are automatically retried on responses with these HTTP status codes: 429, 500, 502, 503 and 504, as well as on PSR-18 transport errors.

`addUser` requests are not retried because the operation is not idempotent and may create duplicates.

## Development

1. `git clone git@github.com:ypost/dummyjson-users.git` to clone the repository

2. `cd dummyjson-users` to get into project directory

3. `docker compose up -d` to run containers

4. `docker compose exec php bash` to log into the container

5. `composer install` to install dependencies

6. `composer check` to run all local tests (phpstan and phpunit)

7. `composer test:integration` to run the integration test using DummyJSON API

8. `exit` followed by `docker compose down` to shut down the container
