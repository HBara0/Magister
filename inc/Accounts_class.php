<?php
class Accounts {
	protected static function create_password($password, $salt) {
		$md5pass = md5($password);		
		return md5(md5($salt).$md5pass);
	}
	
	protected static function create_crypt_password($password, $salt, $workload = 12) {
		return crypt($password, "$2a$".$workload."$".$salt);
	}
	
	protected static function create_salt() {
		if(function_exists('random_string')) {
			return random_string(8);
		}
		else
		{
			return self::random_string(8);
		}
	}
	
	protected static function create_advanced_salt() {
		return substr(str_replace('+', '.', base64_encode(sha1(microtime(true), true))), 0, 22);	
	}

	protected static function create_loginkey() {
		if(function_exists('random_string')) {
			return random_string(40);
		}
		else
		{
			return self::random_string(40);
		}
	}
	
	protected static function username_exists($username) {
		global $db;
		
		$result = $db->fetch_array($db->query("SELECT COUNT(*) AS userscount FROM ".Tprefix."users WHERE username='".$db->escape_string($username)."'"));
		if($result['userscount'] > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}	
	
	protected static function random_string($length) {
		$keys = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$max  = strlen($keys) - 1;
		
		for ($i = 0; $i < $length; $i++)
		{
		   $rand  = rand(0, $max);
		   $rand_key[] = $keys{$rand};
		}
		
		$output = implode('', $rand_key);	
		return $output;
	}
}
?>