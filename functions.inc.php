<?php
/*
 * 		Copyright (c) 2011 Marcus Brown <marcusbrutus@users.sourceforge.net>
 *
 *       This program is free software; you can redistribute it and/or modify
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

global $amp_conf;
// Do we run FreePBX 2.8?
if(file_exists($amp_conf['AMPWEBROOT']."/admin/extensions.class.php")) {
  // Yes, then include these files
  //I need to know if we really need these files. Questionable.
  require_once("functions.inc.php");
  require_once("extensions.class.php");
}

function googlevoice_hookGet_config($engine) {
	global $ext;	
	if (!method_exists($ext, 'ext_senddtmf')) {		
		class ext_senddtmf extends extension {
			var $digits;
			function ext_senddtmf($digits) {
				$this->digits = $digits;
			} 
			function output() {
				return 'SendDTMF('.$this->digits.')';
			}
		}
	}
}

class googlevoice_conf {

	function get_filename() {

		$files = array(
			'jabber.conf',
			'gtalk.conf',
			'extensions_additional.conf'
			);

		return $files;
	}
	
	function generateConf($file) {
		global $version;

		switch ($file) {
			case 'jabber.conf':
				return $this->generate_jabber_conf($version);
				break;
			case 'gtalk.conf':
				return $this->generate_gtalk_conf($version);
				break;
			case 'extensions_additional.conf':
				return $this->generate_extensions_conf($version);
				break;
		}
	}

	function generate_jabber_conf($ast_version) {
		global $astman;

/*
		$this->use_warning_banner = true;
*/
		$output = "[general]\ndebug=no\nautoprune=no\nautoregister=yes\n\n";
		$accounts = googlevoice_list();
		foreach ($accounts as $account) {
			$phonenum = $account[0];
                        $full_address = explode('@', $account[1]);
                        if(array_key_exists(1, $full_address)) {
                            $username = str_replace('.', '', $account[1]);
                            $username = str_replace('@', '', $username);
                            $address  = $full_address[0].'@'.$full_address[1];
                        } else {
                            $username = $account[1];
                            $address  = $account[1].'@gmail.com';
                        }			
			$password = $account[2];
			$output .= "[".$username."]\ntype=client\nserverhost=talk.google.com\n";
			$output .= "username=".$address."/Talk\nsecret=".$password."\n";
			$output .= "port=5222\npriority=1\nusetls=yes\nusesasl=yes\nstatus=Available\n";
			$output .= "statusmessage=\"No Information Available\"\n";
			$output .= "timeout=100\nkeepalive=yes\n\n";
		}

		$response = $astman->send_request('Command', array('Command' => 'module unload res_jabber.so'));
		$response = $astman->send_request('Command', array('Command' => 'module load res_jabber.so'));

		return $output;
	}

	function generate_gtalk_conf($ast_version) {
		global $astman;
/*
		$this->use_warning_banner = true;
*/
		$output  = "[general]\nallowguest=yes\ncontext=googlein\n\n";
		$output .= "[guest]\ndisallow=all\nallow=ulaw\nconnection=asterisk\ncontext=googlein\n\n";

		$response = $astman->send_request('Command', array('Command' => 'module unload chan_gtalk.so'));
		$response = $astman->send_request('Command', array('Command' => 'module load chan_gtalk.so'));
		
		return $output;
	}
	
	function generate_extensions_conf($ast_version) {
		global $ext;

		$accounts = googlevoice_list();
		foreach ($accounts as $account) {
			$incontext = "googlein";
			$phonenum = $account[0];
                        $full_address = explode('@', $account[1]);
                        if(array_key_exists(1, $full_address)) {
                            $username = str_replace('.', '', $account[1]);
                            $username = str_replace('@', '', $username);
                            $address  = $full_address[0].'@'.$full_address[1];
                        } else {
                            $username = $account[1];
                            $address  = $account[1].'@gmail.com';
                        }
			$outcontext = 'googlevoice-'.$username;

			/* INBOUND CONTEXT */
			$ext->add($incontext, $address, '', new ext_noop('Receiving GoogleVoice call'));
			$ext->add($incontext, $address, '', new ext_setvar( 'CALLERID(name)', '${CUT(CALLERID(name),@,1)}' ) );
			$ext->add($incontext, $address, '', new ext_gotoif( '$["${CALLERID(name):0:2}" != "+1"]', 'notrim' ) );
			$ext->add($incontext, $address, '', new ext_setvar('CALLERID(name)', '${CALLERID(name):2}') );
			$ext->add($incontext, $address, 'notrim', new ext_setvar('CALLERID(number)', '${CALLERID(name)}') );
			if (true) {
				$ext->add($incontext, $address, '', new ext_answer('') );
				$ext->add($incontext, $address, '', new ext_wait('1') );
				$ext->add($incontext, $address, '', new ext_senddtmf('1') );
			}

			$ext->add($incontext, $address, '', new ext_goto('1',$phonenum,'from-trunk') );
			if (true) {
				$ext->add($incontext, 'h', '', new ext_hangup('') );
			}

			$ext->add('from-google-voice', $username, '', new ext_goto('1', $phonenum, 'from-trunk') );

			/* OUTBOUND CONTEXT */
			$ext->add($outcontext, '_X.', '', new ext_dial('Gtalk/'.$username.'/+${EXTEN}@voice.google.com') );
			$ext->add($outcontext, '_X.', '', new ext_noop('GoogleVoice Call to ${EXTEN} failed') );
			$ext->add($outcontext, 'h', '', new ext_hangup('') );
			
		}
		$ext->add('from-google-voice', 'h', '', new ext_hangup('') );

		needreload();

		return $ext->generateConf();
	}
}

function googlevoice_list() {
	global $db;
	$sql = "SELECT phonenum, username, password FROM googlevoice ORDER BY username";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		$results = array();
	}

	foreach($results as $result){
		$account[] = array($result[0],$result[1],$result[2]);
	}
	if (isset($account)) {
		return $account;
	}
	return array();
}

function googlevoice_add($phonenum, $username, $password,$add_trunk,$add_routes) {
    if ( ($phonenum == "") || ($username == "") || ($password == "") ){
		return array();
    }
    $result = googlevoice_getnum($phonenum);
    if ($result) {
		return false;
	}

	$query = "INSERT INTO googlevoice (phonenum, username, password)
         	VALUES ('".$phonenum."', '".$username."', '".$password."')";
	$result = sql($query);
	
	if (DB::IsError($result)) {
		return false;
	}
        
        $full_address = explode('@', $username);
        if(array_key_exists(1, $full_address)) {
            $username = str_replace('.', '', $username);
            $username = str_replace('@', '', $username);
        } else {
            $username = $username;
        }

	$trunknum = false;
	if ($add_trunk) {
		/* TRUNK */
		$trunknum = core_trunks_add('custom', 'local/$OUTNUM$@googlevoice-'.$username, '', '', $phonenum, '', 'notneeded', '', '', 'off', '', 'off', 'GV_'.$phonenum, '');
		dbug_write('Trunknum = '.$trunknum.'
','');
                $patterns[] = array(
                        'prepend_digits' 		=> '1',
                        'match_pattern_prefix' 	=> '',
                        'match_pattern_pass' 	=> 'NXXNXXXXXX',
                        'match_cid' 			=> '',
                );
                core_trunks_update_dialrules($trunknum, $patterns);
		if ($add_routes) {
			/* OUTBOUND ROUTE */
			dbug_write('Adding routes
','');
			$trunkroutes = core_trunks_gettrunkroutes($trunknum);
			if( ! empty($trunkroutes) ) {
				dbug_write('Trunk-routes already exist?!?!?!?!','');
				return;
			}
			$dialpattern[] = array(
				'prepend_digits' 		=> '1',
				'match_pattern_prefix' 	=> '',
				'match_pattern_pass' 	=> 'NXXNXXXXXX',
				'match_cid' 			=> '',
			);
			$result = core_routing_addbyid($username, '', '', '', '', '', 'default', '', $dialpattern, array($trunknum));

/*
			// INBOUND ROUTE
			$did[] = array(
				'cidnum' 				=> '',
				'extension' 			=> $phonenum,
				'destination' 			=> '',
				'privacyman' 			=> '',
				'pmmaxretries' 			=> '',
				'pmminlength' 			=> '',
				'alertinfo' 			=> '',
				'ringing' 				=> '',
				'mohclass' 				=> 'default',
				'description' 			=> $username,
				'grppre' 				=> '',
				'delay_answer' 			=> '',
				'pricid' 				=> '',
			);
			$result = core_did_add($did)
*/
		}
	}
	
	return true;
}

function googlevoice_getuser($username) {
	global $db;

    if ($username == "") {
		return false;
    }

	$sql = "SELECT phonenum, username, password FROM googlevoice
         	WHERE username = ?";
    $params = $username;
	$result = $db->getAssoc($sql,false,$params);
	if (DB::IsError($result) || ! is_array($result)) {
		return false;
	}
	
	if (is_array($result) && ($result[$phonenum] == '') ) {
		return false;
	}

	return $result;
}

function googlevoice_getnum($phonenum) {
	global $db;

    if ($phonenum == "") {
		return false;
    }

	$sql = "SELECT phonenum, username, password FROM googlevoice
         	WHERE phonenum = ?";
    $params = $phonenum;
	$result = $db->getAssoc($sql,false,$params);
	if (DB::IsError($result) || ! is_array($result)) {
		return false;
	}
	
	return $result;
}

function googlevoice_del($phonenum) {
    if (empty($phonenum)) {
		return false;
    }

	$username = false;

	$result = googlevoice_getnum($phonenum);
	if ($result) {
		$username = $result[$phonenum][0];
	} else {
		return false;
	}

	$query = "DELETE FROM googlevoice
         	WHERE phonenum = '".$phonenum."'";
	$result = sql($query);
	if (DB::IsError($result)) {
		return false;
	}

	$trunknum = googlevoice_trunkID($phonenum);
	if ($username && $trunknum) {
		core_routing_trunk_delbyid($trunknum);	// Not sure if this is necessary
		googlevoice_del_routes($trunknum,$username);
		core_trunks_del($trunknum);
                core_trunks_delete_dialrules($trunknum);
	}
	return true;
}

function googlevoice_update($phonenum,$username,$password) {
	global $db;

    if (empty($phonenum)) {
		return array();
    }

	$sql = "UPDATE googlevoice SET username = '".$username."', password = '".$password."' WHERE phonenum = '".$phonenum."'";
    $params = array($username, $password, $phonenum);
	$result = $db->query($sql);
	if (DB::IsError($result) || empty($result)) {
		return false;
	}

	return true;
}

function googlevoice_trunkID($phonenum) {
	$trunknum = '';

	foreach(core_trunks_list() as $trunk) {
		$trunknum = ltrim($trunk[0],"OUT_");
		$details = core_trunks_getDetails($trunknum);
		if( ($details['tech'] == 'custom') && ($details['name'] = 'GV_'.$phonenum) ) {
			return $trunknum;
		}
	}

	return false;
}

function googlevoice_del_routes($trunkID,$username) {
	foreach(core_routing_list() as $routeID) {
		if( $routeID['name'] == $username ) {
			core_routing_delbyid($routeID['route_id']);
		}
	}

	return;
