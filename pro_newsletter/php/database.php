<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce
 * InSite Contribution :- Andy Charles
 * 
**/


//ALTER TABLE  `group` ADD  `public` INT NOT NULL ;
//ALTER TABLE  `member_field` ADD  `required` INT NOT NULL ;
//ALTER TABLE  `ns_send` ADD  `full_html` TEXT NOT NULL ;
//ALTER TABLE  `ns_send` CHANGE  `template_html`  `template_html` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
//ALTER TABLE  `ns_send` CHANGE  `full_html`  `full_html` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL


define("_DB_PREFIX","");

function db_connect(){
	$dbcnx = mysql_connect(_DB_SERVER,_DB_USER,_DB_PASS) or die('Unable to connect to mysql server. Reason: '.mysql_error());
	$db = mysql_select_db(_DB_NAME) or die('Unable to select database: '._DB_NAME.'. Reason: '.mysql_error());
	mysql_query( "SET NAMES utf8", $dbcnx );
	mysql_query( "SET CHARACTER SET utf8", $dbcnx );
	return $dbcnx;
}

function db_close($db){mysql_close($db);}

function query($sql,$db){
	$res = mysql_query($sql,$db) or die(mysql_error() . $sql);
	/*if(!$res){
		echo("DATABASE ERROR: \n\n".mysql_error(). " \n\n With this SQL: \n\n $sql");
		exit;
	}*/
	return $res;
}
function db_clean_deep(&$value){
    $value = is_array($value) ? array_map('db_clean_deep', $value) : htmlspecialchars($value);
    return $value;
} 
function query_to_array($res){
	$array = array();
	while($row = mysql_fetch_assoc($res)){
		if(isset($row['id']))
			$array[$row['id']] = $row;
		else
			$array[] = $row;
	}
	//db_clean_deep($array); 
	return $array;
}
function qa($sql,$db){
	return query_to_array(query($sql,$db));
}