<?php
class Node extends BasicObject {
	protected static function table_name() {
		return 'nodes';
	}

	public function parent_node() {
		return ($this->parent == null ? null : Node::from_id($this->parent));
	}

	public function children() {
		return Node::selection(array('computer_id' => $this->computer_id, 'parent' => $this->id));
	}

	public function has_children() {
		return (Node::count(array('computer_id' => $this->computer_id, 'parent' => $this->id)) > 0);
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

	public static function one_with_parent($parent, $selection = array()) {
		return Node::one(Node::prepare_parent_selection($parent, $selection));
	} 

	public static function selection_with_parent($parent, $selection = array()) {
		return Node::selection(Node::prepare_parent_selection($parent, $selection));
	} 

	public static function count_with_parent($parent, $selection = array()) {
		return Node::count(Node::prepare_parent_selection($parent, $selection));
	} 

	/**
	 * Finds a node from a given path.
	 * Paths must be absolute
	 * @param $error_msg filled with error msg if the function returns null
	 */
	public static function from_path($computer, $strpath, &$error_msg) {
		if($strpath[0] == "/") $strpath = substr($strpath, 1);
		if(substr($strpath, -1) == "/") $strpath = substr($strpath, 0, strlen($strpath) - 1);

		$paths = explode("/", $strpath);
		$curpath = "/";
		$node = null;
		$parent_id = null;
		foreach($paths as $path) {
			if($node != null && !$node->is_dir()) {
				$error_msg = "$curpath is not a directory";
			}
			write_log("Find p: $parent_id, cid: {$computer->id}, name: $path");
			$node = Node::one_with_parent($parent_id, array('computer_id' => $computer->id, 'name' => $path));
			if(!$node) {
				$error_msg = "No such file or directory $curpath$path";
				return null;
			}
			$parent_id = $node->id;
			$curpath .= "$path/";
		}
		return $node;
	}
}
