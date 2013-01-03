<?php
class Node extends BasicObject {
	protected static function table_name() {
		return 'nodes';
	}

	public function parent_node() {
		return ($this->parent == null ? null : Node::from_id($this->parent));
	}

	public function full_path() {
		if($this->parent) {
			return $this->parent_node()->full_path() . "/" . $this->name;
		} else {
			return "/" . $this->name;
		}
	}
}
