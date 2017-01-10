<?php

/*

~DATA~

~DATA~

TODO
- input validation when inserting in data

*/

// ignore any given arguments except help
if (isset($argv[1])) {
	if ($argv[1] == 'help') {
		echo "Type 'php pw.php xxx' in order to XXX.\n";
		echo "Type 'php pw.php xxx' in order to XXX.\n";
		echo "Type 'php pw.php xxx' in order to XXX.\n";
		echo "You can adjust settings in pw.php in the init() function.\n";
		die();
	}
}

$strings = (object) [
	'bar' => "::::::::::::::::::::::::::::::::::::::\n\n"
];
// ANSI colors http://bitmote.com/index.php?post/2012/11/19/Using-ANSI-Color-Codes-to-Colorize-Your-Bash-Prompt-on-Linux

PW::init();
while (true) {
	$input = trim(fgets(STDIN));

	if ($input) {

		if (PW::$settings->managerMode == 'ne') {
			PW::saveEntity($input);
		}

		if (PW::$settings->managerMode == '') {
			if ($input == 'ne') {
				PW::$settings->managerMode = 'ne';
				PW::saveEntityMenu();
			}
			if ($input == 'le') {
				PW::$settings->managerMode = 'le';
				PW::listEntities();
			}
		}

	}


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
			'data' => null,
			'key' => null,
			'dataBool' => false,
			'status' => null,
			'managerMode' => ''
		];
		self::$settings->code = file_get_contents(__FILE__);
		self::$settings->dataBool = strlen(self::getDataNeedle('~DATA~'));

		self::toolChecking();

		self::listOptions();

		// echo "\033[38;5;208mee = edit entities\033[0;00m\n";
		// print_r(self::$settings->data);

	}
	public static function initOLD () {
		self::$settings = (object) [
			'arg' => array(
				0 => false
			),
			'status' => array(
				0 => false
			),
			'code' => '',
			'key' => '',
			'data' => null
		];
	}
	public static function toolChecking () {
		global $strings;
		$options = '';

		// first check if there is data


		system('clear');
		echo $strings->bar;

		if (self::$settings->dataBool == 0) {
			echo "Status: \033[38;5;40mWelcome!\033[0;00m\n";
		}
		if (self::$settings->key == null) {
			echo "Status: \033[38;5;40mNo password given\033[0;00m\n\n";
			PW::$settings->managerMode = 'pw';
			self::$settings->key = self::getKey();
			PW::$settings->managerMode = '';
			PW::toolChecking();
			return;
		}

		if (self::$settings->key != null && self::$settings->dataBool == 0) {
			$data = (object) [
				'entity' => array(),
				'type' => array(),
				'text' => array(),
				'entity_has_type' => array(),
				'type_has_text' => array()
			];
			$enc = self::encrypt(serialize($data));
			self::saveData('~DATA~', $enc);
			self::$settings->dataBool = true;
		}

		if (self::$settings->key != null && self::$settings->dataBool) {
			self::$settings->data = unserialize(self::decrypt(self::getDataNeedle('~DATA~')));
			self::$settings->status = 'ready';
		}

	}
	public static function listOptions () {
		$options = '';
		if (self::$settings->status == 'ready') {
			$options .= "\033[38;5;208mne = create new entity\033[0;00m\n";
			$options .= "\033[38;5;208mnt = create new type\033[0;00m\n";
			$options .= "\033[38;5;208mnx = create new text\033[0;00m\n";
			$options .= "\033[38;5;208mle = list entities\033[0;00m\n";

			echo "What would you like to do?\n";
			echo $options;
		}
	}
	public static function saveEntityMenu () {
		global $strings;
		system('clear');
		echo $strings->bar;
		echo "Insert the name of your new entity:\n";
	}
	public static function saveEntity ($name) {
		$entities = self::$settings->data->entity;
		$pass = true;
		foreach ($entities as $entity) {
			if ($entity == $name) {
				$pass = false;
				echo "Sorry, this entity is already taken\n";
				return;
			}
		}
		if ($pass) {
			self::$settings->data->entity[] = $name;
			$enc = self::encrypt(serialize(self::$settings->data));
			self::saveData('~DATA~', $enc);
			echo "A new entity {$name} has been created\n";
			PW::$settings->managerMode = '';
		}
	}
	public static function listEntities () {
		$entities = self::$settings->data->entity;
		foreach ($entities as $entity) {
			echo "{$entity}\n";
		}
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


// TODO
// remove hashed key from this file

// save pw here. use password as key
// http://stackoverflow.com/questions/4081403/how-does-password-based-encryption-technically-work
// hi = 4a7766245aa3b30393c90480684d58f3e0c7ecb81ebd7b791a11082e70a280623999ddb2f5e18fb45bde4fc368d0eade83544155290f281d837345c49ae09d36826e0779579789d9ae343ddbbf779c5340a8cdef7a8243ae942be4adce3709b26e8127424fb5a5909d6619ab7c218e8ae3433eda1e56763ddf8529384c33dd0
