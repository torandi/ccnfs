<?php
include "../includes.php";

write_log("[callback] ". var_export($_REQUEST, true));

$key = request('key');
$computer = Computer::from_key($key);

if(request("format")) $format = 1;

if(!$computer) {
	error("Computer not found or key missing");
}

$cmd = request("cmd");

if(!$cmd) {
	error("Command missing");
}

$filename = str_replace("/", "", request("file"));
$parent = request("parent");

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
case "last_seen":
	output("OK", $computer->formated_last_seen());
	break;
case "ls":

	if(!$file && $full_filename != "/") error("Unknown directory $full_filename");
	if($file && $file->type == "file") error("Can't ls a file");
	$res = execute_command($computer, "ls $file_text_id $full_filename");
	if($res == 1) {
		$data = array();
		$data_str = "";
		foreach($computer->nodes($file_id) as $node) {
			$node_data = array('id' => $node->id, 'name'=>$node->name, 'is_dir'=>$node->is_dir() ? 1 : 0);
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
