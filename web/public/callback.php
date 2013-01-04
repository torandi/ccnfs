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

$file_id = request("file");

$file = Node::from_id($file_id);

$file_text_id = $file ? $file->id : 0;

if($file) {
	$full_filename = $file->full_path();
} else if($file_id == 0) {
	$file_id = null;
	$full_filename = "/";
} else {
	error("No file with id $file_id");
}

if($file && $file->computer_id != $computer->id) {
	error("No file with id $file_id");
}

switch($cmd) {
case "last_seen":
	output("OK", $computer->formated_last_seen());
	break;
case "ls":

	if($file && $file->type != "dir") error("Node is not a directory");
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
case "read":
	if(!$file || $file->type != "file") error("Node is not a file");

	$res = execute_command($computer, "read $file_text_id $full_filename");
	if($res == 1) {
		$file = Node::from_id($file_id);
		output("OK", $file->data);
	} else if($res == 0) {
		error("Command timed out");
	} else {
		error("Remote computer responded with error.");
	}
	break;
case "write":
	if(!$file || $file->type != "file") error("Node is not a file");

	$data = request("data");
	$lines = count(explode("\n", $data));

	$res = execute_command($computer, "write $lines $full_filename\n$data");
	if($res == 1) {
		$file->data = $data;
		$file->commit();
		output("OK");
	} else if($res == 0) {
		error("Command timed out");
	} else {
		error("Remote computer responded with error.");
	}
	break;
case "mknod":
	if($file && $file->type != "dir") error("Node is not a directory");

	$filename = request("filename");
	if(substr($full_filename, -1) != "/") $full_filename .= "/";
	$full_filename .= $filename;

	if(Node::count_with_parent($file_id, array('computer_id' => $computer->id, 'name' => $filename)) > 0 ) error("File exists");

	$res = execute_command($computer, "write 1 $full_filename\n\n");
	if($res == 1) {
		$new_file = new Node(array('computer_id'=> $computer->id, 'parent' => $file_id, 'name' => $filename, 'type' => 'file', 'data' => "\n"));
		$new_file->commit();
		output("OK",$new_file->id);
	} else if($res == 0) {
		error("Command timed out");
	} else {
		error("Remote computer responded with error.");
	}
	break;
case "mkdir":
	if($file && $file->type != "dir") error("Node is not a directory");

	$filename = request("filename");
	if(!substr($full_filename, -1) == "/") $full_filename .= "/";
	$full_filename .= $filename;

	if(Node::count_with_parent($file_id, array('computer_id' => $computer->id, 'name' => $filename)) > 0 ) error("File exists");

	$res = execute_command($computer, "mkdir $full_filename");
	if($res == 1) {
		$new_node = new Node(array('computer_id'=> $computer->id, 'parent' => $file_id, 'name' => $filename, 'type' => 'dir'));
		$new_node->commit();
		output("OK",$new_node->id);
	} else if($res == 0) {
		error("Command timed out");
	} else {
		error("Remote computer responded with error.");
	}
	break;
default:
	error("Unknown command $cmd");
}
