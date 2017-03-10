<?php

// uncomment the lines below when running in stand-alone mode:

// for token+session based authentication (see "login_token.html" + "login_token.php"):

// require 'auth.php';
// $auth = new PHP_API_AUTH(array(
// 	'secret'=>'someVeryLongPassPhraseChangeMe',
// ));
// if ($auth->executeCommand()) exit(0);
// if (empty($_SESSION['user']) || !$auth->hasValidCsrfToken()) {
//      header('HTTP/1.0 401 Unauthorized');
//      exit(0);
// }

// for form+session based authentication (see "login.html"):

// require 'auth.php';
// $auth = new PHP_API_AUTH(array(
// 	'authenticator'=>function($user,$pass){ if ($user=='admin' && $pass=='admin') $_SESSION['user']=$user; }
// ));
// if ($auth->executeCommand()) exit(0);
// if (empty($_SESSION['user']) || !$auth->hasValidCsrfToken()) {
//	header('HTTP/1.0 401 Unauthorized');
//	exit(0);
// }

// for apiKey using key in query, in testing with the swagger editor
//require 'auth.php';
//$auth = new PHP_API_AUTH(array(
//        'origin' => 'http://editor.swagger.io',
//        'security'=>array(
//                'CrudApiKeyQuery' => array(
//                        'type' => 'apiKey',
//                        'name' => 'apikey',
//                        'in' => 'query',
//                        'keys' => array(
//                                'placekeyshereforaccess'
//                        )
//               )
//       )
//));
//if ($auth->executeCommand()) exit(0);
//if (!$auth->hasValidApiKey()) {
//        header('HTTP/1.0 401 Unauthorized');
//        exit(0);
//}

// for apiKey using key in header, in testing with the swagger editor
//require 'auth.php';
//$auth = new PHP_API_AUTH(array(
//        'origin' => 'http://editor.swagger.io',
//        'security'=>array(
//                'CrudApiKeyHeader' => array(
//                        'type' => 'apiKey',
//                        'name' => 'apikey',
//                        'in' => 'header',
//                        'keys' => array(
//                                'placekeyshereforaccess'
//                        )
//                )
//        )
//));
//if ($auth->executeCommand()) exit(0);
//if (!$auth->hasValidApiKey()) {
//        header('HTTP/1.0 401 Unauthorized');
//        exit(0);
//} 

// include your api code here:
//
// see: https://github.com/mevdschee/php-crud-api
//
// placeholder for testing:
// echo 'Access granted!';
