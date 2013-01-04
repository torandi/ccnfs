<?php

$format = 0; //Set to 1 to format with json
$use_cache = 0; //set to 1 to use cache if possible

function request($var, $default=NULL) {
	if(isset($_REQUEST[$var])) 
		return $_REQUEST[$var];
	else
		return $default;
}

function get($var, $default=NULL) {
	if(isset($_GET[$var])) 
		return $_GET[$var];
	else
		return $default;
}

function post($var, $default=NULL) {
	if(isset($_POST[$var])) 
		return $_POST[$var];
	else
		return $default;
}

function redirect($url) {
	header("Location: $url");
}

function output_json($data) {
	header("Content-Type: text/json");
	echo json_encode($data);
	exit();
}

function random_string ($length = 8) {
	$randstr = "";

	$possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGIJKLMNOPQRSTUVWXYZ"; 

	for ($i=0;$i<$length;$i++) { 
	 $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
	 $randstr .= $char;
	}
	return $randstr;
}

function output($op, $data="") {
	global $format;
	if(!$format) {
		echo "$op\n";
		echo $data;
	} else {
		output_json(array('status'=>$op, 'data'=>$data));
	}
	exit();
}

function error($msg) {
	output("ERR",$msg);
}

function execute_command($computer, $cmd, $allow_caching = false) {
	global $use_cache;

	$cached = ($use_cache && $allow_caching);

	$command = new CommandQueue(array('computer_id' => $computer->id, 'command' => $cmd, 'cached' => $cached));
	$command->commit();

	if(!$cached) {
		$id = $command->id;
		for($tries=0; $tries<10; ++$tries) {
			sleep(2);
			$command = CommandQueue::from_id($id);
			if($command->status != 0) break;
		}
		$status = $command->status;
		$command->delete();
		return $status;
	} else {
		return 1;
	}
}

function write_log($line) {
	global $repo_root;
	$f = fopen("$repo_root/ccnfs.log", 'a');
	fwrite($f, "$line\n");
	fclose($f);
}
