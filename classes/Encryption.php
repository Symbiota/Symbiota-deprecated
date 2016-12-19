<?php
class Encryption{

	const METHOD = 'aes-256-cbc';

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

	public static function encrypt_new($plainText) {
		if(!isset($GLOBALS['SECURITY_KEY']) || !$GLOBALS['SECURITY_KEY']) return $plainText;
		if(!function_exists('openssl_encrypt')) return $plainText;
		$key = self::getKey();
		if (mb_strlen($key, '8bit') !== 32) {
			throw new Exception("Needs a 256-bit key!");
		}
		$ivsize = openssl_cipher_iv_length(self::METHOD);
		$iv = openssl_random_pseudo_bytes($ivsize);
	
		$ciphertext = openssl_encrypt($plainText, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
	
		return $iv . $ciphertext;
	}

	public static function decrypt_new($cipherText) {
		if(!isset($GLOBALS['SECURITY_KEY']) || !$GLOBALS['SECURITY_KEY']) return $cipherText;
		if(!function_exists('openssl_decrypt')) return $cipherText;
		if(strpos($cipherText,'CollEditor') !== false || strpos($cipherText,'CollAdmin') !== false) return $cipherText;
		if(strpos($cipherText,'uid=') !== false) return $cipherText;
		$key = self::getKey();
		if (mb_strlen($key, '8bit') !== 32) {
			throw new Exception("Needs a 256-bit key!");
		}
		$ivSize = openssl_cipher_iv_length(self::METHOD);
		$iv = mb_substr($cipherText, 0, $ivSize, '8bit');
		$cipherText = mb_substr($cipherText, $ivSize, null, '8bit');
	
		return openssl_decrypt($cipherText, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
	}

	public static function getKey_new(){
		if(strlen($GLOBALS['SECURITY_KEY']) > 32)
			return substr($GLOBALS['SECURITY_KEY'],0,32);
			if(strlen($GLOBALS['SECURITY_KEY']) < 32){
				return str_pad($GLOBALS['SECURITY_KEY'], 32, substr($GLOBALS['SECURITY_KEY'],0,1), STR_PAD_BOTH);
			}
	}
}
?>