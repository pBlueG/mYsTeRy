mYsTeRy PHP Bot
=======

Once you have configured the bot, execute run_win32.bat (windows) or run_unix.ssh (linux).


** -- General Configuration: **

configuration/general.cfg



** -- Network Configuration: **

configuration/networks/

How to define a new network:

Format
	[Network]
	Name = Networkname			# Case sensitive string
	SSL = false				# boolean (alt)
	SSL_CRT = ""				# case sensitive string (alt)
	Port = 6667				# integer
	Servers[] = "irc.network.address"	# Array set of strings



** -- Bot Configuration: **

configuration/bots/

How to create a new bot:

Create a new config (.cfg) file and use the following structure:

	[Bot]
	Nick = mYsTeRy				# String
	AltNick = mYsTeRy_			# String
	Username = mYsTeRy			# String
	Realname = mYsTeRy			# String
	Password = test				# String
	Child = false				# boolean	
	Quit = Goodbye				# String
	Channels[] = #mYsTeRy			# Array set of string  	
	Network = FoCoIRC			# Specify the network name here, the bot will attempt
						# to join this network. (see network config above)
	Commands[] = 				# Array set of strings

PS: The filename does NOT matter, as the script will try to load all files
within the bots/ folder.	



** -- Commands: **

The bot has built in commands to fully control the bot. (admin privileges required)

	!join (#channel)			# joins the given channel
	!part (#channel)			# parts the given channel
	!quit (quit message)			# disconnects the bot
	!! (php code)				# evaluates the given php code
	!addcmd (command) (privilege) (phpcode) # creates a new command
	!delcmd (command)			# deletes a command
	!cmds					# displays a list of all commands (via NOTICE)
	!load (plugin/module)			# loads a plugin from plugins/
	!unload (plugin/module)			# unloads a plugin
	!plugins				# shows a list of all plugins
	!ident					# returns your ident
	!mem					# displays current memory usage
	!uptime					# displays uptime

These commands



** -- Admin authentication: **

You will need admin privileges to control the bot and run commands. There
are two ways to identify as administrator:

	1. Open configuration/general.cfg and add your irc ident to the 'Admins[]' array set.

	2. If you don't have a static irc ident, edit the password for the variable 'AdminPass' in configuration/general.cfg.
	   Now start the bot and execute the following command:

		 /msg [BotNickname] login [password]

	   The bot will let you know, whether it worked or not.



