<?

include "../includes.php";

$key = get('key');

$computer = Computer::from_key($key);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="sv" lang="sv">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>ComputerCraft Network File System</title>
		<script src="js/jquery.js" type="text/javascript"></script>
		<link type="text/css" rel="stylesheet" href="bootstrap.min.css" />
	</head>
	<body>
		<div class"row" style="margin-top: 10px">
			<div class="span12">
			<h1>ComputerCraft Network File System</h1>
			<? if($computer) { ?>
			<div>
			</div>
			<? } else { ?>
			<div>
				<form method="get">
					<label for="key">Computer identification key: </label>
					<input type="text" name="key" id="key"/>
					<br/>
					<input type="submit" value="Open"/>
				</form>
			</div>
			<? } ?>
		</div>
		</div>
	</body>
</html>
