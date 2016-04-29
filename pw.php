<?php

/*
~HASHED KEY~

~HASHED KEY~

~PASSWORD HASH1212~

~PASSWORD HASH1212~

~TYPE~

~TYPE~

~SUBTYPE~

~SUBTYPE~

*/

PW::init();

foreach ($argv as $arg) {
	// check for help arg
	if ($arg == 'pw' && !PW::$settings->arg1) {
		PW::$settings->arg1 = true;
	}
}

PW::checkToolStatus();

if (PW::$settings->arg1) {
	PW::newPassword();	
}

function strposAll ($haystack, $needle) {
	$offset = 0;
	$allpos = array();
	while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
		$offset   = $pos + 1;
		$allpos[] = $pos;
	}
	return $allpos;
}

class PW {
	public static $settings;
	public static function init () {
		self::$settings = (object) [
			'arg1' => false,
			'keyNeedle' => '~HASHED KEY~',
			'typeNeedle' => '~TYPE~',
			'subtypeNeedle' => '~SUBTYPE~',
			'noHashedKey' => false,
			'invHashedKey' => false,
			'noTypes' => false,
			'noSubtypes' => false,
			'key' => ''
		];
	}
	public static function checkToolStatus () {
		self::$settings->code = file_get_contents('pw.php');

		// check for hashed key
		$hashedKeyLength = strlen(self::getDataNeedle(self::$settings->keyNeedle));
		if ($hashedKeyLength == 0) {
			self::$settings->noHashedKey = true;
		} else if ( $hashedKeyLength > 255 || $hashedKeyLength < 255) {
			self::$settings->invHashedKey = true;
		}

		// check for type
		$typeLength = strlen(self::getDataNeedle(self::$settings->typeNeedle));
		if ($typeLength == 0) {
			self::$settings->noTypes = true;
		}

		// check for subtype
		$typeLength = strlen(self::getDataNeedle(self::$settings->subtypeNeedle));
		if ($typeLength == 0) {
			self::$settings->noSubtypes = true;
		}

		// mandatory settings
		if (self::$settings->noHashedKey) {
			echo "No master password.\n";
			self::saveKey(self::getKey());
		}

		if (self::$settings->invHashedKey) {
			echo "Invalid key.\n";
			die();
		}
	}
	private static function getDataNeedle ($needle) {
		$data = array();
		$data[0] = strposAll(self::$settings->code, $needle);
		$data[1] = substr(self::$settings->code, $data[0][0] + strlen($needle) + 1, $data[0][1] - ($data[0][0] + strlen($needle) + 2));
		return $data[1];
	}
	private static function getKey () {

		echo "Please enter your master password:\n";
		system('stty -echo'); // stop showing characters in terminal
		$password = trim(fgets(STDIN));
		system('stty echo'); // show characters in terminal again

		// http://php.net/manual/en/function.hash-pbkdf2.php
		$iterations = 1000;
		$hashLength = 255;
		$hash = hash_pbkdf2("sha256", $password, '', $iterations, $hashLength); // using without salt

		return $hash;
	}
	private static function saveKey ($hash) {
		if (self::$settings->noHashedKey) {

			$split = strpos(self::$settings->code, self::$settings->keyNeedle) + strlen(self::$settings->keyNeedle) + 1;
			$start = substr(self::$settings->code, 0, $split);
			$endin = substr(self::$settings->code, $split);
			
			$code = $start . $hash . $endin;

			$file = fopen("pw.php", "w");
			fwrite($file, $code);
			fclose($file);

		} else {
			echo "wanted to save key but was not allowed\n";
		}
	}
	public static function newPassword () {

		if (self::$settings->noTypes) {
			echo "\nThere are no types.\n\n";
		} else {
			self::showTypes();
		}

		$type = readline("Enter type: ");
		self::processType($type);

		// check whether type is given else create new

		// $password = trim(fgets(STDIN));

		// $split = strpos(self::$settings->code, self::$settings->pwNeedle) + strlen(self::$settings->pwNeedle) + 1;
		// $start = substr(self::$settings->code, 0, $split);
		// $endin = substr(self::$settings->code, $split);

	}
	public static function showTypes () {

	}
	private static function processType ($type) {
		echo self::getDataNeedle(self::$settings->typeNeedle);
		// use encdec here
	}
	private static function decrypt ($string, $key) {
	  $string = str_split($string);
	  $key = str_split($key);
	  $message = "";
	  $keyUnit = 0;
	  $keyLength = count($key);
	  foreach ($string as $k => $char) {
	    $num = abs(ord($char) - self::$settings->addMax) - ord($key[$keyUnit]);
	    $message .= chr($num);
	    $keyUnit++;
	    if($keyUnit == $keyLength){
	      $keyUnit = 0;
	    }
	  }
	  return $message;
	}
}



// save pw here. use password as key
// http://stackoverflow.com/questions/4081403/how-does-password-based-encryption-technically-work
// hi = 4a7766245aa3b30393c90480684d58f3e0c7ecb81ebd7b791a11082e70a280623999ddb2f5e18fb45bde4fc368d0eade83544155290f281d837345c49ae09d36826e0779579789d9ae343ddbbf779c5340a8cdef7a8243ae942be4adce3709b26e8127424fb5a5909d6619ab7c218e8ae3433eda1e56763ddf8529384c33dd0
