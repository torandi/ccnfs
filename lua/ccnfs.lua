-- Config:

server = "http://ccnfs.torandi.com";
config_file = "ccnfs.config";

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
		print("Critical error: http.post returned nil, I'm out of here");
		shell.exit();
	else
		local op = res.readLine();
		local data = res.readAll();
		if(op == "OK") then
			res.close();
			return data;
		elseif(op == "ERR") then
			print(string.format("Error: %s", data));
			return nil;
		end
	end
end

-- does call to server and appends computer key
-- @param data post data to send
-- @return content on OK, nil on ERR or exits on critical error
function call(cmd, data)
	local new_string = string.format("cmd=%s&key=%s",key);
	if(data) then
		new_string = string.format("%s&%s", new_string, data);
	end
	return call_raw(new_string);
end

-- end help functions

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
		shell.exit();
	end
end

print(string.format("Go to %s to interface with this computer\nKEY: %s", server, key));
