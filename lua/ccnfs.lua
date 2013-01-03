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

function call(cmd, data)
	
end

-- end help functions

-- begin main code

version = "0.1 ALPHA"
print(string.format("ComputerCraft Network FileSystem %s\n  Author: Torandi\n", version));

load_config();

if(!key) then


-- initialize connection
