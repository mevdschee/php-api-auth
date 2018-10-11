<?php

function generateToken($claims, $time, $ttl, $algorithm, $secret)
{
    $algorithms = array('HS256' => 'sha256', 'HS384' => 'sha384', 'HS512' => 'sha512');
    $header = array();
    $header['typ'] = 'JWT';
    $header['alg'] = $algorithm;
    $token = array();
    $token[0] = rtrim(strtr(base64_encode(json_encode((object) $header)), '+/', '-_'), '=');
    $claims['iat'] = $time;
    $claims['exp'] = $time + $ttl;
    $token[1] = rtrim(strtr(base64_encode(json_encode((object) $claims)), '+/', '-_'), '=');
    if (!isset($algorithms[$algorithm])) {
        return false;
    }
    $hmac = $algorithms[$algorithm];
    $signature = hash_hmac($hmac, "$token[0].$token[1]", $secret, true);
    $token[2] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return implode('.', $token);
}

function redirect($url)
{
    header('Location: ' . $url, true, 302);
}

function handleGet($session, $get)
{
    if (empty($session)) {
        return 'login.html';
    }
    return generateTokenUrl($session);
}

function generateTokenUrl($claims, $redirectUri)
{
    $time = getConfig('time', time());
    $ttl = getConfig('ttl', 5);
    $algorithm = getConfig('algorithm', 'HS256');
    $secret = getConfig('secret', '');
    $token = generateToken($claims, $time, $ttl, $algorithm, $secret);
    $redirects = getConfig('redirects', []);
    if (!in_array($redirectUri, $redirects)) {
        return 'login.html#message=invalid_redirect';
    }
    return $redirectUri . '#access_token=' . $token;
}

function handlePost(&$session, $post, $get)
{
    $GLOBALS['client_id'] = $get['client_id'];
    $GLOBALS['audience'] = $get['audience'];
    $validate = getConfig('validate', function ($username, $password) {return false;});
    $valid = call_user_func($validate, $post['username'], $post['password']);
    if ($valid) {
        $session['username'] = $post['username'];
        return generateTokenUrl($session, $get['redirect_uri']);
    }
    return 'login.html#message=invalid_password';
}

function getConfig($key, $default)
{
    $clientId = $GLOBALS['client_id'];
    $audience = $GLOBALS['audience'];
    $config = $GLOBALS['config'][$clientId][$audience];
    die('<pre>' . var_export($clientId, true) . '</pre>');
    die('<pre>' . var_export($audience, true) . '</pre>');
    die('<pre>' . var_export($config, true) . '</pre>');
    return isset($config[$key]) ? $config[$key] : $default;
}

function main($config)
{
    $GLOBALS['config'] = $config;
    session_start();
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            redirect(handleGet($_SESSION, $_GET));
            break;
        case 'POST':
            redirect(handlePost($_SESSION, $_POST, $_GET));
            break;
    }
}

main([
    'default' => [
        'http://127.0.0.3/api.php' => [
            'secret' => 'test',
            'redirects' => ['http://127.0.0.1/vanilla.html'],
            'validate' => function ($username, $password) {
                return $username == 'admin' && $password == 'admin';
            },
        ],
    ],
]);
