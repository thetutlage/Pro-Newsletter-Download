<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce
 * InSite Contribution :- Andy Charles
 * 
**/

require_once("config.php");
require_once("php/database.php");
require_once("php/functions.php");
$db = db_connect();

require_once("php/class.newsletter.php");
$newsletter = new newsletter();
$newsletter -> init();

if(isset($_REQUEST) && isset($_REQUEST['t'])){
	echo '<p>add this command to cron, make it run every 20 minutes or so. Contact your hosting provider for instructions on how to setup cron for your hosting account (eg: from cPanel). </p>';
	echo '<pre>' ."\n\n";
	echo "php ".getcwd()."/cron_bounce.php \n";
	echo '</pre>';
	exit;
}

$now = time();
@set_time_limit(0);



// check for bounces
$newsletter->check_bounces($db);
echo 'done';
exit;