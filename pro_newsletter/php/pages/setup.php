<div id="setupdiv">
	<h1>Initial Setup Screen</h1>
<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/
/* Listing to songs.... */
/* Loves charu's ass... shuck want it */
error_reporting(0);
if($_REQUEST['go']){
	
ob_start();

echo '<?php' . "\n"; ?>define("_DB_NAME","<?php echo $_REQUEST['db_name'];?>");
define("_DB_USER","<?php echo $_REQUEST['db_user'];?>");
define("_DB_PASS","<?php echo $_REQUEST['db_pass'];?>");
define("_DB_SERVER","<?php echo $_REQUEST['db_host'];?>");
define("_TEMPLATE_DIR","templates/");
define("_NEWSLETTERS_DIR","newsletters/");
define("_IMAGES_DIR","images/");
define("_MAIL_SMTP",<?php echo ($_REQUEST['smtp_out'])?'true':'false';?>); 
define("_MAIL_SMTP_HOST","<?php echo $_REQUEST['smtp_out'];?>"); 
define("_MAIL_SMTP_AUTH",<?php echo ($_REQUEST['smtp_user'])?'true':'false';?>); 
define("_MAIL_SMTP_USER","<?php echo $_REQUEST['smtp_user'];?>"); 
define("_MAIL_SMTP_PASS","<?php echo $_REQUEST['smtp_pass'];?>"); 


define("_DEBUG_MODE",false);
define("_DEMO_MODE",false);
ini_set("display_errors",_DEBUG_MODE);

// date format for printing dates to the screen (uses php date syntax)
define("_DATE_FORMAT","d/m/Y"); 
// date format for inputting dates into the system
// 1 = DD/MM/YYYY
// 2 = YYYY/MM/DD
// 3 = MM/DD/YYYY
define("_DATE_INPUT",1); 
switch(_DATE_INPUT){
	case 1: define('_DATE_INPUT_HELP','DD/MM/YYYY'); break;
	case 2: define('_DATE_INPUT_HELP','YYYY/MM/DD'); break;
	case 3: define('_DATE_INPUT_HELP','MM/DD/YYYY'); break;
}
<?php
$data = ob_get_clean();
$res = false;
if(!defined('_DEMO_MODE') || !_DEMO_MODE){
	$res = file_put_contents('config.php',$data);
}

if($res){
	header("Location: ?p=setup&loaddb=true&load_db_auto=true");
	exit;
}
	?>
	
	<form action="?p=setup&loaddb=true" method="post">
	
	<h2><span>Configuration File:</span></h2>
	
	<div class="box">
		<p>
			Copy the below text into the "config.php" file and then make sure you <b>upload</b> this file.
		</p>
<textarea cols="60" rows="20" id="config_file">
<?php echo $data;?>
</textarea>
		<p>
			When you are done, click the button below.
		</p>
		<input type="checkbox" name="load_db_auto" value="true" checked>Load Database In Automatically <br>
		
		<input type="submit" name="fin" id="fin" value="I have finished and uploaded my new config.php file!">
		</div>
		
		</form>

	<?php
}else if($_REQUEST['loaddb']){ 
	
	
	if(!defined("_DB_NAME")){
		echo "It doesn't look like you have loaded your config.php file in correctly!
		<a href='index.php' title='' class='submit orange'> Go Back </a>

		";
		exit;
	}
ob_start();
?>

CREATE TABLE IF NOT EXISTS `campaign` (
  `campaign_id` int(11) NOT NULL auto_increment,
  `campaign_name` varchar(255) NOT NULL,
  `create_date` date NOT NULL,
  PRIMARY KEY  (`campaign_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `campaign_member` (
  `campaign_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `current_newsletter_id` int(11) NOT NULL,
  `join_time` int(11) NOT NULL,
  PRIMARY KEY  (`campaign_id`,`member_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `campaign_newsletter` (
  `campaign_id` int(11) NOT NULL,
  `newsletter_id` int(11) NOT NULL,
  `send_time` int(11) NOT NULL,
  PRIMARY KEY  (`campaign_id`,`newsletter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `group` (
  `group_id` int(11) NOT NULL auto_increment,
  `group_name` varchar(255) NOT NULL,
  `public` int(11) NOT NULL,
  PRIMARY KEY  (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `group` VALUES (1, 'Default Newsletter', 1);


CREATE TABLE IF NOT EXISTS `image` (
  `image_id` int(11) NOT NULL auto_increment,
  `image_url` text NOT NULL,
  PRIMARY KEY  (`image_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `link` (
  `link_id` int(11) NOT NULL auto_increment,
  `link_url` text NOT NULL,
  PRIMARY KEY  (`link_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `link_open` (
  `link_open_id` int(11) NOT NULL auto_increment,
  `link_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `send_id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY  (`link_open_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `member` (
  `member_id` int(11) NOT NULL auto_increment,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `join_date` date NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `unsubscribe_date` date NOT NULL,
  `unsubscribe_send_id` int(11) NOT NULL,
  PRIMARY KEY  (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `member_field` (
  `member_field_id` int(11) NOT NULL auto_increment,
  `field_name` varchar(255) NOT NULL,
  `field_type` varchar(20) NOT NULL,
  `required` int(11) NOT NULL,
  PRIMARY KEY  (`member_field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `member_field_value` (
  `member_id` int(11) NOT NULL,
  `member_field_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`member_id`,`member_field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `member_group` (
  `member_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY  (`member_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `newsletter` (
  `newsletter_id` int(11) NOT NULL auto_increment,
  `create_date` date NOT NULL,
  `template` varchar(100) collate utf8_bin NOT NULL,
  `subject` varchar(255) collate utf8_bin NOT NULL,
  `from_name` varchar(255) collate utf8_bin NOT NULL,
  `from_email` varchar(255) collate utf8_bin NOT NULL,
  `content` text collate utf8_bin NOT NULL,
  `bounce_email` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`newsletter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `newsletter_member` (
  `send_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `sent_time` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `open_time` int(11) NOT NULL,
  `bounce_time` int(11) NOT NULL,
  PRIMARY KEY  (`send_id`,`member_id`),
  KEY `open_time` (`open_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `send` (
  `send_id` int(11) NOT NULL auto_increment,
  `start_time` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `finish_time` int(11) NOT NULL,
  `newsletter_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `template_html` text NOT NULL,
  `full_html` text NOT NULL,
  PRIMARY KEY  (`send_id`),
  KEY `newsletter_id` (`newsletter_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(255) NOT NULL,
  `val` varchar(255) NOT NULL,
  PRIMARY KEY  (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `sync` (
  `sync_id` int(11) NOT NULL auto_increment,
  `sync_name` varchar(50) NOT NULL,
  `edit_url` varchar(255) NOT NULL,
  `db_username` varchar(40) NOT NULL,
  `db_password` varchar(40) NOT NULL,
  `db_host` varchar(40) NOT NULL,
  `db_name` varchar(40) NOT NULL,
  `db_table` varchar(40) NOT NULL,
  `db_table_key` varchar(40) NOT NULL,
  `db_table_email_key` varchar(40) NOT NULL,
  `db_table_fname_key` varchar(40) NOT NULL,
  `db_table_lname_key` varchar(40) NOT NULL,
  `last_sync` int(11) NOT NULL,
  `create_date` date NOT NULL,
  PRIMARY KEY  (`sync_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;



CREATE TABLE IF NOT EXISTS `sync_group` (
  `sync_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY  (`sync_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sync_member` (
  `sync_id` int(11) NOT NULL,
  `sync_unique_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  PRIMARY KEY  (`sync_id`,`sync_unique_id`,`member_id`),
  KEY `sync_id` (`sync_id`),
  KEY `sync_unique_id` (`sync_unique_id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `settings` VALUES ('bounce_email', 'you@email.com');
INSERT INTO `settings` VALUES ('default_template', 'Plain Newsletter');
INSERT INTO `settings` VALUES ('from_email', 'you@email.com');
INSERT INTO `settings` VALUES ('from_name', 'Your Company Name');
INSERT INTO `settings` VALUES ('password', 'thetutlage');
INSERT INTO `settings` VALUES ('username', 'thetutlage');
        
<?php

$allsql = ob_get_clean();
$sqlerrors=array();
if($_REQUEST['load_db_auto']){
	$sql_bits = explode(";",$allsql);
	foreach($sql_bits as $sql){
		$sql = trim($sql);
		if(!$sql)continue;
		$res = query($sql,$db);
		if(!$res){
			$sqlerrors[] = "Mysql Error: $sql - " . mysql_error();
		}
	}
	if($sqlerrors){
		foreach($sqlerrors as $error){
			echo $error . "<br>
			";
		}
	}
}

	if(!$_REQUEST['load_db_auto'] || $sqlerrors){
	?>
	
	<h2><span>Load MySQL Manually:</span></h2>
	<textarea cols="70" rows="40" id="config_file"><?php echo $allsql;?></textarea>
	
	<?php
}else{
	
	
	?>
	<h2><span>Setup Success!</span></h2>
	
	<div class="box">
		<p>That smells good !!! , I am done with my work now go ahead and start filling up your database.</p>
		<p>
			<b> Note:-</b> We don't have any ugly success messages becuase you can see live changes on your screen.
		</p>
		<p>
			The default login details are: <br>
			Username:<strong> thetutlage</strong> <br>
			Password:<strong> thetutlage</strong> <br>
			You can change these from the settings page.
		</p>
		<p>
			<input type="button" name="b" value="Login to the Dashboard!" onclick="window.location.href='index.php'" class="submit green">
		</p>
	</div>
	<?php
}
	
?>


<?php
}else{
	?>
	
	
<form action="?p=setup&go=true" method="post">
<h2><span>File Permissions:</span></h2>

<div class="box">
	<?php
	$folders = array(
		"newsletters",
		"images",
	);
	foreach($folders as $folder){
		if(!is_dir($folder)){
			echo "The folder '$folder' does not exists, please create it. <br>";
		}else if(!is_writable($folder)){
			echo "The folder '$folder' is not WRITABLE, please enable write permissions (eg: right click on folder in FTP, set permissions, tick all write). <br>";
		}else{
			echo "The folder '$folder' is OK <br>";
		}
		
	}
	?>
</div>

<h2><span>Database Setup:</span></h2>

<div class="box">
	<p>Please create a MySQL database, and enter those details here:</p>
	<table cellpadding="4">
		<tr>
			<td><label>Database Name</label></td>
			<td>
			<div class="form_field">
				<input type="text" name="db_name" id="db_name" value="newsletter"></td>
			</div>
		</tr>
		<tr>
			<td><label>Database Username</label></td>
			<td><div class="form_field"><input type="text" name="db_user" id="db_user" value="user"></div></td>
		</tr>
		<tr>
			<td><label>Database Password</label></td>
			<td><div class="form_field"><input type="text" name="db_pass" id="db_pass" value="pass"></div></td>
		</tr>
		<tr>
			<td><label>Database Host</label></td>
			<td>			<div class="form_field"><input type="text" name="db_host" id="db_host" value="localhost"></div></td>
		</tr>
	</table>
</div>

<h2><span>SMTP Setup:</span></h2>

<div class="box">
	<p>Please enter your SMTP connection details into this box:</p>
	<table cellpadding="4">
		<tr>
			<td><label>SMTP Outgoing Server</label></td>
			<td><div class="form_field"><input type="text" name="smtp_out" id="smtp_out" value="mail.yoursite.com"></div></td>
		</tr>
		<tr>
			<td><label>SMTP Username</label></td>
			<td><div class="form_field"><input type="text" name="smtp_user" id="smtp_user" value="you@yoursite.com"></div> (leave blank for none)</td>
		</tr>
		<tr>
			<td><label>SMTP Password</label></td>
			<td><div class="form_field"><input type="text" name="smtp_pass" id="smtp_pass" value=""><div class="form_field"></td>
		</tr>
	</table>
</div>

<h2><span>Generate Config File</span></h2>
<div class="box">
	<input type="submit" name="submit" id="submit" value="Generate" class="submit green">
</div>



</form>
</div>
	<?php
}
?>