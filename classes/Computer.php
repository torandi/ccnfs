<?php
class Computer extends BasicObject {
	protected static function table_name() {
		return 'computers';
	}

	public static function from_key($key) {
		if($key) {
			return self::one(array('key' => $key));
		} else {
			return null;
		}
	}

	public function generate_unique_key() {
		$exists = true;
		$tries = 20;
		while($exists) {
			if($tries == 0) {
				return false;
			}
			$key = random_string(8);
			$exists = (Computer::count(array('key' => $key)) > 0);
			--$tries;
		}
		$this->key = $key;
		return true;
	}
}
