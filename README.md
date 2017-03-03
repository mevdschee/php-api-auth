# PHP-API-AUTH

Single file PHP script that adds authentication to a [PHP-CRUD-API](https://github.com/mevdschee/php-crud-api) project.

## Requirements

  - PHP 5.3 or higher

## Simple username + password

On API server

- login.html is loaded
- sends username + password via POST to "api.php/"
- api.php (POST on "/" gets hijacked by auth.php) is loaded
- sends back csrf token + http-only session cookie
- call API as: api.php?csrf=\[csrf token] (session cookie is sent automatically)
- (when using Angular2 or Vue2 the CSRF token is sent automatically)

## With authentication server

On authentication server

- login_token.html is loaded
- sends username + password via POST to "login_token.php"
- login_token.php is loaded
- sends token via POST to "api.php/"

On API server

- api.php (POST on "/" gets hijacked by auth.php) is loaded
- sends back csrf token + http-only session cookie
- call API as: api.php?csrf=\[csrf token] (session cookie is sent automatically)
- (when using Angular2 or Vue2 the CSRF token is sent automatically)
