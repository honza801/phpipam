<?php

/**
 * Script to display usermod result
 *************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');
require( dirname(__FILE__) . "/../../../functions/adLDAP/src/adLDAP.php");

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fetch server
$server = $Admin->fetch_object("usersAuthMethod", "id", $_POST['server']);
$server!==false ? : $Result->show("danger", _("Invalid server ID"), true);

//parse parameters
$params = json_decode($server->params);

//no login parameters
if(strlen(@$params->adminUsername)==0 || strlen(@$params->adminPassword)==0)	{ $Result->show("danger", _("Missing credentials"), true); }
//at least 2 chars
if(strlen($_POST['dname'])<2) 													{ $Result->show("danger", _('Please enter at least 2 characters'), true); }



//open connection
try {
	//AD
	$adldap = new adLDAP(array( 'base_dn'=>$params->base_dn, 'account_suffix'=>$params->account_suffix,
								'domain_controllers'=>explode(";",$params->domain_controllers), 'use_ssl'=>$params->use_ssl,
								'use_tls'=> $params->use_tls, 'ad_port'=> $params->ad_port
								));

	//first check connection
	if(@fsockopen($adldap->selected_controller, $params->ad_port, $errno, $errstr, 2)==false)	{ $Result->show("danger",  "Cannot connect to controller $adldap->selected_controller<br>$errstr ($errno)", true); }

	//try to login with higher credentials for search
	$authUser = $adldap->authenticate($params->adminUsername, $params->adminPassword);
	if ($authUser == false) {
		$Result->show("danger", _("Invalid credentials"), true);
	}

	// set OpenLDAP flag
	if($server->type == "LDAP") { $adldap->setUseOpenLDAP(true); }

	//search for domain user!
	$userinfo = $adldap->user()->info("$_POST[dname]*", array("*"));

	//echo $adldap->getLastError();
}
catch (adLDAPException $e) {
	$Result->show("danger", $e, true);
}


//check for found
if(!isset($userinfo['count'])) {
	print "<div class='alert alert-info alert-block'>";
	print _('No users found')."!<hr>";
	print _('Possible reasons').":";
	print "<ul>";
	print "<li>"._('Username not existing')."</li>";
	print "<li>"._('Invalid baseDN setting for AD')."</li>";
	print "<li>"._('AD account does not have enough privileges for search')."</li>";
	print "</div>";
} else {
	print _(" Following users were found").": ($userinfo[count]):<hr>";

	print "<table class='table table-striped'>";

	unset($userinfo['count']);
	if(sizeof(@$userinfo)>0 && isset($userinfo)) {
	 	foreach($userinfo as $u) {
			print "<tr>";
			print "	<td>".$u['displayname'][0];
			print "</td>";
			print "	<td>".$u['samaccountname'][0]."</td>";
			print "	<td>".$u['mail'][0]."</td>";
			//actions
			print " <td style='width:10px;'>";
			print "		<a href='' class='btn btn-sm btn-default btn-success userselect' data-uname='".$u['displayname'][0]."' data-username='".$u['samaccountname'][0]."' data-email='".$u['mail'][0]."'>"._('Select')."</a>";
			print "	</td>";
			print "</tr>";
		}
	}
	print "</table>";
}

?>