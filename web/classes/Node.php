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

	public function is_dir() {
		return $this->type == "dir";
	}

	private static function prepare_parent_selection($parent, $selection = array()) {
		if($parent == null) {
			$selection['parent:null'] = null;
		} else {
			$selection['parent'] = $parent;
		}
		return $selection;
	}

	public static function selection_with_parent($parent, $selection = array()) {
		return Node::selection(Node::prepare_parent_selection($parent, $selection));
	} 

	public static function count_with_parent($parent, $selection = array()) {
		return Node::count(Node::prepare_parent_selection($parent, $selection));
	} 
}
