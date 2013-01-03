var key;
var active_logs = 0;

var dir = {
	parent: {
		id: 0,
		path: "/"
	},
	id: 0,
	path: "/"
}
var file = {
	id: 0,
	dir: null,
	path: null,
	changed: false
}

function ccnfs(ckey) {
	key = ckey;
	$(function() {

		$("#dir_refresh").click(function() {
			ls(dir)
		});

		$("#files option").live('click',function() {
			var sel = $("#files option:selected");
			var new_id = parseInt(sel.attr("value"));
			var is_dir = sel.data("is_dir");
			if(is_dir) {
				var new_dir;
				if(new_id == 0 && dir.id != 0) {
					new_dir = dir.parent;
				} else if(new_id == 0) {
					new_dir = dir;
				} else {
					var new_dir = {
						parent: dir,
						id: new_id,
						path: dir.path + sel.html()
					};
				}
				ls(new_dir);
			} else {
				var new_file = {
					id: new_id,
					dir: dir,
					path: dir.path + sel.html(),
					changed: false
				};
				read(new_file);
			}
		});

		refresh_last_seen();
	});
}

function call(cmd, data, callback, error_callback) {
	data['cmd'] = cmd;
	data['format'] = "true";
	data['key'] = key;
	$.post("callback.php", data, function(data) {
		if(data.status == "OK") {
			$("#error").fadeOut();
			callback(data.data);
		} else {
			$("#error").html(cmd + ": " +data.data);
			$("#error").fadeIn();
			if(error_callback) error_callback();
		}
	});
}

function call_logged(log, cmd, data, callback, error_callback) {
	call(cmd, data, function(data) {
		log.fadeOut().remove();
		pop_log();
		callback(data);
	}, 
	function() {
		log.fadeOut().remove();
		pop_log();
		if(error_callback) error_callback();
	});
}

function pop_log() {
	--active_logs;
	if(active_logs == 0) {
		$("#spinner").fadeOut();
	}
}

function create_log(entry) {
	var log = $("<span>" + entry + "<br/></span>").appendTo("#log");
	++active_logs;
	$("#spinner").fadeIn();
	return log;
}

function refresh_last_seen() {
	call('last_seen', {}, function(data) {
		$("#last_seen").html("Last seen " + data);
		setTimeout(refresh_last_seen, 30000);
	});
}

function ls(new_dir) {
	var log = create_log("ls " + new_dir.path);
	call_logged(log,'ls', {file: new_dir.id}, function(data) {
		dir = new_dir;
		$("#cur_dir").html(new_dir.path);
		$("#files").children(":not(:first)").remove();
		$.each(data, function(index, file) {
			$("#files").append("<option value='" + file.id + "' data-is_dir='" + file.is_dir + "'>" + file.name + (file.is_dir ? "/" : "") + "</option>");
		})
	});
}

function read(new_file) {
	if(file.changed && !confirm("The file has changed, discard changes?")) return;
	var log = create_log("read " + new_file.path);
	call_logged(log,'read', {file: new_file.id}, function(data) {
		file = new_file;
		file.changed = false;
		$("#content").text(data);
		$("#cur_file").html(new_file.path);
		$("#file").fadeIn();
	});
}


