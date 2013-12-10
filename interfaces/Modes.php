<?php

/**
 * Interface for IRC raw modes
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

interface Modes {
	const SET_MODERATED	= 'm';
	const SET_INVITE_ONLY	= 'i';
	const SET_LIMIT 	= 'l';
	const SET_KEY		= 'k';
	const SET_PRIVATE	= 'p';
	const SET_SECRET	= 's';
	const VOICE		= 'v';
	const HALFOP		= 'h';
	const OPERATOR		= 'o';
	const SUPER_OPERATOR	= 'a';
	const OWNER		= 'q';
}

?>