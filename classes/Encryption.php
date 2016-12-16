<?php
class Encryption{

	const CIPHER = MCRYPT_RIJNDAEL_128; // Rijndael-128 is AES
	const MODE   = MCRYPT_MODE_CBC;

	public static function encrypt($plainText) {
		if(!isset($GLOBALS['SECURITY_KEY']) || !$GLOBALS['SECURITY_KEY']) return $plainText;
		if(!function_exists('mcrypt_get_iv_size')) return $plainText;
		$ivSize = mcrypt_get_iv_size(self::CIPHER, self::MODE);
		$iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);
		$cipherText = mcrypt_encrypt(self::CIPHER, self::getKey(), $plainText, self::MODE, $iv);
		return base64_encode($iv.$cipherText);
	}

	public static function decrypt($cipherTextIn) {
		if(!isset($GLOBALS['SECURITY_KEY']) || !$GLOBALS['SECURITY_KEY']) return $cipherTextIn;
		if(!function_exists('mcrypt_get_iv_size')) return $cipherTextIn;
		if(strpos($cipherTextIn,'CollEditor') !== false || strpos($cipherTextIn,'CollAdmin') !== false) return $cipherTextIn;
		if(strpos($cipherTextIn,'uid=') !== false) return $cipherTextIn;
		$cipherText = base64_decode($cipherTextIn);
		if(!$cipherText) return $cipherTextIn;
		$ivSize = mcrypt_get_iv_size(self::CIPHER, self::MODE);
		if(strlen($cipherText) < $ivSize) {
			throw new Exception('Missing initialization vector');
		}
		$iv = substr($cipherText, 0, $ivSize);
		$cipherText = substr($cipherText, $ivSize);
		$plainText = mcrypt_decrypt(self::CIPHER, self::getKey(), $cipherText, self::MODE, $iv);
		return rtrim($plainText, "\0");
	}

	public static function getKey(){
		if(strlen($GLOBALS['SECURITY_KEY']) > 31)
			return substr($GLOBALS['SECURITY_KEY'],0,32);
		if(strlen($GLOBALS['SECURITY_KEY']) > 23)
			return substr($GLOBALS['SECURITY_KEY'],0,24);
		if(strlen($GLOBALS['SECURITY_KEY']) > 15)
			return substr($GLOBALS['SECURITY_KEY'],0,16);
	}
}
?>