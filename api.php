<?php

// uncomment the lines below when running in stand-alone mode:

// for token+session based authentication (see "login_token.html" + "login_token.php"):

// require 'auth.php';
// $auth = PHP_API_AUTH(array(
// 	'secret'=>'someVeryLongPassPhrase',
// ));
// $auth->executeCommand();
// if (empty($_SESSION['user'])) exit(403);

// for form+session based authentication (see "login.html"):

// require 'auth.php';
// $auth = PHP_API_AUTH(array(
// 	'authenticator'=>function($user,$pass){ if ($user=='admin' && $pass=='admin') $_SESSION['user']=$user; }
// ));
// $auth->executeCommand();
// if (empty($_SESSION['user'])) exit(403);

// include your api code here:
echo 'Access granted!';
