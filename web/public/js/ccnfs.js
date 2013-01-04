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

		$("#save").click(write);

		$("#content").keypress(function() {
			file.changed = true;
		});

		$("#mkfile").click(function() {
			if(file.changed && !confirm("The open file has changed, discard changes?")) return;
			var name = prompt("Name: ");
			if(name) {
				create_file(name);
			}
		});

		$("#mkdir").click(function() {
			var name = prompt("Name: ");
			if(name) {
				create_dir(name);
			}
		});

		$("#rm").click(function() {
			var sel = $("#files option:selected");
			var file_id = parseInt(sel.attr("value"));
			var file_path = dir.path + sel.html();
			var type = sel.data("is_dir") == 1 ? "directory" : "file";
			if(!confirm("Delete "+ type + " " + file_path + "?")) return;
			rm(file_id, file_path);
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
	if($("#cached").attr("checked")) {
		data['cached'] = "true";
	}
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
	if(file.changed && !confirm("The open file has changed, discard changes?")) return;
	var log = create_log("read " + new_file.path);
	call_logged(log,'read', {file: new_file.id}, function(data) {
		file = new_file;
		file.changed = false;
		$("#content").val(data);
		$("#cur_file").html(new_file.path);
		$("#file").fadeIn();
	});
}

function write() {
	var log = create_log("write " + file.path);
	call_logged(log,'write', {file: file.id, data: $("#content").val()}, function(data) {
		file.changed = false;
	});
}

function rm(file_id, filepath) {
	var log = create_log("rm " + filepath);
	call_logged(log,'rm', {file: file_id}, function(data) {
		$("#file").fadeOut();
		file.id = null;
	});
	ls(dir);
}

function create_file(name) {
	var new_file = {
		id: null,
		dir: dir,
		path: dir.path + name
	};
	var log = create_log("create file " + new_file.path);
	call_logged(log,'mknod', {file: dir.id, filename: name}, function(data) {
		file = new_file;
		file.changed = false;
		file.id = parseInt(data);
		$("#content").val("");
		$("#cur_file").html(new_file.path);
		$("#file").fadeIn();

		if(new_file.dir.id == dir.id) {
			//Dir has not changed
			$("#files").append("<option value='" + file.id + "' data-is_dir='0'>" + name + "</option>");
		}
	});
}

function create_dir(name) {
	var new_dir = {
		parent: dir,
		id: null,
		path: dir.path + name + "/"
	}
	var log = create_log("create directory " + new_dir.path);
	call_logged(log,'mkdir', {file: dir.id, filename: name}, function(data) {
		dir = new_dir;
		dir.id = parseInt(data);

		dir = new_dir;
		$("#cur_dir").html(new_dir.path);
		$("#files").children(":not(:first)").remove();
	});
}
