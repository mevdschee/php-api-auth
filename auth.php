<?php

function generateToken($subject, $audience, $issuer, $time, $ttl, $algorithm, $secret)
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

function handleGet($config, $session)
{
    if (empty($session)) {
        serve('login.html');
    } else {
        redirect(generateTokenUrl($config, $session));
    }
}

function getSecure()
{
    return isset($_SERVER['HTTPS']) && !in_array(strtolower($_SERVER['HTTPS']), array('off', 'no'));
}

function getFullUrl()
{
    return (getSecure() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function generateTokenUrl($config, $session)
{
    $time = getConfig($config, 'time', time());
    $ttl = getConfig($config, 'ttl', 5);
    $algorithm = getConfig($config, 'algorithm', 'HS256');
    $secret = getConfig($config, 'secret', '');
    $subject = $session['username'];
    $audience = $config['audience'];
    $issuer = getFullUrl();
    $token = generateToken($subject, $audience, $issuer, $time, $ttl, $algorithm, $secret);
    $redirectUri = getConfig($config, 'redirectUri', '');
    return $redirectUri . '#access_token=' . $token;
}

function handlePost($config, &$session, $username, $password)
{
    $validate = getConfig($config, 'validate', function ($username, $password) {return false;});
    $valid = call_user_func($validate, $username, $password);
    if (!$valid) {
        serve('login.html');
    } else {
        session_regenerate_id();
        $session['username'] = $username;
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
    $clientId = isset($_GET['client_id']) ? $_GET['client_id'] : 'default';
    $audience = isset($_GET['audience']) ? $_GET['audience'] : 'default';
    $redirectUri = isset($_GET['redirect_uri']) ? $_GET['redirect_uri'] : '';
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
                    handleGet($config, $_SESSION);
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
