<?php

class gs_dict {
	static $words=array();
	
	static function append($words) {
		self::$words=array_merge(self::$words,$words);
	}
	
	static function get($key) {
		if (is_array($key)) {
			foreach ($key as $k=>$v) $key[$k]=gs_dict::get($v);
			return $key;
		}
		return isset(self::$words[$key]) ? self::$words[$key] : $key;
	}
}


?>
