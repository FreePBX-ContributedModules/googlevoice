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

$dispnum = 'googlevoice';

$engineinfo = engine_getinfo();
$astver =  $engineinfo['version'];
$ast_lt_18 = version_compare($astver, '1.8', 'lt');

	if ($ast_lt_18) {
		?>Google Voice module requires Asterisk 1.8<?php
        }

//the account we are currently displaying
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';

isset($_REQUEST['phonenum'])?$phonenum=$_REQUEST['phonenum']:$phonenum='';
isset($_REQUEST['username'])?$username=$_REQUEST['username']:$username='';
isset($_REQUEST['password'])?$password=$_REQUEST['password']:$password='';
isset($_REQUEST['agree2terms'])?$agree2terms = $_REQUEST['agree2terms']:$agree2terms='';
isset($_REQUEST['add_trunk'])?$add_trunk = $_REQUEST['add_trunk']:$add_trunk='';
isset($_REQUEST['add_routes'])?$add_routes = $_REQUEST['add_routes']:$add_routes='';


if (isset($_REQUEST["gvlist"])) {
	$gvlist = explode("\n",$_REQUEST["gvlist"]);
	if (!$gvlist) {
                $gvlist = null;
        }

        foreach (array_keys($gvlist) as $key) {
                //trim it
                $gvlist[$key] = trim($gvlist[$key]);

                // remove invalid chars
                $gvlist[$key] = preg_replace("/[^0-9#*]/", "", $gvlist[$key]);

                if ($gvlist[$key] == $extdisplay.'#')
                        $gvlist[$key] = rtrim($gvlist[$key],'#');

                // remove blanks
                if ($gvlist[$key] == "") unset($gvlist[$key]);
        }

        // check for duplicates, and re-sequence
        $gvlist = array_values(array_unique($gvlist));

}

// do if we are submitting a form
if(isset($_POST['action'])){
                //add account
                if ($action == 'addGV') {
					$phonenum_match = googlevoice_getnum($phonenum);
					if (empty($agree2terms)) {
						echo "<script>javascript:alert('"._("You must acknowledge that you have agreed to the Google Voice Terms of Service before adding an account.")."');</script>";
					} else
					if ($phonenum_match) {
							echo "<script>javascript:alert('"._("Phone number already exists.")."');</script>";
					} else {
						$added = googlevoice_add($phonenum,$username,$password,$add_trunk,$add_routes);
						if ($added) {
							needreload();
							redirect_standard();
						} else {
							redirect_standard();
						}
					}
                }

                if ($action == 'edtGV') {
                        googlevoice_update($phonenum,$username,$password);
                        needreload();
                        redirect_standard('extdisplay');
                }
}

$gvresults = googlevoice_list();

?>
</div>

<div class="rnav"><ul>
    <li><a class="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add GoogleVoice Account")?></a></li>
<?php
if (is_array($gvresults)) {
        foreach ($gvresults as $gvresult) {
                echo "<li><a class=\"".($extdisplay==$gvresult[0] ? 'current':'')."\" href=\"config.php?display=".urlencode($dispnum)."&extdisplay=".urlencode($gvresult[0])."\">".$gvresult[0]." ({$gvresult[1]})</a></li>";
        }
}
?>
</ul></div>

<div class="content">
<?php
if ($action == 'delGV') {
	$result = googlevoice_del($extdisplay);
	if ($result) {
		$extdisplay = '';
		redirect_standard();
	} else {
		echo "<script>javascript:alert('"._("Error deleting GoogleVoice account ").$extdisplay."');</script>";
		redirect_standard('extdisplay');
	}
} else {
        if ($extdisplay) {
				$phonenum = $extdisplay;
                $thisgv = googlevoice_getnum($extdisplay);
                if ($thisgv) {
					$username = $thisgv[$phonenum][0];
					$password = $thisgv[$phonenum][1];
					unset($thisgv);

					$delButton = "
							<form name=delete action=\"{$_SERVER['PHP_SELF']}\" method=\"POST\">
									<input type=\"hidden\" name=\"display\" value=\"{$dispnum}\">
									<input type=\"hidden\" name=\"phonenum\" value=\"".$phonenum."\">
									<a  href=\"config.php?type=setup&display=googlevoice&extdisplay=".$phonenum."&action=delGV\"
										id=\"del\">
										<span>
											<img width=\"16\" height=\"16\" border=\"0\" title=\"Delete Extension 3733364\" alt=\"\" src=\"images/user_delete.png\"/>
											&nbsp;Delete Account
										</span>
									</a>
							</form>";

					echo "<h2>"._("GoogleVoice Account").": ".$phonenum."</h2>";

					echo "<p>".$delButton."</p>";
				} else {
					echo "<h2>"._("GoogleVoice Account").": ".$phonenum." does not exist.</h2>";	
				}
        } else {
				$phonenum = '';
                $username = '';
                $password = '';

                if (!empty($conflict_url)) {
                        echo "<h5>"._("Conflicting Accounts")."</h5>";
                        echo implode('<br .>',$conflict_url);
                }

                echo "<h2>"._("Add GoogleVoice Account")."</h2>";

				$helptext = _('Add your <a href="www.google.com/voice/about">Google Voice</a> account(s) so that it can be used as a trunk.<br><a href="www.google.com/voice/about">Google Voice</a> is only available for people located in continental North America.<br>You must read and agree to the <a href="http://www.google.com/accounts/TOS">Google Voice Terms of Service</a> prior to use.<br>This module may be in violation of those terms.<br><br>Note that this module and it\'s author are not affiliated with <a href="http://www.google.com">Google</a>.');
				echo "<p>".$helptext."</p>\n";
        }
        ?>
			<form name="editGV" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkGV(editGV);">
			<input type="hidden" name="display" value="<?php echo $dispnum?>">
			<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edtGV' : 'addGV'); ?>">
			<table>
				<tr>
					<td colspan="2">
						<h5><?php  echo ($extdisplay ? _("Edit Account") : _("Add Account")) ?><hr></h5>
					</td>
				</tr>
				<tr>
<?php
        if ($extdisplay) {
?>
					<input size="30" type="hidden" name="phonenum" value="<?php  echo $extdisplay; ?>">
<?php   } else { ?>
					<td>
						<a href="#" class="info"><?php echo _("Phone number")?>:<span><?php echo _("The 10 digit phone number for your Google Voice account.")?></span></a>
					</td>
					<td>
						<input size="30" maxlength="30" type="text" name="phonenum" value="<?php echo (isset($phonenum) ? $phonenum : ''); ?>">
					</td>
<?php   } ?>
				</tr>

				<tr>
					<td><a href="#" class="info"><?php echo _("Username")?>:<span><?php echo _("Your Google Voice username without the '@gmail.com' portion")?></span></a></td>
					<td><input size="30" maxlength="30" type="text" name="username" value="<?php echo (isset($username) ? $username : ''); ?>"></td>
				</tr>

				<tr>
					<td>
						<a href="#" class="info"><?php echo _("Password")?>:<span><?php echo _("The password for your Google Voice account.")?></span></a>
					</td>
					<td>
						<input size="30" maxlength="30" type="text" name="password" value="<?php echo (isset($password) ? $password : ''); ?>"
					</td>
				</tr>
<?php	if ( empty($extdisplay) ) {	?>
				<tr>
				<td><a href="#" class="info"><?php echo _("Add trunk")?><span> <?php echo _("Automatically add a Google Voice trunk for this account.") ?></span></a>:</td>
					<td>
						<input type="checkbox" name="add_trunk" value="CHECKED" <?php echo $add_trunk ?> />
					</td>
				</tr>
				<tr>
				<td><a href="#" class="info"><?php echo _("Add routes")?><span> <?php echo _("Automatically add inbound and outbound routes for this account.") ?></span></a>:</td>
					<td>
						<input type="checkbox" name="add_routes" value="CHECKED" <?php echo $add_routes ?> />
					</td>
				</tr>
				<tr>
				<td><a href="#" class="info"><?php echo _("Agree to TOS")?><span> <?php echo _("You agree to the Terms of Service as specified by Google.") ?></span></a>:</td>
					<td>
						<input type="checkbox" name="agree2terms" value="CHECKED" <?php echo $agree2terms ?> />
					</td>
					</tr>
<?php	}	?>
				<tr>
					<td colspan="2">
						<br><h6><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>"></h6>
					</td>
				</tr>
			</table>
			<table>
				<tr>
					<td colspan="4">
						<br>
						<hr>
						<br>
						This module has been produced by <a href="#" class="info"><?php echo _("the author")?><span> <?php echo _("Author: Marcus Brown. Email: marcusbrutus@users.sourceforge.net") ?></span></a>
						without funding or sponsorship, and for the benefit of all.<br>Select one of the amounts below if you would like to thank or assist <a href="#" class="info"><?php echo _("the author")?><span> <?php echo _("Author: Marcus Brown. Email: marcusbrutus@users.sourceforge.net") ?></span></a> by donating via Paypal. 
					</td>
				</tr>
				<tr>
					<td colspan='1' align='center'>
						<h3>$5</h3>
						<div style="margin-top:5px;display:block;margin-bottom:5px;margin-right:auto;zoom:1;text-align:center"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=marcusbrutus@users.sourceforge.net&amp;lc=AU&amp;item_name=googlevoice&amp;item_number=googlevoice5&amp;amount=5.00&amp;currency_code=AUD&amp;bn=PP-DonationsBF:btn_donate_LG.gif:NonHosted" imageanchor="1" rel="nofollow" target="_blank"><img src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_AU/i/btn/btn_donate_LG.gif"></a></div>
					</td>
					<td colspan='1' align='center'>
						<h3>$10</h3>
						<div style="margin-top:5px;display:block;margin-bottom:5px;margin-right:auto;zoom:1;text-align:center"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=marcusbrutus@users.sourceforge.net&amp;lc=AU&amp;item_name=googlevoice&amp;item_number=googlevoice10&amp;amount=10.00&amp;currency_code=AUD&amp;bn=PP-DonationsBF:btn_donate_LG.gif:NonHosted" imageanchor="1" rel="nofollow" target="_blank"><img src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_AU/i/btn/btn_donate_LG.gif"></a></div>
					</td>
					<td colspan='1' align='center'>
						<h3>$25</h3>
						<div style="margin-top:5px;display:block;margin-bottom:5px;margin-right:auto;zoom:1;text-align:center"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=marcusbrutus@users.sourceforge.net&amp;lc=AU&amp;item_name=googlevoice&amp;item_number=googlevoice25&amp;amount=25.00&amp;currency_code=AUD&amp;bn=PP-DonationsBF:btn_donate_LG.gif:NonHosted" imageanchor="1" rel="nofollow" target="_blank"><img src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_AU/i/btn/btn_donate_LG.gif"></a></div>
					</td>
					<td colspan='1' align='center'>
						<h3>$50</h3>
						<div style="margin-top:5px;display:block;margin-bottom:5px;margin-right:auto;zoom:1;text-align:center"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=marcusbrutus@users.sourceforge.net&amp;lc=AU&amp;item_name=googlevoice&amp;item_number=googlevoice50&amp;amount=50.00&amp;currency_code=AUD&amp;bn=PP-DonationsBF:btn_donate_LG.gif:NonHosted" imageanchor="1" rel="nofollow" target="_blank"><img src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_AU/i/btn/btn_donate_LG.gif"></a></div>
					</td>
				</tr>
				
			</table>
		</form>
<?php
                }

?>
<script language="javascript">
<!--

function checkGV(theForm) {
	var bad = "false";

        if (!isNumeric(theForm.phonenum.value)) {
			<?php echo "alert('"._("Phone number invalid")."')"?>;
            bad = "true";
		}

        if (!isAlphanumeric(theForm.username.value)) {
			<?php echo "alert('"._("Username must not be blank")."')"?>;
            bad = "true";
		}

        if (isEmpty(theForm.password.value)) {
			<?php echo "alert('"._("Password must not be blank")."')"?>;
            bad = "true";
		}

		if (bad == "false") {
			theForm.submit();
		}
}

function openPaypal(theForm) {
	window.open('page'.theForm.paypal.value.'.html','_newtab');
}

//-->
</script>
