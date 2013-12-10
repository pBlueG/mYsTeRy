<?php

/**
 * Interface for IRC raw events
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */


interface RawEvents {
	// I did only add codes for events I needed 
	// more raw codes with a full documentation @ RFC http://www.ietf.org/rfc/rfc1459.txt
	const WELCOME_MSG = 		'001'; // server welcome message
	const CONNECTION_REFUSED = 	'005'; // server is full, use alternative 
	const WHOIS_USER = 		'311'; // whois ident reply
	const WHOIS_SERVER = 		'312'; // whois server reply
	const WHOWAS_USER = 		'314'; // reply to whowas request
	const WHOIS_END = 		'318'; // end of whois
	const WHOIS_CHANNELS = 		'319'; // whois channels reply
	const NAMES_LIST = 		'353'; // names list
	const END_OF_NAMES_LIST = 	'366'; // end of names list
	const MOTD = 			'375'; // message of the day
	const END_MOTD = 		'376'; // end of motd
	const USERS_REPLY = 		'393'; // reply to the USERS command
	const INVALID_NICKNAME =	'432'; // nickname is not valid
	const NICKNAME_ALREADY_IN_USE = '433'; // nickname is already in use
	const INVITE_ONLY_CHAN = 	'473'; // requires invitation to join channel
	const BANNED_FROM_CHAN = 	'474'; // banned from channel
	const INVALID_CHAN_KEY = 	'475'; // invalid channel key
	const NO_CHAN_PRIVILEGES = 	'482'; // requires operator to execute command
}

?>