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

function validate($username, $password)
{
    if ($username == 'admin' && $password == 'admin') {
        return true;
    }
    return false;
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
    $url = $redirectUri . '#' . generateToken($claims, $time, $ttl, $algorithm, $secret);
}

function handlePost($session, $post, $get)
{
    $valid = validate($post['username'], $post['password']);
    if ($valid) {
        $_SESSION['username'] = $post['username'];
        return generateTokenUrl($_SESSION, $get['redirect_uri']);
    }
}

function getConfig($key, $default)
{
    global $config;
    return isset($config[$key]) ? $config[$key] : $default;
}

session_start();
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        redirect(handleGet($_SESSION, $_GET));
    case 'POST':
        redirect(handlePost($_SESSION, $_POST, $_GET));
}
