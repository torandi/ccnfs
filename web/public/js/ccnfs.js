var key;
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
	name: null
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
						path: sel.html()
					};
				}
				ls(new_dir);
			} else {
				//TODO
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
		callback(data);
	}, 
	function() {
		log.fadeOut().remove();
		if(error_callback) error_callback();
	});
}

function create_log(entry) {
	var log = $("<span>" + entry + "<br/></span>").appendTo("#log");
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
	call_logged(log,'ls', {parent: new_dir.parent.id, file: new_dir.path}, function(data) {
		dir = new_dir;
		$("#files").children(":not(:first)").remove();
		$.each(data, function(index, file) {
			$("#files").append("<option value='" + file.id + "' data-is_dir='" + file.is_dir + "'>" + file.name + (file.is_dir ? "/" : "") + "</option>");
		})
	});
}
