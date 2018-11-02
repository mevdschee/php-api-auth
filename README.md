# PHP-API-AUTH

Single file PHP that can serve as a JWT based authentication provider 
to the [PHP-CRUD-API](https://github.com/mevdschee/php-crud-api) project.

NB: Are you looking for v1? It is here: https://github.com/mevdschee/php-api-auth/tree/v1

## Requirements

  - PHP 7 or higher

## Installation

Upload "`auth.php`" and edit the configuration block at the bottom of the file:

        main([
            'default' => [
                'api.php' => [
                    'secret' => 'someVeryLongPassPhraseChangeMe',
                    'redirects' => 'http://localhost/vanilla.html',
                    'validate' => function ($username, $password) {
                        return $username == 'admin' && $password == 'admin';
                    },
                ],
            ],
        ]);

You should change the "secret" and set the "redirects" to the client ([from the PHP-CRUD_API examples](https://github.com/mevdschee/php-crud-api/blob/master/examples/clients/auth.php/vanilla.html)) that uses the JWT token. The validate function should be changed to hold a stronger password validation.
