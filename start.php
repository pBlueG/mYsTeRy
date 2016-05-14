<?php

define('REVISION', 'v2.1a');

// timezone
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
require_once('classes/channel_priv.class.php');
require_once('classes/commands.class.php');
require_once('classes/misc.class.php');
require_once('classes/main.class.php');
require_once('classes/bot.class.php');
require_once('classes/socket.class.php');
require_once('classes/database.class.php');
require_once('classes/plugin.class.php');
require_once('classes/ini.class.php' );
require_once('classes/timer.class.php');
require_once('classes/commandhandler.class.php');

echo "Initializing mYsTeRy ".REVISION." ...". PHP_EOL;

$INI = Ini::getInstance();
$g_aConfig = array(
	'General' 	=> $INI->_getConfig('configuration/general.ini', 'General'),
	'Bots' 		=> $INI->_getArrayConfig('bots', 'Bot'),
	'Networks' 	=> $INI->_getArrayConfig('networks', 'Network'),
);
CommandHandler::getInstance();
Database::getInstance();
Timer::getInstance();
$g_aNetworks = $g_aConfig['Networks'];
unset($g_aConfig['Networks']);

foreach($g_aNetworks as $key => $value) {
	$g_aNetworks[$g_aNetworks[$key]['Name']] = $g_aNetworks[$key];
	unset($g_aNetworks[$key]);
}

foreach($g_aConfig['General']['Admins'] as $sAdmin) {
	if(!Privileges::AddBotAdmin($sAdmin))
		Log::Error('>> Invalid ident format -> '.$sAdmin);
}

echo "General & Bot configuration has been loaded.". PHP_EOL;

$gHandler = Main::getInstance($g_aConfig, $g_aNetworks);
$iSleep = $g_aConfig['General']['Sleep'];

echo "All required modules have been successfully registered.". PHP_EOL;

while(true) {
	$gHandler->_Run();
	usleep($iSleep);
}
?>