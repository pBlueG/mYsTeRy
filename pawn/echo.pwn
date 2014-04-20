// (c) 2009, BlueG
// updated 2014 BlueG

#include <a_samp>

#define COLOR 0xFFFFFF
#define MAX_IRC_NAME 30 //FFSNetwork NICKLEN=30
#define IRC_ACTION_FILE "commands.txt"

new g_maxPlayers;

public ReadActions();

new aWeaponNames[][32] = {
	{"Unarmed (Fist)"}, // 0
	{"Brass Knuckles"}, // 1
	{"Golf Club"}, // 2
	{"Night Stick"}, // 3
	{"Knife"}, // 4
	{"Baseball Bat"}, // 5
	{"Shovel"}, // 6
	{"Pool Cue"}, // 7
	{"Katana"}, // 8
	{"Chainsaw"}, // 9
	{"Purple Dildo"}, // 10
	{"Big COLOR Vibrator"}, // 11
	{"Medium COLOR Vibrator"}, // 12
	{"Small COLOR Vibrator"}, // 13
	{"Flowers"}, // 14
	{"Cane"}, // 15
	{"Grenade"}, // 16
	{"Teargas"}, // 17
	{"Molotov"}, // 18
	{" "}, // 19
	{" "}, // 20
	{" "}, // 21
	{"Colt 45"}, // 22
	{"Colt 45 (Silenced)"}, // 23
	{"Desert Eagle"}, // 24
	{"Normal Shotgun"}, // 25
	{"Sawnoff Shotgun"}, // 26
	{"Combat Shotgun"}, // 27
	{"Micro Uzi (Mac 10)"}, // 28
	{"MP5"}, // 29
	{"AK47"}, // 30
	{"M4"}, // 31
	{"Tec9"}, // 32
	{"Country Rifle"}, // 33
	{"Sniper Rifle"}, // 34
	{"Rocket Launcher"}, // 35
	{"Heat-Seeking Rocket Launcher"}, // 36
	{"Flamethrower"}, // 37
	{"Minigun"}, // 38
	{"Satchel Charge"}, // 39
	{"Detonator"}, // 40
	{"Spray Can"}, // 41
	{"Fire Extinguisher"}, // 42
	{"Camera"}, // 43
	{"Night Vision Goggles"}, // 44
	{"Infrared Vision Goggles"}, // 45
	{"Parachute"}, // 46
	{"Fake Pistol"} // 47
};

public OnFilterScriptInit()
{
	WriteEcho("[color=blue][--] [color=black]Server is now [color=green]online.");
	if(fexist(IRC_ACTION_FILE))
		fremove(IRC_ACTION_FILE);
	SetTimer("ReadActions", 1500, 1);
	g_maxPlayers = GetMaxPlayers();
	return 1;
}

stock GetPlayerCount()
{
	new pCount;
	for(new i;i < g_maxPlayers;i++) {
		if(IsPlayerConnected(i))
		        pCount++;
	}
	return pCount;
}

public ReadActions()
{
	new
		string[256],
		idx = 0,
		reason[128],
		action[32],
		admin[MAX_IRC_NAME],
		playerid,
		pName[MAX_PLAYER_NAME],
		message[128];
		
	if(fexist(IRC_ACTION_FILE)) {
	    new File:handle = fopen(IRC_ACTION_FILE, io_read);
		fread(handle, string);
		fclose(handle);
		action = strtok(string, idx);
		if(!strcmp(action, "[rcon]", true))
			SendRconCommand(strrest(string, idx));
		if(!strcmp(action, "[kick]", true)) {
			playerid = ReturnUser(strtok(string, idx));
			admin = strtok(string, idx);
			reason = strrest(string, idx);
			if(IsPlayerConnected(playerid)) {
			    GetPlayerName(playerid, pName, sizeof pName);
			    if(strlen(reason) > 0)
					format(string, sizeof string, ">> %s has been kicked by IRC Admin %s (Reason: %s)", pName, admin, reason);
				else
					format(string, sizeof string, ">> %s has been kicked by IRC Admin %s", pName, admin);
				Kick(playerid);
				SendClientMessageToAll(COLOR, string);
				WriteEcho(string);
			}
		}
		if(!strcmp(action, "[msg]", true)) {
		    SendClientMessageToAll(COLOR, strrest(string, idx));
		}
		if(!strcmp(action, "[players]", true)) {
		    new pCount, szPlayers[800], sName[MAX_PLAYER_NAME+8];
		    format(szPlayers, sizeof szPlayers, "[b]Current players (%d):[/b] ", GetPlayerCount());
		    for(new i;i < g_maxPlayers;i++) {
		        if(IsPlayerConnected(i)) {
		            pCount++;
		            GetPlayerName(playerid, pName, MAX_PLAYER_NAME);
		            format(sName, sizeof sName, "[%d] %s,", playerid, pName);
		            strcat(szPlayers, sName);
					if(pCount >= 25) {
					    strcat(szPlayers, " [..]");
					    break;
					}
				}
			}
		}
		if(!strcmp(action, "[pm]", true)) {
		    playerid = ReturnUser(strtok(string, idx));
		    admin = strtok(string, idx);
			format(message, sizeof message, "PM from %s on IRC: %s", admin, strrest(string, idx));
			if(IsPlayerConnected(playerid))
			    SendClientMessage(playerid, COLOR, message);
		}
		if(!strcmp(action, "[ban]", true)) {
			playerid = ReturnUser(strtok(string, idx));
			admin = strtok(string, idx);
			reason = strrest(string, idx);
			if(IsPlayerConnected(playerid)) {
			    GetPlayerName(playerid, pName, sizeof pName);
			    if(strlen(reason) > 0)
					format(string, sizeof string, ">> %s has been banned by IRC Admin %s (Reason: %s)", pName, admin, reason);
				else
					format(string, sizeof string, ">> %s has been banned by IRC Admin %s", pName, admin);
				Ban(playerid);
				SendClientMessageToAll(COLOR, string);
				WriteEcho(string);
			}
		}
		fremove(IRC_ACTION_FILE);
	}
	return 1;
}

public OnFilterScriptExit()
{
	WriteEcho("[color=blue][--] [color=black]Server is currently 5offline.");
	return 1;
}

public OnPlayerConnect(playerid)
{
	new string[256];
	format(string, sizeof string, "[b][%d][/b][color=blue] *** %s has joined the server", playerid, Playername(playerid));
	WriteEcho(string);
	return 1;
}

public OnPlayerDisconnect(playerid, reason)
{
    new string[256], R[20];
	switch(reason) {
	    case 0: R = "Timeout";
	    case 1: R = "Leaving";
	    case 2: R = "Kicked/Banned";
	}
	format(string, sizeof string, "[b][%d][/b][color=grey] *** %s has left the server (%s)", playerid, Playername(playerid), R);
	WriteEcho(string);
	return 1;
}

public OnPlayerDeath(playerid, killerid, reason)
{
	new string[256];
	if(reason == 255)
		format(string, sizeof string, "[color=brown]*** [%d] %s died", playerid, Playername(playerid));
	else
	    format(string, sizeof string, "[color=brown]*** %s [%d] was killed by %s [%d] (%s)", Playername(playerid), playerid, Playername(killerid), killerid, aWeaponNames[reason]);
	WriteEcho(string);
	return 1;
}

public OnPlayerText(playerid, text[])
{
	new string[256];
	format(string, sizeof string, "[color=lightblue][%d] [color=lightblue]%s: [color=black]%s", playerid, Playername(playerid), text);
	WriteEcho(string);
	return 1;
}

WriteEcho(string[])
{
    new File:handle,
        fileStr[256];
	format(fileStr, sizeof fileStr, "%s\r\n", string);
	if(fexist("echo.txt"))
		handle = fopen("echo.txt", io_append);
	else
	    handle = fopen("echo.txt", io_write);
	fwrite(handle, fileStr);
	fclose(handle);
	return 1;
}

Playername(playerid)
{
	new pName[MAX_PLAYER_NAME];
	GetPlayerName(playerid, pName, sizeof pName);
	return pName;
}

IsNumeric(const string[])
{
	for (new i = 0, j = strlen(string); i < j; i++)
	{
		if (string[i] > '9' || string[i] < '0') return 0;
	}
	return 1;
}

ReturnUser(text[], playerid = INVALID_PLAYER_ID)
{
	new pos = 0;
	while (text[pos] < 0x21) // Strip out leading spaces
	{
		if (text[pos] == 0) return INVALID_PLAYER_ID; // No passed text
		pos++;
	}
	new userid = INVALID_PLAYER_ID;
	if (IsNumeric(text[pos])) // Check whole passed string
	{
		// If they have a numeric name you have a problem (although names are checked on id failure)
		userid = strval(text[pos]);
		if (userid >=0 && userid < MAX_PLAYERS)
		{
			if(!IsPlayerConnected(userid))
			{
				/*if (playerid != INVALID_PLAYER_ID)
				{
					SendClientMessage(playerid, 0xFF0000AA, "User not connected");
				}*/
				userid = INVALID_PLAYER_ID;
			}
			else
			{
				return userid; // A player was found
			}
		}
		/*else
		{
			if (playerid != INVALID_PLAYER_ID)
			{
				SendClientMessage(playerid, 0xFF0000AA, "Invalid user ID");
			}
			userid = INVALID_PLAYER_ID;
		}
		return userid;*/
		// Removed for fallthrough code
	}
	// They entered [part of] a name or the id search failed (check names just incase)
	new len = strlen(text[pos]);
	new count = 0;
	new name[MAX_PLAYER_NAME];
	for (new i = 0; i < MAX_PLAYERS; i++)
	{
		if (IsPlayerConnected(i))
		{
			GetPlayerName(i, name, sizeof (name));
			if (strcmp(name, text[pos], true, len) == 0) // Check segment of name
			{
				if (len == strlen(name)) // Exact match
				{
					return i; // Return the exact player on an exact match
					// Otherwise if there are two players:
					// Me and MeYou any time you entered Me it would find both
					// And never be able to return just Me's id
				}
				else // Partial match
				{
					count++;
					userid = i;
				}
			}
		}
	}
	if (count != 1)
	{
		if (playerid != INVALID_PLAYER_ID)
		{
			if (count)
			{
				SendClientMessage(playerid, 0xFF0000AA, "Multiple users found, please narrow earch");
			}
			else
			{
				SendClientMessage(playerid, 0xFF0000AA, "No matching user found");
			}
		}
		userid = INVALID_PLAYER_ID;
	}
	return userid; // INVALID_USER_ID for bad return
}

strtok(const string[], &index)
{
	new length = strlen(string);
	while ((index < length) && (string[index] <= ' '))
	{
		index++;
	}

	new offset = index;
	new result[20];
	while ((index < length) && (string[index] > ' ') && ((index - offset) < (sizeof(result) - 1)))
	{
		result[index - offset] = string[index];
		index++;
	}
	result[index - offset] = EOS;
	return result;
}

strrest(const string[], &index)
{
	new length = strlen(string);
	while ((index < length) && (string[index] <= ' '))
	{
		index++;
	}
	new offset = index;
	new result[64];
	while ((index < length) && ((index - offset) < (sizeof(result) - 1)))
	{
		result[index - offset] = string[index];
		index++;
	}
	result[index - offset] = EOS;
	return result;
}
