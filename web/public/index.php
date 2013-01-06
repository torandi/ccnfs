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
		<script src="js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
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
				<p><form><input type="checkbox" id="cached"/><label style="display: inline; margin-left: 5px;" for="cached">Use cached data if available</label></form></p>
				<div>
					<div id="directory" style="float: left">
						<form id="dir_form">
							<p> <strong>Dir: </strong> <span id="cur_dir">/</span>
								<input type='button' value='Refresh' style='float: right' id='dir_refresh'/>
							</p>
							<p>
							<select id="files" size="30">
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
								<input type="button" value="New file" id="mkfile"/>
								<input type="button" value="Delete" id="rm" style="float: right;"/> <br/>
								<input type="button" value="New directory" id="mkdir"/>
								<input type="button" value="Move" id="move" style="float: right;"/> <br/>
								<input type="button" value="Copy" id="copy" style="float: right;"/>
							</p>
						</form>
						</p>
					</div>

					<div id="file" style="float: left; margin-left: 10px; width: 600px; display: none;">
						<form id="file_form">
							<p> 
								<strong>File: </strong> <span id="cur_file">-</span>
								<input type="button" value="Save" id="save" style="float: right;"/>
								<input type="button" value="Run & Save" id="run" style="float: right;"/>
							</p>
							<div id="content" style="width: 600px; height: 600px"></div>
						</form>
						</p>
					</div>

				</div>
				<p style="clear: both; padding: 5px; min-height: 32px" class="well" id="log">
					<img src="ajax-loader.gif" id="spinner" style="float: right; display: none;"/>
				</p>

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
