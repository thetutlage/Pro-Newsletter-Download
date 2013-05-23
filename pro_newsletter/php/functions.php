<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce
 * InSite Contribution :- Andy Charles
 * 
**/
function stripslashes_deep(&$value){
    $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    return $value;
} 
if(get_magic_quotes_gpc()){
	stripslashes_deep($_GET);
    stripslashes_deep($_POST); 
    stripslashes_deep($_REQUEST); 
}



// simple string highlight for search results.
function _shl($string,$highlight){
	$highlight= trim($highlight);
	if(!$highlight)return $string;
	return preg_replace('/'.preg_quote($highlight,'/').'/i','<span style="background-color:#FFFF66">$0</span>',$string);
}


if(isset($_REQUEST['jsonp'])&&$_REQUEST['jsonp']){
	echo '<script language="javascript">' . $_REQUEST['jsonp'] . '</script>';
	exit;
}

function _l($text){
	// read in from the global label array 
	global $labels;
	$argv = func_get_args();
	// see if the first one is a lang label
	if(isset($labels[$text])){
		$argv[0] = $labels[$text];
	}
	// use this for building up the language array.
	// visit index.php?dump_lang=true to get a csv file of language vars.
	if(_DEMO_MODE){
		//$_SESSION['l'][$text] = true;
	}
	return call_user_func_array('sprintf',$argv); 
}

function get_languages(){
	$files = @glob("php/lang/*.php");
	if(!is_array($files))$files = array();
	$languages=array();
	foreach($files as $file){
		$languages[] = basename(str_replace('.php','',$file));
	}
	return $languages;
}


function input_date($date,$include_time=false){
	
	if(	
		!$date || 
		(preg_match('/[a-z]/i',$date) && !preg_match('/^[\+-]\d/',$date)) || 
		preg_match('/^\d+$/',$date)  
	)return '';
	
	// takes a user input date and returns the mysql YYYY-MM-DD valid format.
	// 1 = DD/MM/YYYY
	// 2 = YYYY/MM/DD
	// 3 = MM/DD/YYYY
	
	// could use sscanf below, but still wanted to run preg_match
	// so used implode(explode( instead... meh
	
	switch(_DATE_INPUT){
		case 1:
			if(preg_match('#^\d?\d([-/])\d?\d\1\d{2,4}$#',$date,$matches)){
				$date = implode("-",array_reverse(explode($matches[1],$date)));
				if(strtotime($date)){
					$date = date('Y-m-d'.(($include_time)?' H:i:s':''),strtotime($date));
					break;
				}
			}
		case 2:
			if(preg_match('#^\d{2,4}([-/])\d?\d\1\d?\d$#',$date,$matches)){
				$date = implode("-",explode($matches[1],$date));
				if(strtotime($date)){
					$date = date('Y-m-d'.(($include_time)?' H:i:s':''),strtotime($date));
					break;
				}
			}
		case 3:
			if(preg_match('#^\d?\d([-/])\d?\d\1\d{2,4}$#',$date,$matches)){
				$date_bits = explode($matches[1],$date);
				$date = $date_bits[2] .'-'. $date_bits[0] .'-'. $date_bits[1]; 
				if(strtotime($date)){
					$date = date('Y-m-d'.(($include_time)?' H:i:s':''),strtotime($date));
					break;
				}
			}
		default:
			$date = date('Y-m-d'.(($include_time)?' H:i:s':''),strtotime($date));
	}
	
	return $date;
}
function print_date($date,$include_time=false,$input_format=false){
	if(!$date || (preg_match('/[a-z]/i',$date) && !preg_match('/^[\+-]\d/',$date)))return '';
	if(strpos($date,'0000-00-00')!==false)return '';
	if(is_numeric($date)){
		// we have a timestamp, simply spit this out 
		$time = $date;
	}else{
		$time = strtotime(input_date($date,$include_time));
	}
	if($input_format){
		switch(_DATE_INPUT){
			case 1:
				$date = date("d/m/Y",$time);
				break;
			case 2:
				$date = date("Y/m/d",$time);
				break;
			case 3:
				$date = date("m/d/Y",$time);
				break;
			default:
				$date = date("Y-m-d",$time);
				break;
		}
	}else{
		$date = date(_DATE_FORMAT,$time);
	}
	if($include_time){
		$date.= ' '.date("H:i:s",$time);
	}
	return $date;
}