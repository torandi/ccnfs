<?php
include "../includes.php";

/*
 * Callback file used by computer in minecraft
 */

try {

write_log("[cc_callback] ". var_export($_REQUEST, true));

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
		/*$data = request("data");
		ls($data, null);*/
		output("OK");
	}
	break;
case "poll":
	$queue = CommandQueue::selection(array('computer_id' => $computer->id, 'status'=>0));
	echo "OK\n";
	foreach($queue as $command) {
		echo "{$command->id} {$command->command}\n";
	}
	break;
case "ls":
	$id = request("id");
	$command = CommandQueue::from_id($id);

	$parent = request("parent");
	if($parent == null) error("Missing argument: parent\n");
	if($parent == 0) $parent = null;
	if($parent != null && Node::count(array('id'=>$parent)) < 1) error("Parent node $parent not found");
	ls(request("data"), $parent);

	if($command) {
		$command->status = 1;
		$command->commit();
	}
	output("OK");
	break;
case "err":
	$id = request("id");
	$command = CommandQueue::from_id($id);

	if($command) {
		$command->status = 2;
		$command->commit();
	}

	output("OK");

default:
	error("Unknown command");
}

} catch (Exception $e) {
	error("Server exception: $e");
}


/**
 * Input handlers
 */

function ls($data, $parent_node) {
	global $computer, $db;
	$data = trim($data);
	$files = explode("\n", $data);
	$names = array();
	foreach($files as $file) {
		$split = explode(" ", trim($file), 2);
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
