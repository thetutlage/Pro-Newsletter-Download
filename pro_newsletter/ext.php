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


$send_id = (int)$_REQUEST['sid'];
$id = (int)$_REQUEST['id'];
$member_id = (int)$_REQUEST['mid'];

if(isset($_REQUEST['mhash'])){
	$mhash_provided = $_REQUEST['mhash'];
	$mhash_real = md5("Member Hash for $send_id with member_id $member_id");
}else{
	$mhash_provided = false;
	$mhash_real = 'NOTHING!'; // these are different, important.
}


switch($_REQUEST['t']){
	case "signup":
		$message = false;
		switch(true){ default:
			// simple switch so we can break out of it on error.
			$groups = array();
			$all_groups = $newsletter->get_groups($db);
			if(is_array($_REQUEST['group_id'])){
				$groups = $_REQUEST['group_id'];
			}
			$campaigns = array();
			if(is_array($_REQUEST['campaign_id'])){
				$campaigns = $_REQUEST['campaign_id'];
			}
			$custom = array();
			if(is_array($_REQUEST['mem_custom_val'])){
				$custom = $_REQUEST['mem_custom_val'];
			}
			$fields = array(
				"first_name"=>$_REQUEST['first_name'],
				"last_name"=>$_REQUEST['last_name'],
				"email"=>$_REQUEST['email'],
				"group_id"=>$groups,
				"custom"=>$custom,
				"campaign_id"=>$campaigns,
			);
			// todo - better error checking maybe?
			// custom error page?
			$existing_custom_fields = $newsletter->get_member_fields($db);
			foreach($existing_custom_fields as $key=>$val){
				if($val['required'] && !$custom[$val['field_name']]){
					$message = "Complete all required fields. <br /> Go <a href='javascript:history.go(-1);'>back</a> ";
					break;
				}
			}
			if($message)break;
			if(!$fields['first_name'] || !$fields['email']){
					$message = "Complete all required fields. <br /> Go <a href='javascript:history.go(-1);'>back</a> ";
				break;
			}
			$member_id = $newsletter->save_member($db,'new',$fields,true);
			if($member_id){
				
				if($newsletter->settings['notify_email']){
					
					$email_to = $newsletter->settings['notify_email'];
					$email_from = $newsletter->settings['from_email'];
					$email_from_name = $newsletter->settings['from_name'];
					$email_subject = "Subscriber Notification";
					
					
					$replace = $fields;
					$replace['groups'] = '';
					foreach($groups as $group_id){
						$replace['groups'] .= $all_groups[$group_id]['group_name'] . ", ";
					}
					//todo error check this file exists, eh, silly if they delete it.
					$email_contents = file_get_contents("layout/notify.html");
					foreach($replace as $key=>$val){
						if(is_array($val))continue;
						$email_contents = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$email_contents);
					}
					
					if(!$newsletter->send_email($email_to,$email_subject,$email_contents,$email_from,$email_from_name)){
						$message .= "(failed to notify owner) ";
					}
				}
				
				if(isset($newsletter->settings['double_opt_in']) && strtolower($newsletter->settings['double_opt_in']) == 'yes'){
					// send email to user asking them to confirm their membership.
					$email_to = $fields['email'];
					$email_from = $newsletter->settings['from_email'];
					$email_from_name = $newsletter->settings['from_name'];
					$email_subject = (isset($newsletter->settings['double_opt_in_subject'])) ? $newsletter->settings['double_opt_in_subject'] : 'Confirm newsletter subscription';
					$email_contents = file_get_contents("layout/double_optin.html");
					$replace = array(
						"from_name"=>$email_from_name,
						"CONFIRM_SUBSCRIPTION"=>'http://'.$newsletter->base_href.'/ext.php?t=confirm&mid='.$member_id.'&hash='.md5("double".$member_id).'',
						"first_name"=>$fields['first_name'],
						"last_name"=>$fields['last_name'],
						"email"=>$fields['email'],
					);
					foreach($replace as $key=>$val){
						if(is_array($val))continue;
						$email_contents = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$email_contents);
					}
					
					if(!$newsletter->send_email($email_to,$email_subject,$email_contents,$email_from,$email_from_name)){
						$message .= "Failed to send opt-in email, please contact us to inform us of this error. ";
					}else{
					}
					include("layout/subscribe-pending.html");
					exit;
				}
				
				if($newsletter->settings['subscribe_redirect']){
					ob_end_clean();
					header("Location: ".$newsletter->settings['subscribe_redirect']);
				}else{
					$message .= " <img src='layout/images/send_success.jpg' id='success_image'/> <br />Subscription successful.";
				}
			}
		}
		include("layout/subscribe.html");
		
		break;
	case "signup_form":
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: " . date(DATE_RFC822,strtotime("+1 day")));
		// display signup form.
		// generate the form, then include our header/footer wrapper that the user can style.
		$form = $newsletter->get_form($db,true);
		include("layout/subscribe_form.html");
		break;
	case "update_form":
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: " . date(DATE_RFC822,strtotime("+1 day")));
		// display update form.
		// generate the form, then include our header/footer wrapper that the user can style.
		$member_id = false;
		$message = '';
		if(isset($_REQUEST['email'])){
			$email = trim($_REQUEST['email']);
			// check if this member is subscribed.
			$sql = "SELECT * FROM `"._DB_PREFIX."member` WHERE email = '".mysql_real_escape_string(strtolower($email))."'";
			$existing = array_shift(qa($sql,$db));
			if($existing){
				$email = $existing['email'];
				$member_id = $existing['member_id'];
			}else{
				$email = '';
				$message = '<p>Sorry, we could not find your email address in our database. Please check your email address and try again.
					<br /> <a href="javascript:history.go(-1);" id="green"> Hide </a>
				</p>';
			}
		}
		if(!$member_id){
			include("layout/subscribe_update1.html");
		}else{
			$form = $newsletter->get_form($db,true,$member_id);
			include("layout/subscribe_update.html");
		}
		break;
	case "img":
		if($id){
			$link = $newsletter->get_image($db,$id);
			$newsletter->record_open($db,$send_id,$member_id);
			header("Location: ".$link['image_url']);
			exit;
		}
		break;
	case "lnk":
		if($id){
			$link = $newsletter->get_link($db,$id);
			$newsletter->record_open($db,$send_id,$member_id);
			$newsletter->record_link_click($db,$send_id,$member_id,$id);
			if($mhash_provided && preg_match('#'.preg_quote($newsletter->base_href,'#').'#',$link['link_url'])){
				// append the send id and member hash to the url:
				$link['link_url'] .= ((strpos($link['link_url'],'?')===false) ? '?' : '&') . 'sid='.$send_id.'&mid='.$member_id.'&mhash='.$mhash_provided;
			}
			header("Location: ".$link['link_url']);
			exit;
		}
		break;
	case "confirm":
		if($member_id){
			// basic hash 
			$realhash = md5("double".$member_id);
			$userhash = $_REQUEST['hash'];
			if($realhash == $userhash){
				$sql = "UPDATE `"._DB_PREFIX."member` SET join_date = NOW() WHERE member_id = '$member_id' LIMIT 1";
				$res = query($sql,$db);
				if($newsletter->settings['subscribe_redirect']){
					ob_end_clean();
					header("Location: ".$newsletter->settings['subscribe_redirect']);
				}else{
					include("layout/subscribe.html");
				}
			}
		}
		exit;
		break;
	case 'send_to_friend':
		if($id){
			$success = false;
			if($mhash_real == $mhash_provided && $send_id){
				
				$newsletter_data = $newsletter->get_newsletter($db,$id);
				if($_REQUEST['email']){
					$fields = array(
						'email' => $_REQUEST['email'],
					);
					$new_member_id = $newsletter->save_member($db,'new',$fields,true);
					if($new_member_id){
						// successfully created member! yey
					}
				}
				if($_REQUEST['email']){
					$fields = array(
						'email' => $_REQUEST['email'],
					);
					$new_member_id = $newsletter->save_member($db,'new',$fields,true);
					if($new_member_id){
						// add member to newsletter send
						$sql = "REPLACE INTO `"._DB_PREFIX."newsletter_member` SET send_id = '$send_id', member_id = '$new_member_id', status = 1";
						$res = query($sql,$db);
						// send newsletter
						$res = $newsletter->send_out_newsletter($db,$send_id,$new_member_id,false,true);
						$success = true;
					}
				}
				// set confirm for template layout
				// email a ' signup properly' email to the user ? 
				
				//$send_data = $newsletter->get_send($db, $send_id);
				$member_data = $newsletter->get_member($db,$member_id,true);
				include("layout/send_to_friend.html");
			}else{
				echo 'Please click the link in the email you were sent';
				$member_data=array();
			}
		}
		break;
	case "view":
		// TODO - replace vars like its a members email.
		// check full version first:
		$provided_hash = $_REQUEST['hash'];
		$real_hash = md5("view link ".$member_id."from $send_id");
		
		$newsletter_data = $newsletter->get_newsletter($db,$id);
		
		ob_start();
		
		if(isset($_REQUEST['small']) && is_file(_NEWSLETTERS_DIR . 'newsletter-'.$id.'.html')){
			include(_NEWSLETTERS_DIR . 'newsletter-'.$id.'.html');
		}else if(is_file(_NEWSLETTERS_DIR . 'newsletter-'.$id.'-full.html')){
			include(_NEWSLETTERS_DIR . 'newsletter-'.$id.'-full.html');
		}else if(is_file(_NEWSLETTERS_DIR . 'newsletter-'.$id.'.html')){
			include(_NEWSLETTERS_DIR . 'newsletter-'.$id.'.html');
		}else{
			echo 'Sorry, Newsletter No Longer Exists';
		}
		$newsletter_html = ob_get_clean();

		if(($mhash_real == $mhash_provided) || ($provided_hash == $real_hash)){ 
			
			$send_data = $newsletter->get_send($db, $send_id);
			$member_data = $newsletter->get_member($db,$member_id,true);
			
			$string = '&sid='.$send_id.'&mid='.$member_data['member_id'].'&mhash='.$mhash_real;
			if(preg_match_all('/(ext\.php\?t=[^"]*)"/',$newsletter_html,$matches)){
				foreach($matches[1] as $key=>$val){ 
					if(!strpos($val,'mhash')){
						if(strpos($val,'#')){
							$replace = str_replace('#',$string.'#',$val);
						}else{
							$replace = $val.$string;
						}
						$newsletter_html = preg_replace('/'.preg_quote($val,'/').'/',$replace,$newsletter_html,1);
					}
				}
			}
			
			
			$replace = array(
				"email_subject" => $newsletter_data['subject'],
				"from_name" => $newsletter_data['from_name'],
				"from_email" => $newsletter_data['from_email'],
				"to_email" => $member_data['email'],
				"sent_date" => date("jS M, Y"), // TODO - read newsletter date
				"sent_month" => date("M Y"), // TODO - read newsletter date
				"member_id" => $member_data['member_id'],
				"send_id" => $send_id,
				"MEMBER_HASH" => md5("Member Hash for $send_id with member_id $member_id"),
				"first_name"=>$member_data['first_name'],
				"last_name"=>$member_data['last_name'],
				"email"=>$member_data['email'],
				// backwards compatiblility:
				"unsubscribe_url" => 'http://'.$newsletter->base_href.'/ext.php?t=unsub',
				"view_online"=>'http://'.$newsletter->base_href.'/ext.php?t=view&id='.$send_data['newsletter_id'].'&sid='.$send_id.'&mid='.$member_data['member_id'].'&hash='.md5("view link ".$member_data['member_id']."from $send_id").'',
				"link_account" => $newsletter->settings['url_update'],
			);
			
		}else{
			// for public things like share on facebook
			$replace = array(
				"email_subject" => $newsletter_data['subject'],
				"from_name" => $newsletter_data['from_name'],
				"from_email" => $newsletter_data['from_email'],
				"to_email" => $newsletter_data['from_email'],
				"sent_date" => date("jS M, Y"), // TODO - read newsletter date
				"sent_month" => date("M Y"), // TODO - read newsletter date
				"member_id" => '',
				"send_id" => '',
				"MEMBER_HASH" => '',
				"first_name"=>'Member',
				"last_name"=>'',
				"email"=>'',
				// backwards compatiblility:
				"unsubscribe_url" => 'http://'.$newsletter->base_href.'/ext.php?t=unsub_form',
				"view_online"=>'http://'.$newsletter->base_href.'/ext.php?t=view&id='.$id,
				"link_account" => 'http://'.$newsletter->base_href.'/ext.php?t=update_form',
			);
		}
		
		foreach($replace as $key=>$val){
			$newsletter_html = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$newsletter_html);
		}
		echo $newsletter_html;
		
		break;
	case "unsub":
		
		// check hash.
		$real_hash = md5("Unsub ".$member_id."from $send_id");
		$provided_hash = $_REQUEST['hash'];
		
		if(($mhash_real == $mhash_provided) || ($provided_hash == $real_hash)){ 
			$newsletter->unsubscribe($db,$member_id,$send_id);
			if($newsletter->settings['unsubscribe_redirect']){
				ob_end_clean();
				header("Location: ".$newsletter->settings['unsubscribe_redirect']);
			}else{
				include("layout/unsub.html");
			}
		}else{
			echo "Bad hash.";
			sleep(4);// basic brute force stopper :)
			exit;
		}
		exit;
	case "unsub_form":
		
		$member_id = false;
		$message = '';
		if(isset($_REQUEST['email'])){
			$email = trim($_REQUEST['email']);
			// check if this member is subscribed.
			$sql = "SELECT * FROM `"._DB_PREFIX."member` WHERE email = '".mysql_real_escape_string(strtolower($email))."'";
			$existing = array_shift(qa($sql,$db));
			if($existing){
				$email = $existing['email'];
				$member_id = $existing['member_id'];
			}else{
				$email = '';
				$message = '<p>Sorry, we could not find your email address in our database. Please check your email address and try again.
					<br /> <a href="javascript:history.go(-1);" id="green"> Go Back </a>
				</p>';
			}
		}
		if($member_id){
			$newsletter->unsubscribe($db,$member_id);
			$message = 'Unsubscribe successful';
		}
		include("layout/unsub_form.html");
		exit;
}
