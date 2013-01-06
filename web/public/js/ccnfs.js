var key;
var active_logs = 0;
var editor;
var error_timeout = 0;

var dir = {
	parent: {
		id: 0,
		path: "/"
	},
	id: 0,
	path: "/"
}
var file = {
	id: null,
	dir: null,
	path: null,
	changed: false
}

var ignore_cached = false;

function ccnfs(ckey) {
	key = ckey;
	$(function() {

		ace.config.set("workerPath", "js/ace");
		editor = ace.edit("content");
		editor.setTheme("ace/theme/eclipse");
		editor.getSession().setMode("ace/mode/lua");


		$("#dir_refresh").click(function() {
			ignore_cached  = true;
			ls(dir)
			ignore_cached = false;
		});

		$("#save").click(write);

		editor.getSession().on("change", function(e) {
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

		$("#move").click(function() {
			var sel = get_selected();
			if(!sel) {
				alert("Nothing selected");
				return;
			}
			var newpath = prompt("Move " + sel.path + " to: ");
			if(newpath) {
				move(sel, newpath);
				ignore_cached  = true;
				ls(dir)
				ignore_cached = false;
			}
		});

		$("#rm").click(function() {
			var sel = get_selected();
			if(!sel) {
				alert("Nothing selected");
				return;
			}
			if(!confirm("Delete "+ sel.type + " " + sel.path + "?")) return;
			rm(sel.id, sel.path);
		});

		$("#run_selected").click(function() {
			var sel = get_selected();
			if(!sel) {
				alert("Nothing selected");
				return;
			}
			if(sel.is_dir) {
				alert("You can't run a directory :D");
				return;
			}
			run(sel.id, sel.path);
		});

		$("#run").click(function() {
			write();
			run(file.id, file.path);
		});

		$("#files option").live('dblclick',function() {
			var sel = get_selected();
			if(!sel) {
				alert("Nothing selected? Wat?");
				return;
			}
			if(sel.is_dir) {
				var new_dir;
				if(sel.id == 0 && dir.id != 0) {
					new_dir = dir.parent;
				} else if(sel.id == 0) {
					new_dir = dir;
				} else {
					var new_dir = {
						parent: dir,
						id: sel.id,
						path: sel.path
					};
				}
				ls(new_dir);
			} else {
				var new_file = {
					id: sel.id,
					dir: dir,
					path: sel.path,
					changed: false
				};
				read(new_file);
			}
		});

		$(document).keydown(function(event) {

				//19 for Mac Command+S
				if (( String.fromCharCode(event.which).toLowerCase() == 's' && event.ctrlKey) || (event.which == 19)) {

					if(file.id != null) {
						write();
					}

					event.preventDefault();
					return false;
				}

				if (( String.fromCharCode(event.which).toLowerCase() == 'r' && event.ctrlKey)) {
					if(file.id != null) {
						write()
						run(file.id, file.path);
					}

					event.preventDefault();
					return false;
				}
		});


		refresh_last_seen();
	});
}

/*
 * Returns { id, name, path, type, is_dir } or null if nothing is selected
 */
function get_selected() {
	var sel = $("#files option:selected");

	if(sel.size() == 0) return null;

	return {
		id: parseInt(sel.attr("value")),
		is_dir: sel.data("is_dir"),
		type: sel.data("is_dir") == 1 ? "directory" : "file",
		name: sel.html(),
		path: dir.path + sel.html()
	}
}

function call(cmd, data, callback, error_callback) {
	data['cmd'] = cmd;
	data['format'] = "true";
	data['key'] = key;
	if(!ignore_cached && $("#cached").attr("checked")) {
		data['cached'] = "true";
	}
	$.post("callback.php", data, function(data) {
		if(data.status == "OK") {
			if(error_timeout < time()) $("#error").fadeOut();
			
			if(callback) callback(data.data);
		} else {
			$("#error").html(cmd + ": " +data.data);
			$("#error").fadeIn();
			error_timeout = time() + 7000;
			if(error_callback) error_callback();
		}
	});
}

function call_logged(log, cmd, data, callback, error_callback) {
	call(cmd, data, function(data) {
		log.fadeOut().remove();
		pop_log();
		if(callback) callback(data);
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
		set_editor(data);
		$("#cur_file").html(new_file.path);
		$("#file").fadeIn();
	});
}

function write() {
	var log = create_log("write " + file.path);
	call_logged(log,'write', {file: file.id, data: editor.getValue()}, function(data) {
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

function run(file_id, filepath) {
	var log = create_log("run " + filepath);
	call_logged(log,'run', {file: file_id});
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
		set_editor("");
		$("#cur_file").html(new_file.path);
		$("#file").fadeIn();

		if(new_file.dir.id == dir.id) {
			//Dir has not changed
			$("#files").append("<option value='" + file.id + "' data-is_dir='0'>" + name + "</option>");
		}
	});
}

function move(oldfile, newfile) {
	if(newfile[0] != "/") newfile = dir.path + newfile;
	var log = create_log("move " + oldfile.path + " to " + newfile);
	call_logged(log,'mv', {file: oldfile.id, target: newfile});
	ls(dir);
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

function set_editor(new_value) {
	editor.setValue(new_value);
	editor.clearSelection();
	editor.scrollToRow(0);
	file.changed = false;
}

function time() {
	return new Date().getTime();
}
