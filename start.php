<?php

// Version
define('VERSION', 'v2.1a');

// Timezone
date_default_timezone_set('Europe/Amsterdam');

// Enable error reporting
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

// Classes
require_once('interfaces/RawEvents.php');
require_once('interfaces/Modes.php');
require_once('interfaces/ColorCodes.php');
require_once('classes/singleton.class.php');
require_once('classes/log.class.php');
set_error_handler('Log::DebugHandler', E_ALL);
require_once('classes/privileges.class.php');
require_once('classes/rawcommands.class.php');
require_once('classes/misc.class.php');
require_once('classes/main.class.php');
require_once('classes/bot.class.php');
require_once('classes/socket.class.php');
require_once('classes/database.class.php');
require_once('classes/plugin.class.php');
require_once('classes/timer.class.php');
require_once('classes/commandhandler.class.php');

// Includes
include_once('parse.php');

// init various classes
Database::getInstance();
CommandHandler::getInstance();
Timer::getInstance();

echo 'Initializing mYsTeRy '.VERSION.' ...'. PHP_EOL;

$Settings = parse('configuration/general.ini', 'General', false);
$Bots = parse('configuration/bots/', 'Bot', true);
$Network = parse('configuration/networks/', 'Network', true, 'Name');
$iSleep = $Settings['Sleep'];

$Controller = Main::getInstance();
$Controller->_registerSettings($Settings);
$Controller->_registerNetwork($Network);
$Controller->_registerAdmins();
$Controller->_initBots($Bots);
$Controller->_initPlugins();

echo '>> Global settings have been registered. Bot(s) will connect now.' . PHP_EOL;


while(true) {
	$Controller->_Run();
	usleep($iSleep);
}
?>