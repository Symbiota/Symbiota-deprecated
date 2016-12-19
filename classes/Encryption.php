<?php
class Encryption{

	const METHOD = 'aes-256-cbc';

	public static function encrypt($plainText) {
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

	public static function decrypt($cipherText) {
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

	public static function getKey(){
		if(strlen($GLOBALS['SECURITY_KEY']) > 32)
			return substr($GLOBALS['SECURITY_KEY'],0,32);
		if(strlen($GLOBALS['SECURITY_KEY']) < 32){
			return str_pad($GLOBALS['SECURITY_KEY'], 32, substr($GLOBALS['SECURITY_KEY'],0,1), STR_PAD_BOTH);
		}
	}
}
?>