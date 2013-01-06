-- Config:

server = "http://ccnfs.torandi.com";
config_file = "ccnfs.conf";

max_delay = 10
min_delay = 2

cur_delay = min_delay

-- end config

url = string.format("%s/cc_callback.php", server);
version = 1
version_long = "0.2 BETA"

version_url_string = "version=" .. version

-- begin help functions

function load_config()
	local disk_file = "/disk/" .. config_file;
	if(fs.exists(disk_file)) then
		config_file = disk_file;
	end
	local file = fs.open(config_file, "r");
	if(file) then
		key = file.readLine();
	else
		key = nil;
	end
end

-- from http://lua-users.org/wiki/SplitJoin
-- @param p string
-- @param d delimiter
function split(p, d)
  local t, ll
  t={}
  ll=0
  if(#p == 1) then return {p} end
    while true do
      l=string.find(p,d,ll,true) -- find the next d in the string
      if l~=nil then -- if "not not" found then..
        table.insert(t, string.sub(p,ll,l-1)) -- Save it in our array.
        ll=l+1 -- save just after where we found it for searching next time.
      else
        table.insert(t, string.sub(p,ll)) -- Save what's left in our array.
        break -- Break at end, as it should be, according to the lua manual.
      end
    end
  return t
end

function update()
	res = http.get(server .. "/lua/ccnfs.lua");
	if(not res) then
		error("Update: Failed to read ccnfs.lua from server");
	end
	fh = fs.open(shell.getRunningProgram(), "w");
	if(not fh) then
		error("Update: Can't open " .. shell.getRunningProgram() .. " for writing");
	else
		fh.write(res.readAll());
		fh.close();
		print("Program updated, rebooting system");
		os.reboot();
	end
end

-- Call to server, does not append computer key
-- @see call
function call_raw(data)
	local res = http.post(url,data);
	if(not res) then
		error("Critical error: http.post returned nil, I'm out of here");
	else
		local op = res.readLine();
		local data = res.readAll();
		if(op == "OK") then
			res.close();
			return data;
		elseif(op == "ERR") then
			print(string.format("Server responded with error: %s", data));
			return nil;
		elseif(op == "UPDATE") then
			print("Local program out of date, updating!");
			update();
		else
			print(string.format("Unknown response operation from server: %s", op));
			return nil;
		end
	end
end

-- does call to server and appends computer key
-- @param data post data to send
-- @return content on OK, nil on ERR or s on critical error
function call(cmd, data)
	local new_string = string.format("cmd=%s&key=%s", cmd, key);
	if(data) then
		new_string = string.format("%s&%s", new_string, data);
	end
	return call_raw(new_string);
end

function call_req(cmd, req_id, data)
	local new_string = string.format("id=%d", req_id);
	if(data) then
		new_string = string.format("%s&%s", new_string, data);
	end
	call(cmd, new_string);
end

function req_error(req_id, msg)
	print("Error: " .. msg);
	call_req("err", req_id, "data=" .. msg);
end

function lines(str) 
	if(not str) then
		return nil;
	end

	return split(str, "\n");
end

function file_data(str) 
	local file_id, filename = str:match("([0-9]+) (.+)");
	return file_id, filename;
end

function is_blank(x)
  return not not tostring(x):find("^%s*$")
end

-- end help functions

current_write = {
	lines_left = 0,
	fh = nil,
	req_id = nil,
}

-- begin remote call functions

function ls(req_id, file_id, filename)
	if(not fs.exists(filename)) then
		req_error(req_id, string.format("[ls] No such file or directory %s", filename));
		return;
	elseif(not fs.isDir(filename)) then
		req_error(req_id, string.format("[ls] %s is not a directory", filename));
		return;
	else
		local files = fs.list(filename);
		local data = "";
		for _, file in ipairs(files) do
			local type;
			if(fs.isDir(fs.combine(filename, file))) then
				type = "dir";
			else
				type = "file";
			end
			data = data .. string.format("%s %s\n", type, file);
		end
		call_req("ls", req_id, string.format("parent=%d&data=%s", file_id, textutils.urlEncode(data)));
	end
end

function read(req_id, file_id, filename) 
	if(not fs.exists(filename)) then
		req_error(req_id, string.format("[read] No such file or directory %s", filename));
		return;
	elseif(fs.isDir(filename)) then
		req_error(req_id, string.format("[read] %s is a directory", filename));
		call_req("err", req_id);
		return;
	else
		local file = fs.open(filename, "r");
		local data = file.readAll();
		file.close();
		call_req("read", req_id, string.format("file=%d&data=%s", file_id, textutils.urlEncode(data)));
	end
end

function write(req_id, num_lines, filename)
	if(fs.isReadOnly(filename)) then
		req_error(req_id, string.format("[write] %s is read only\n", filename));
		return;
	else
		current_write.lines_left = tonumber(num_lines);
		current_write.fh = fs.open(filename, "w");
		current_write.req_id = req_id;
	end
end

function mkdir(req_id, filename)
	if(fs.isReadOnly(filename)) then
		req_error(req_id, string.format("[mkdir] %s is read only\n", filename));
		return;
	elseif (fs.exists(filename)) then
		req_error(req_id, string.format("[mkdir] %s : file exists\n", filename));
		return;
	else
		fs.makeDir(filename);
		call_req("done", req_id);
	end
end

function rm(req_id, filename) 
	if(fs.isReadOnly(filename)) then
		req_error(req_id, string.format("[rm] %s is read only\n", filename));
		return;
	elseif (not fs.exists(filename)) then
		req_error(req_id, string.format("[rm] %s file doesn't exist\n", filename));
		return;
	else
		fs.delete(filename);
		call_req("done", req_id);
	end
end

function run(req_id, filename) 
	if(not fs.exists(filename)) then
		req_error(req_id, string.format("[read] No such file or directory %s", filename));
		return;
	else 
		call_req("done", req_id);
		shell.run(filename);
	end
end

function write_line(line) 
	current_write.fh.writeLine(line);

	current_write.lines_left = current_write.lines_left - 1;
	if(current_write.lines_left == 0) then
		current_write.fh.close();
		call_req("done",current_write.req_id);
	end
end


-- end remote call functions

remote_functions = {
	ls = function(req_id, data)
		local file_id, filename = file_data(data);
		ls(req_id, file_id, filename);
	end,
	read = function(req_id, data) 
		local file_id, filename = file_data(data);
		read(req_id, file_id, filename);
	end,
	write = function(req_id, data)
		local lines, filename = file_data(data);
		write(req_id, lines, filename);
	end,
	mkdir = mkdir,
	rm = rm,
	run = run,
}

-- begin main code

print(string.format("ComputerCraft Network FileSystem %s\n  Author: Torandi\n", version));

load_config();

-- initialize connection

if(not key) then
	local res = call_raw("cmd=hi&new=true&" .. version_url_string);
	if(res) then
		key = res;
		local fh = fs.open(config_file, "w");
		fh.writeLine(key);
		fh.close();
		ls(0, 0, "/");
	else
		-- error message should already been printed
		return;
	end
else
	if(call("hi", version_url_string)) then
		ls(0, 0, "/");
	else
		return;
	end
end

print(string.format("Go to %s to interface with this computer\nKEY: %s", server, key));

-- start main loop

while(true) do
	local poll = lines(call("poll"));

	local any = false;

	if(poll) then
		for index, line in ipairs(poll) do
			if(current_write.lines_left > 0) then
				write_line(line);
			elseif(not is_blank(line)) then
				print(string.format(">> %s", line))
				any = true;
				local req_id, cmd, data = line:match("([0-9]+) (%a+) ?(.*)")
				fn = remote_functions[cmd];
				if(fn) then
					fn(req_id, data);
				else
					req_error(req_id, "Unknown command " .. cmd);
				end
			end
		end
	end

	if(any) then
		cur_delay = min_delay;
	elseif (cur_delay < max_delay) then
		cur_delay = cur_delay + 1;
	end
	sleep(cur_delay);
end
