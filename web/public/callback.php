<?php
include "../includes.php";

$key = get('key');
$computer = Computer::from_key($key);

if(!$computer) {
	error("Computer not found or key missing");
}

$cmd = get("cmd");

if(get("format")) $format = 1;

if(!$cmd) {
	error("Command missing");
}

$filename = get("file");
$parent = get("parent");
$selection = array('computer_id' => $computer->id, 'name' => $filename);

if($parent == 0) $parent = null;

if($parent == null) {
	$selection['parent:null'] = null;
} else {
	$selection['parent'] = $parent;
}

$file = Node::one($selection);

$file_id = $file ? $file->id : null;

$file_text_id = $file ? $file->id : 0;

if($file) {
	$full_filename = $file->full_path();
} else {
	$full_filename = $filename;
	if($full_filename == "") $full_filename = "/";
}

switch($cmd) {
case "ls":

	if(!$file && $full_filename != "/") error("Unknown directory $full_filename");
	if($file && $file->type == "file") error("Can't ls a file");

	if($res = execute_command($computer, "ls $file_text_id $full_filename") == 1) {
		$data = array();
		$selection = array('computer_id' => $computer->id);
		if($file_id) {
			$selection['parent'] = $file_id;
		} else {
			$selection['parent:null'] = null;
		}
		$data_str = "";
		foreach(Node::selection($selection) as $node) {
			$node_data = array('id' => $node->id, 'name'=>$node->name, 'is_dir'=>$node->type == "dir");
			$data[] = $node_data;
			if(!$format) {
				$data_str .= "{$node->id} " . ($node_data['is_dir'] ? "1" : "0" ) . " {$node->name}\n";
			}
		}
		output("OK", $format ? $data : $data_str);
	} else if($res == 0) {
		error("Command timed out");
	} else {
		error("Remote computer responded with error.");
	}
	break;
default:
	error("Unknown command $cmd");
}
