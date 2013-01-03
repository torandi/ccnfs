<?php
include "../includes.php";

/*
 * Callback file used by computer in minecraft
 */

try {

$cmd = request("cmd");

if(!$cmd) {
	error("Command missing");
}

$cmd = strtolower($cmd);

$computer = Computer::from_key(request("key"));

if(!$computer && !($cmd == "hi" && request("new"))) {
	error("Unknown key, register first\n");
}

switch($cmd) {
case "hi":
	if(request("new")) {
		//Create new computer
		$computer = new Computer();
		if($computer->generate_unique_key()) {
			$computer->commit();
			output("OK",$computer->key);
		} else {
			error("Server failed to generate unique key, report to ccnfs admin");
		}
	} else {
		$data = request("data");
		ls($data, null);
		output("OK");
	}
	break;
case "poll":
	break;
default:
	error("Unknown command");
}

} catch (Exception $e) {
	error("Server exception: $e");
}

/*
 * Functions
 */

function output($op, $data="") {
	echo "$op\n";
	echo $data;
	exit();
}

function error($msg) {
	output("ERR",$msg);
}

/**
 * Input handlers
 */

function ls($data, $parent_node) {
	global $computer, $db;
	$files = explode("\n", $data);
	$names = array();
	foreach($files as $file) {
		$split = explode(" ", $file, 2);
		$name = $split[1];
		$type = $split[0];
		if($type != "dir" && $type != "file") error("Invalid type for node $name");
		$names[] = $db->real_escape_string($name);
		$selection = array('computer_id' => $computer->id, 'name' => $name);
		if($parent_node == null) {
			$selection['parent:null'] = null;
		} else {
			$selection['parent'] = $parent_node;
		}
		if($node = Node::one($selection)) {
			if($node->type != $type) {
				$node->type = $type;
				$node->commit();
			}
		} else {
			$node = new Node(array('computer_id' => $computer->id, 'name' => $name, 'parent' => $parent_node, 'type' => $type));
			$node->commit();
		}
	}
	$computer_id = $computer->id;
	if($parent_node) {
		$stmt = $db->prepare("delete from nodes where computer_id = ? and parent = ? and name not in ('" . implode("','", $names) . "')");
		$stmt->bind_param('ii', $computer_id, $parent_node);
	} else {
		$stmt = $db->prepare("delete from nodes where computer_id = ? and parent is null and name not in ('" . implode("','", $names) . "')");
		$stmt->bind_param('i', $computer_id);
	}
	$stmt->execute();
}

?>
