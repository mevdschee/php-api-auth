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

You should change the "secret" and set the "redirects" to the URL of the client (for source code see below) that uses the JWT token. The validate function should be changed to hold a stronger password validation.

The default "clientId" is "default" and is the first key in the config. The default "audience" is "api.php" and this is defined as the second key in the config. 

Example client:

https://github.com/mevdschee/php-crud-api/blob/master/examples/clients/auth.php/vanilla.html

## Flow

This is the authentication flow:

- Browser will load the client.
- The client will redirect the user to the "auth.php" 
- If the user does not have a valid session (cookie) then the "login.html" page is served.
- The user is redirected back to the client with the token in the URL
- The client uses the token to call the api.
- The API will exchange the token for a session (cookie)

Now the client can do API calls until the session times out.

I suggest that you first get PHP-CRUD-API working with [Auth0](https://auth0.com/) before you start 
implementing your own JWT based authentication provider using this repository.
