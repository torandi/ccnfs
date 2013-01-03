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
		<script src="js/ccnfs.js" type="text/javascript"></script>
		<link type="text/css" rel="stylesheet" href="bootstrap.min.css" />
	</head>
	<body>
		<div class"row" style="margin-top: 10px">
			<div class="span12">
			<h1>ComputerCraft Network File System</h1>
			<? if($computer) { ?>
<script type='text/javascript'>
	ccnfs("<?=$computer->key?>");
</script>
			<div>
				<h2>Connected to computer <?=$computer->key?></h2>
				<p class='alert alert-error' style='display: none' id="error"></p>
				<p><i id="last_seen">Last seen <?=$computer->formated_last_seen()?></i><p>
				<div>
					<div id="directory" style="float: left">
						<form id="dir_form">
							<p> <strong>Dir: </strong> <span id="cur_dir">/</span>
								<input type='button' value='Refresh' style='float: right' id='dir_refresh'/>
							</p>
							<p>
							<select id="files" size="20">
								<option value='0' data-is_dir='1'/>..</option>
<?
foreach($computer->nodes() as $node) {
	echo "<option value='{$node->id}' data-is_dir='" . ($node->is_dir() ? "1" : "0" ) . "'/>{$node->name}" . ($node->is_dir() ? '/' : '') ."</option>
";
}
?>
							</select>
							</p>
							<p>
								<input type="button" value="New directory" id="mkdir"/>
								<input type="button" value="New file" id="mkfile"/>
							</p>
						</form>
						</p>
					</div>

					<div id="file" style="float: left; margin-left: 10px; display: none;">
						<p> <strong>File: </strong> <span id="cur_file">-</span> </p>
						<form id="file_form">
							<p>
							<textarea id="content" rows="20" style="width: 500px;"></textarea>
							</p>
							<p>
								<input type="button" value="Delete file" id="rm"/>
								<input type="button" value="Save" id="save"/>
							</p>
						</form>
						</p>
					</div>

				</div>
				<p style="clear: both;" class="well" id="log"> </p>

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
