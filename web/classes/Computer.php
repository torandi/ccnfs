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

	public function touch() {
		$this->last_seen = date('Y-m-d H:i:s');
		$this->commit();
	}

	public function last_seen_date() {
		return new DateTime($this->last_seen);
	}

	public function formated_last_seen() {
		$diff = date_diff(new DateTime('now'), $this->last_seen_date(), true);
		if($diff->days >= 1) {
			return $this->last_seen;
		} else {
			if($diff->h == 1) return "1 hour ago";
			if($diff->h > 1) return $diff->h . " hours ago";
			if($diff->i == 1) return "1 minute ago";
			if($diff->i > 1) return $diff->i . " minutes ago";
			return $diff->s . " seconds ago";
		}
	}

	public function nodes($parent = null) {
		$selection = array('computer_id' => $this->id, '@order'=>array('type', 'name'));
		if($parent == null) {
			$selection['parent:null'] = null;
		} else {
			$selection['parent'] = $parent;
		}
		return Node::selection($selection);
	}

	/**
	 * Finds a node from a given path.
	 * Paths must be absolute
	 * @param $error_msg filled with error msg if the function returns null
	 */
	public function find_node($path, &$error_msg) {
		return Node::from_path($this, $path, $error_msg);
	}
}
