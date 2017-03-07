<?php
//var_dump($_SERVER['REQUEST_METHOD'],$_SERVER['PATH_INFO']); die();

class PHP_API_AUTH {
	
	public function __construct($config) {
		extract($config);
		
		$verb = isset($verb)?$verb:null;
		$path = isset($path)?$path:null;
		$username = isset($username)?$username:null;
		$password = isset($password)?$password:null;
		$token = isset($token)?$token:null;
		$authenticator = isset($authenticator)?$authenticator:null;
		
		$method = isset($method)?$method:null;
		$request = isset($request)?$request:null;
		$post = isset($post)?$post:null;
		$origin = isset($origin)?$origin:null;
		
		$time = isset($time)?$time:null;
		$leeway = isset($leeway)?$leeway:null;
		$ttl = isset($ttl)?$ttl:null;
		$algorithm = isset($algorithm)?$algorithm:null;
		$secret = isset($secret)?$secret:null;

		$allow_origin = isset($allow_origin)?$allow_origin:null;

		// defaults
		if (!$verb) {
			$verb = 'POST';
		}
		if (!$path) {
			$path = '';
		}
		if (!$username) {
			$username = 'username';
		}
		if (!$password) {
			$password = 'password';
		}
		if (!$token) {
			$token = 'token';
		}
		
		if (!$method) {
			$method = $_SERVER['REQUEST_METHOD'];
		}
		if (!$request) {
			$request = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
			if (!$request) {
				$request = isset($_SERVER['ORIG_PATH_INFO'])?$_SERVER['ORIG_PATH_INFO']:'';
			}
		}
		if (!$post) {
			$post = 'php://input';
		}
		if (!$origin) {
			$origin = isset($_SERVER['HTTP_ORIGIN'])?$_SERVER['HTTP_ORIGIN']:'';
		}

		if (!$time) {
			$time = time();
		}
		if (!$leeway) {
			$leeway = 5;
		}
		if (!$ttl) {
			$ttl = 30;
		}
		if (!$algorithm) {
			$algorithm = 'HS256';
		}

		if ($allow_origin===null) {
			$allow_origin = '*';
		}

		$request = trim($request,'/');
		
		$this->settings = compact('verb', 'path', 'username', 'password', 'token', 'authenticator', 'method', 'request', 'post', 'origin', 'time', 'leeway', 'ttl', 'algorithm', 'secret', 'allow_origin');
	}


	protected function retrieveInput($post) {
		$input = (object)array();
		$data = trim(file_get_contents($post));
		if (strlen($data)>0) {
			if ($data[0]=='{') {
				$input = json_decode($data);
			} else {
				parse_str($data, $input);
				$input = (object)$input;
			}
		}
		return $input;
	}

	protected function generateToken($claims,$time,$ttl,$algorithm,$secret) {
		$algorithms = array('HS256'=>'sha256','HS384'=>'sha384','HS512'=>'sha512');
		$header = array();
		$header['typ']='JWT';
		$header['alg']=$algorithm;
		$token = array();
		$token[0] = rtrim(strtr(base64_encode(json_encode((object)$header)),'+/','-_'),'=');
		$claims['iat'] = $time;
		$claims['exp'] = $time + $ttl;
		$token[1] = rtrim(strtr(base64_encode(json_encode((object)$claims)),'+/','-_'),'=');
		if (!isset($algorithms[$algorithm])) return false;
		$hmac = $algorithms[$algorithm];
		$signature = hash_hmac($hmac,"$token[0].$token[1]",$secret,true);
		$token[2] = rtrim(strtr(base64_encode($signature),'+/','-_'),'=');
		return implode('.',$token);
	}

	protected function getVerifiedClaims($token,$time,$leeway,$ttl,$algorithm,$secret) {
		$algorithms = array('HS256'=>'sha256','HS384'=>'sha384','HS512'=>'sha512');
		if (!isset($algorithms[$algorithm])) return false;
		$hmac = $algorithms[$algorithm];
		$token = explode('.',$token);
		if (count($token)<3) return false;
		$header = json_decode(base64_decode(strtr($token[0],'-_','+/')),true);
		if (!$secret) return false;
		if ($header['typ']!='JWT') return false;
		if ($header['alg']!=$algorithm) return false;
		$signature = bin2hex(base64_decode(strtr($token[2],'-_','+/')));
		if ($signature!=hash_hmac($hmac,"$token[0].$token[1]",$secret)) return false;
		$claims = json_decode(base64_decode(strtr($token[1],'-_','+/')),true);
		if (!$claims) return false;
		if (isset($claims['nbf']) && $time+$leeway<$claims['nbf']) return false;
		if (isset($claims['iat']) && $time+$leeway<$claims['iat']) return false;
		if (isset($claims['exp']) && $time-$leeway>$claims['exp']) return false;
		if (isset($claims['iat']) && !isset($claims['exp'])) {
			if ($time-$leeway>$claims['iat']+$ttl) return false;
		}
		return $claims;
	}

	protected function allowOrigin($origin,$allowOrigins) {
		if (isset($_SERVER['REQUEST_METHOD'])) {
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Expose-Headers: X-XSRF-TOKEN');
			foreach (explode(',',$allowOrigins) as $o) {
				if (preg_match('/^'.str_replace('\*','.*',preg_quote(strtolower(trim($o)))).'$/',$origin)) { 
					header('Access-Control-Allow-Origin: '.$origin);
					break;
				}
			}
		}
	}

	protected function headersCommand() {
		$headers = array();
		$headers[]='Access-Control-Allow-Headers: Content-Type, X-XSRF-TOKEN';
		$headers[]='Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, DELETE, PATCH';
		$headers[]='Access-Control-Allow-Credentials: true';
		$headers[]='Access-Control-Max-Age: 1728000';
		if (isset($_SERVER['REQUEST_METHOD'])) {
			foreach ($headers as $header) header($header);
		} else {
			echo json_encode($headers);
		}
	}

	public function hasValidCsrfToken() {
		$csrf = isset($_SESSION['csrf'])?$_SESSION['csrf']:false;
		if (!$csrf) return false;
		$get = isset($_GET['csrf'])?$_GET['csrf']:false;
		$header = isset($_SERVER['HTTP_X_XSRF_TOKEN'])?$_SERVER['HTTP_X_XSRF_TOKEN']:false;
		return ($get == $csrf) || ($header == $csrf);
	} 

	public function executeCommand() {
		extract($this->settings);
		if ($origin) {
			$this->allowOrigin($origin,$allow_origin);
		}
		if ($method=='OPTIONS') {
			$this->headersCommand();
			return true;
		}
		$no_session = $authenticator && $secret; 
		if (!$no_session) {
			ini_set('session.cookie_httponly', 1);
			session_start();
			if (!isset($_SESSION['csrf'])) {
				if (function_exists('random_int')) $_SESSION['csrf'] = 'N'.random_int(0,PHP_INT_MAX);
				else $_SESSION['csrf'] = 'N'.rand(0,PHP_INT_MAX);
			}
		}
		if ($method==$verb && trim($path,'/')==$request) {
			$input = $this->retrieveInput($post);
			if ($authenticator && isset($input->$username) && isset($input->$password)) {
				$authenticator($input->$username,$input->$password);
				if ($no_session) {
					echo json_encode($this->generateToken($_SESSION,$time,$ttl,$algorithm,$secret));
				} else {
					session_regenerate_id();
					setcookie('XSRF-TOKEN',$_SESSION['csrf'],0,'/');
					header('X-XSRF-TOKEN: '.$_SESSION['csrf']);
					echo json_encode($_SESSION['csrf']);
				}
			} elseif ($secret && isset($input->$token)) {
				$claims = $this->getVerifiedClaims($input->$token,$time,$leeway,$ttl,$algorithm,$secret);
				if ($claims) {
					foreach ($claims as $key=>$value) {
						$_SESSION[$key] = $value;
					}
					session_regenerate_id();
					setcookie('XSRF-TOKEN',$_SESSION['csrf'],0,'/');
					header('X-XSRF-TOKEN: '.$_SESSION['csrf']);
					echo json_encode($_SESSION['csrf']);
				}
			} else {
				if (!$no_session) {
					session_destroy();
				}
			}
			return true;
		}
		return false;
	}
}
