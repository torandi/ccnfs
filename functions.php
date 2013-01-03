<?php

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

function random_string ($length = 8) {
	$randstr = "";

	$possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGIJKLMNOPQRSTUVWXYZ"; 

	for ($i=0;$i<$length;$i++) { 
	 $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
	 $randstr .= $char;
	}
	return $randstr;
}
