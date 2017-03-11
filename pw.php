<?php

/*

~DATA~

~DATA~

*/

// ANSI colors http://bitmote.com/index.php?post/2012/11/19/Using-ANSI-Color-Codes-to-Colorize-Your-Bash-Prompt-on-Linux

if (isset($argv[1])) {
	$arg1 = $argv[1];
	if ($arg1 == 'help') {
		PW::showHelp();
	} else if (
		$arg1 == 'import' ||
		$arg1 == 'export' ||
		$arg1 == 'show'
	) {
		PW::init();
	} else {
		echo "Unknown command.\n";
	}
} else {
	PW::showHelp();
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
			'code' => null,
			'key' => null,
			'dataBool' => false,
		];
		self::$settings->code = file_get_contents(__FILE__);
		self::$settings->dataBool = strlen(self::getDataNeedle('~DATA~'));

		self::toolChecking();
	}
	public static function toolChecking () {
		if (self::$settings->key == null) {
			self::$settings->key = self::getKey();
			PW::toolChecking();
			return;
		}

		global $argv;

		if (self::$settings->key != null) {
			if ($argv[1] == 'import') {

				if (self::$settings->dataBool) {
					echo "\033[38;5;160mCaution:\033[0;00m \033[38;5;244mThere is already data avaiable. Please make a backup before you procedure. Import stopped\033[0;00m\n";
					return;
				}

				$data = file_get_contents($argv[2]);
				$enc = self::encrypt(serialize($data));
				self::saveData('~DATA~', $enc);
				echo "\033[38;5;40mImport of file {$argv[2]} done\033[0;00m\n";
				return;
			} else if ($argv[1] == 'export') {
				$data = unserialize(self::decrypt(self::getDataNeedle('~DATA~')));
				self::saveFile($argv[2], $data);
				echo "\033[38;5;40mExport of file {$argv[2]} done\033[0;00m\n";
				return;
			} else if ($argv[1] == 'show') {
				$data = unserialize(self::decrypt(self::getDataNeedle('~DATA~')));
				echo $data;
				echo "\n";
				echo "\033[38;5;244mIf nothing was shown, it might be that you didn't use the correspondig master password\033[0;00m\n";
				return;
			}
		}

	}
	public static function showHelp () {
		echo "\033[38;5;244m- type \033[0;00m\033[38;5;208mphp pw.php export choosename.choosetype\033[0;00m\033[38;5;244m in order to export the encrypted data into a file\033[0;00m\n";
		echo "\033[38;5;244m- type \033[0;00m\033[38;5;208mphp pw.php import choosename.choosetype\033[0;00m\033[38;5;244m in order to import the content of a file and encrypt it\033[0;00m\n";
		echo "\033[38;5;244m- type \033[0;00m\033[38;5;208mphp pw.php show\033[0;00m\033[38;5;244m in order to show the decrypted content\033[0;00m\n";
		// echo "\033[38;5;244m- you may adjust settings in pw.php in the init() function\033[0;00m\n";
		echo "\033[38;5;244m- slashes for file access aren't working at the moment, so all files should be in the same directory\033[0;00m\n";
	}
	private static function getDataNeedle ($needle) {
		$data = array();
		$data[0] = strposAll(self::$settings->code, $needle);
		$data[1] = substr(self::$settings->code, $data[0][0] + strlen($needle) + 1, $data[0][1] - ($data[0][0] + strlen($needle) + 2));
		return $data[1];
	}
	private static function splitDataNeedle ($needle) {
		$data = array();
		$split = strpos(self::$settings->code, $needle) + strlen($needle) + 1;
		$data[0] = substr(self::$settings->code, 0, $split);
		$data[1] = substr(self::$settings->code, $split);
		return $data;
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
	private static function decrypt ($string) {
		$string = str_split($string);
		$key = str_split(self::$settings->key);
		$message = "";
		$keyUnit = 0;
		$keyLength = count($key);
		foreach ($string as $k => $char) {
			$num = abs(ord($char) - 444) - ord($key[$keyUnit]);
			$message .= chr($num);
			$keyUnit++;
			if($keyUnit == $keyLength){
				$keyUnit = 0;
			}
		}
		return $message;
	}
	private static function encrypt ($string) {
		$string = str_split($string);
		$key = str_split(self::$settings->key);
		$secretString = '';
		$keyUnit = 0;
		$keyLength = count($key);
		foreach ($string as $k => $char) {
			$num = abs((ord($char) + ord($key[$keyUnit])) - 444);
			$secretString .= chr($num);
			$keyUnit++;
			if ($keyUnit == $keyLength) {
				$keyUnit = 0;
			}
		}
		return $secretString;
	}
	private static function saveData ($needle, $data) {
		$file = self::splitDataNeedle($needle);
		$code = $file[0] . $data . $file[1];
		self::saveFile(__FILE__, $code);
		self::$settings->code = file_get_contents(__FILE__);
	}
	private static function saveFile ($fileName, $string) {
		$file = fopen($fileName, "w");
		fwrite($file, $string);
		fclose($file);
	}
}
