<?php

class gs_dict {
	static $words=array();
	
	static function append($words) {
		self::$words=array_merge(self::$words,$words);
	}
	
	static function get($key) {
		return isset(self::$words[$key]) ? self::$words[$key] : $key;
	}
}


?>