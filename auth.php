<?php

function generateToken($subject, $user, $audience, $issuer, $time, $ttl, $algorithm, $secret)
{
    $algorithms = array(
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
        'RS256' => 'sha256',
        'RS384' => 'sha384',
        'RS512' => 'sha512',
    );
    $header = array();
    $header['typ'] = 'JWT';
    $header['alg'] = $algorithm;
    $token = array();
    $token[0] = rtrim(strtr(base64_encode(json_encode((object) $header)), '+/', '-_'), '=');
    $claims['sub'] = $subject;
    $claims['email'] = $user->email; // added email to claims
    $claims['role'] = $user->role;  // added role to claims
    $claims['aud'] = $audience;
    $claims['iss'] = $issuer;
    $claims['iat'] = $time;
    $claims['exp'] = $time + $ttl;
    $token[1] = rtrim(strtr(base64_encode(json_encode((object) $claims)), '+/', '-_'), '=');
    if (!isset($algorithms[$algorithm])) {
        return false;
    }
    $hmac = $algorithms[$algorithm];
    $data = "$token[0].$token[1]";
    switch ($algorithm[0]) {
        case 'H':
            $signature = hash_hmac($hmac, $data, $secret, true);
            break;
        case 'R':
            $signature = (openssl_sign($data, $signature, $secret, $hmac) ? $signature : '');
            break;
    }
    $token[2] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return implode('.', $token);
}

function redirect($url)
{
    header('Location: ' . $url, true, 302);
}

function serve($file)
{
    echo file_get_contents($file);
}

function handleGet($config, $session, $logout, $error)
{
    if ($error) {
        redirect($config['loginError']); // redirect (instead of serving an html page) to login application with error parameter set to=1 in case of failed login (used to display error message)
    }
    if (empty($session) || $logout ) {
        redirect($config['login']); // redirect (instead of serving an html page) to login application in case of missing token
    } else {
        redirect(generateTokenUrl($config, $session));
    }
}

function getSecure()
{
    return isset($_SERVER['HTTPS']) && !in_array(strtolower($_SERVER['HTTPS']), array('off', 'no'));
}

function getHost() // get host name, used to set the instance server name as issuer
{
    return $_SERVER['HTTP_HOST']; 
}

function getFullUrl()
{
    return (getSecure() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function generateTokenUrl($config, $session)
{
    $time = getConfig($config, 'time', time());
    $ttl = getConfig($config, 'ttl', 3600);
    $algorithm = getConfig($config, 'algorithm', 'HS256');
    $secret = getConfig($config, 'secret', '');
    $subject = $session['user']->uuid;
    $user = $session['user'];
    $audience = $config['audience'];
    $issuer = getHost();
    $token = generateToken($subject, $user, $audience, $issuer, $time, $ttl, $algorithm, $secret);
    $redirectUri = getConfig($config, 'redirectUri', '');
    return $redirectUri . '#access_token=' . $token;
}

function handlePost($config, &$session, $username, $password)
{
    $validate = getConfig($config, 'validate', function ($username, $password) {
        return false;
    });
    $valid = call_user_func($validate, $username, $password);
    if (!$valid['success']) {
        redirect($config['loginError']); // added redirect to login page with error parameter set to=1 in case of failed login (used to display error message) 
    } else {
        session_regenerate_id();
        $session['user'] = $valid['payload'];
        redirect(generateTokenUrl($config, $session));
    }
}

function getConfigArray($config, $key, $default)
{
    return array_filter(array_map('trim', explode(',', getConfig($config, $key, $default))));
}

function getConfig($config, $key, $default)
{
    return isset($config[$key]) ? $config[$key] : $default;
}

function main($config)
{
    session_start();
    $logout = isset($_GET['logout']) ? $_GET['logout'] : '0'; // added logout parameter as logout flag to unset the php session 
    $error = isset($_GET['error']) ? $_GET['error'] : '0'; // added error parameter as error flag to enter in error state
    $clientId = isset($_GET['client_id']) ? $_GET['client_id'] : 'default';
    $audience = isset($_GET['audience']) ? $_GET['audience'] : 'default';
    $redirectUri = isset($_GET['redirect_uri']) ? $_GET['redirect_uri'] : '';
    if ($logout) {
        session_unset(); // unset the php session
    }
    if (isset($config[$clientId][$audience])) {
        $config = $config[$clientId][$audience];
        $config['clientId'] = $clientId;
        $config['audience'] = $audience;
        $redirects = getConfigArray($config, 'redirects', '');
        if (in_array($redirectUri, $redirects)) {
            $config['redirectUri'] = $redirectUri;
        }
        if (isset($config['redirectUri'])) {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    handleGet($config, $_SESSION, $logout, $error); //added logout and error parameters
                    break;
                case 'POST':
                    handlePost($config, $_SESSION, $_POST['username'], $_POST['password']);
                    break;
            }
        } else {
            echo "Could not find valid redirect URI: $redirectUri";
        }
    } else {
        echo "Could not find configuration: $clientId / $audience";
    }
}

//implementation examples
main([
    'default' => [ // client_id
        'example1' => [ // audience1
            'secret' => 'someVeryLongPassPhraseChangeMe',
            'login' => 'https://login.example1.com/', // redirection to login application in case of missing token
            'loginError' => 'https://login.example1.com?error=1', // redirection to login application with error parameter set to=1 in case of failed login (used to display error message)
            'redirects' => 'https://app.example1.com', // redirects to application in case of successful login
            'validate' => function ($username, $password) { // validation of username and password, this example uses api.php and dbauth middleware
                $url = "api.example1.com/authapi.php/login";  // this instance of api.php is configured to use dbauth middleware
                $data = array('username' => $username, 'password' => $password);
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    )
                );
                $context  = stream_context_create($options);
                $resp = file_get_contents($url, false, $context); // retreived user data from authapi.php
                preg_match('/([0-9])\d+/', $http_response_header[0], $matches); // response code from authapi.php
                $responsecode = intval($matches[0]);
                return array('success' => ($responsecode == 200), 'payload' => $resp); // return success status and user data, user data will be used to generate the jwt token
            },
        ],
        'example2' => [ // audience2
            'secret' => 'someVeryLongPassPhraseChangeMe',
            'login' => 'https://login.example2.com/', // redirection to login application in case of missing token
            'loginError' => 'https://login.example2.com?error=1', // redirection to login application with error parameter set to=1 in case of failed login (used to display error message)
            'redirects' => 'https://app.example2.com', // redirects to application in case of successful login
            'validate' => function ($username, $password) { // validation of static username and password, with fixed payload
                $valid = $username == 'company_admin_account' && $password == 'password'; 
                $response = array(
                    'success' => ($valid == true),
                    'payload' => json_decode('{
                        "uuid": "admin@example2.com",
                        "email": "admin@example2.com",
                        "role": "admin" 
                    }')
                );
                return $response;
            }
        ]
    ],
    'development_team_example' => [ // client_id
        'developer1' => [ // audience1 - developer1
            'secret' => 'someVeryLongPassPhraseChangeMe',
            'login' => 'https://login.devel.example1.com/', // redirection to login application in case of missing token
            'loginError' => 'https://login.devel.example1.com?error=1', // redirection to login application with error parameter set to=1 in case of failed login (used to display error message)
            'redirects' => 'http://localhost:8080/', // redirects to localhost development environment
            'validate' => function ($username, $password) { // validation of static username and password, with fixed payload
                $valid = $username == 'developer1' && $password == 'password'; 
                $response = array(
                    'success' => ($valid == true),
                    'payload' => json_decode('{
                        "uuid": "developer1",
                        "email": "devel1@developerteam.com",
                        "role": "developer" 
                    }')
                );
                return $response;
            }
        ], 
        'developer2' => [ // audience2 - developer2
            'secret' => 'someVeryLongPassPhraseChangeMe',
            'login' => 'https://login.devel.example2.com/', // redirection to login application in case of missing token
            'loginError' => 'https://login.devel.example2.com?error=1', // redirection to login application with error parameter set to=1 in case of failed login (used to display error message)
            'redirects' => 'http://localhost:8081/', // redirects to application in case of successful login
            'validate' => function ($username, $password) { // validation of static username and password, with fixed payload
                $valid = $username == 'developer2' && $password == 'password'; 
                $response = array(
                    'success' => ($valid == true),
                    'payload' => json_decode('{
                        "uuid": "developer2",
                        "email": "devel2@developerteam.com",
                        "role": "developer" 
                    }')
                );
                return $response;
            }
        ]
    ]
]);
