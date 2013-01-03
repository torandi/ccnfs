-- Config:

server = "http://ccnfs.torandi.com";
config_file = "ccnfs.config";

delay = 5

-- end config

url = string.format("%s/cc_callback.php", server);

-- begin help functions

function load_config() 
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
	local new_string = string.format("id=%s", req_id);
	if(data) then
		new_string = string.format("%s&%s", new_string, data);
	end
	call(cmd, new_string);
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

current_write = 0;

-- begin remote call functions

function ls(req_id, file_id, filename)
	if(not fs.exists(filename)) then
		print(string.format("[ls] No such file or directory %s", filename));
		call_req("err", req_id);
		return;
	elseif(not fs.isDir(filename)) then
		print(string.format("[ls] %s is not a directory", filename));
		call_req("err", req_id);
		return;
	else
		local files = fs.list(filename);
		local data = "";
		for _, file in ipairs(files) do
			local type;
			if(fs.isDir(file)) then
				type = "dir";
			else
				type = "file";
			end
			data = data .. string.format("%s %s\n", type, file);
		end
		call_req("ls", req_id, string.format("parent=%d&data=%s", file_id, textutils.urlEncode(data)));
	end
end

-- end remote call functions

remote_functions = {
	ls = function(req_id, cmd, data)
		local file_id, filename = file_data(data);
		ls(req_id, file_id, filename);
	end
}

-- begin main code

version = "0.1 ALPHA"
print(string.format("ComputerCraft Network FileSystem %s\n  Author: Torandi\n", version));

load_config();

-- initialize connection

if(not key) then
	local res = call_raw("cmd=hi&new=true");
	if(res) then
		key = res;
		local fh = fs.open(config_file, "w");
		fh.writeLine(key);
		fh.close();
	else
		-- error message should already been printed
		return;
	end
else
	if(call("hi")) then
		ls(0, 0, "/");
	else
		return;
	end
end

print(string.format("Go to %s to interface with this computer\nKEY: %s", server, key));

-- start main loop

while(true) do
	local poll = lines(call("poll"));

	if(poll) then
		for index, line in ipairs(poll) do
			if(current_write > 0) then
				current_write = current_write - 1;
				-- todo
			elseif(not is_blank(line)) then
				print(string.format(">> %s", line))
				local req_id, cmd, data = line:match("([0-9]+) (%a+) ?(.*)")
				fn = remote_functions[cmd];
				if(fn) then
					fn(req_id, cmd, data);
				else
					call_req("err",req_id);
				end
			end
		end
	end

	sleep(delay);
end
