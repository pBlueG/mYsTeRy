mYsTeRy IRC PHP Bot
=======

The following short instruction will show you how to configure the IRC bot. Once the bot is configured, you can run the bot via the enclosed run_(win32.bat|unix.ssh) files. You'll need PHP installed to run the bot.

### Directory hierarchy

- mYsTeRy-v2.1a/
	- /classes/
	- /configuration/
		- /bots/
		- /networks/
	- /database/
	- /interfaces/
	- /logs/
	- /pawn/
	- /plugins/
	- /ssl/
       	


### General Configuration:

> /configuration/general.cfg

	[General]
	SQLite 		= on 				# requires the php_sqlite3.(dll|so) module
	MySQL 		= off 				# see mysql.ini for host/user/db config
	AdminPass 	= testicle 			# specifies the password to get recognised as bot admin -> /msg BotNickname login password
	Admins[] 	= "BlueG!i.am@out.of.reach" 	# adding your ident allows you to get recognised as administrator without login
	;Admins[] 	= "your!ident@hostname.com" 	# format: nickname!ident@hostname
	Logging 	= on 				# specifies whether all channel messages get logged
	Sleep 		= 40000				# specifies the sleep time of the bot = 1mio/sleeptime => ticks per second (1000000/40000 = 25 ticks)
	Prefix 		= "!" 				# command prefix
	Ping 		= 45 				# specifies the bot ping timeout check in seconds
	# plugins loaded on startup, use !load/!unload when the bot is connected 	
	Plugins[] 	= ctcp
	Plugins[] 	= dcc
	Plugins[]	= "auto_perform"



### Network Configuration:

> /configuration/networks/

How to define a new network (required file format):

	[Network]
	Name = Networkname		# Case sensitive string
	SSL = false				# boolean (alt)
	SSL_CRT = ""			# case sensitive string (alt)
	Port = 6667				# integer
	Servers[] = "irc.network.address"	# Array set of strings

The filename does not matter.



### Bot Configuration:

> /configuration/bots/

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



### Commands:

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




### Admin authentication:

You will need admin privileges to control the bot and run commands. There
are two ways to identify as administrator:

	1. Open configuration/general.cfg and add your irc ident to the 'Admins[]' array set.

	2. If you don't have a static irc ident, edit the password for the variable 'AdminPass' in configuration/general.cfg.
	   Now start the bot and execute the following command:

		 /msg [BotNickname] login [password]

	   The bot will let you know, whether it worked or not.




### PHP extensions

The bot requires various php libraries to work properly. The following list
will show you all required extensions in your PHP configuration file (php.ini).

	php_sockets.(so|dll)
	php_sqlite3.(so|dll)
	php_mysql.(so|dll)
	php_pdo_mysql.(so|dll)
	php_pdo_sqlite.(so|dll)
	php_openssl.(so|dll)



### Plugin API

