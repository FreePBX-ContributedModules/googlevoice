<?php
/*
 * 		Copyright (c) 2011 Marcus Brown <marcusbrutus@users.sourceforge.net>
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

if (false) {
_("Uninstalling Google Voice trunks");
}

global $db;
global $amp_conf;
global $asterisk_conf;

if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

if($amp_conf["AMPDBENGINE"] == "mysql")  {
	$sql = "
DROP TABLE IF EXISTS `googlevoice`";
	$check = $db->query($sql);
	if(DB::IsError($check)) {
		die_freepbx(_("Can not drop googlevoice table"));
	} else {
		out("Database table for Google Voice removed");
	}
} else {
	die_freepbx(_("Unknown database type: ".$amp_conf["AMPDBENGINE"]));
}

// This is NOT the right way ... but easiest way to prove the theory works ;)
//exec("/bin/rm -f ".get_var($agi, "ASTETCDIR")."/jabber.conf");

?>

