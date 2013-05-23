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
	echo "php ".getcwd()."/cron.php \n";
	echo '</pre>';
	exit;
}

$now = time();
@set_time_limit(0);


		
// find any pending sends and process them if their date is less than now.
$pending = $newsletter->get_pending_sends($db);
foreach($pending as $send){
	$send_id = $send['send_id'];
	$newsletter_id = $send['newsletter_id'];
	if($send['start_time'] < $now){
		
		$send_data = $newsletter->get_send($db,$send_id);
		$newsletter_id = $send_data['newsletter_id'];
		$newsletter_data = $newsletter->get_newsletter($db,$newsletter_id);
		
		$batch_limit = (int)$newsletter->settings['burst_count'];
		if(!$batch_limit)$batch_limit = 10; // default 10.
		
		$result = array();
		$result['status'] = true;
		$sent_to = count($send_data['sent_members']);
		$batch_count = 0;
		$send_count = 0;
		foreach($send_data['unsent_members'] as $unsent_member){ 
			
			$result = $newsletter->send_out_newsletter($db,$send_id,$unsent_member['member_id']);
			
			if($result['status']){
				$batch_count++;
				$sent_to++;
			}else{
				$sent_to = $result['message'];
			}
			/*?>
			<script language="javascript">
	    	$('#sent_to',window.parent.document).html('<?php echo $sent_to;?>');
	    	</script>
	    	
	    	<?php 
	    	ob_flush();
	    	flush();*/
	    	
	    	if(!$result['status']){
	    		// break on fail to send
	    		break;
	    	}
			$send_count++;
	    	if($batch_count >= $batch_limit){
	    		if(_DEMO_MODE)sleep(4);
			}
		}
		if($result['status']){
			 $send_data = $newsletter->get_send($db,$send_id);
		    if(!count($send_data['unsent_members']) ){
		    	$newsletter->send_complete($db,$send_id);
				// finished send successfully.
				if($newsletter->settings['notify_email']){
				
					$email_to = $newsletter->settings['notify_email'];
					$email_from = $newsletter->settings['from_email'];
					$email_from_name = $newsletter->settings['from_name'];
					$email_subject = "Automatic Newsletter Processed: ".$send['subject'];
					$email_contents = 'We just automatically processed your newsletter and sent it to '.$send_count .' people';
					if(!$newsletter->send_email($email_to,$email_subject,$email_contents,$email_from,$email_from_name)){
						echo "Cron job successful. Failed to notify owner.";
					}
				}
			}
		}
	}
}


// find any newsletter campaigns that need sending.
$campaigns = $newsletter->get_campaigns($db);
foreach($campaigns as $campaign_data){
	$campaign_id = $campaign_data['campaign_id'];
	$campaign_data = $newsletter->get_campaign($db,  $campaign_id);
	
	$campaign_newsletters = array();
	while($newsletter_row = mysql_fetch_assoc($campaign_data['newsletter_rs'])){
		$campaign_newsletters[] = $newsletter_row;
	}
	while($member = mysql_fetch_assoc($campaign_data['members_rs'])){
		
		$member_next_newsletter = false;
		reset($campaign_newsletters);
		if(!$member['current_newsletter_id']){
			//echo 'Nothing sent yet.';
			$member_next_newsletter = current($campaign_newsletters);
		}else{
			$x = 0;
			$member_newsletter = false;
			foreach($campaign_newsletters as $campaign_newsletter){
				if($member_newsletter){
					$member_next_newsletter = $campaign_newsletter;
					break;
				}
				if($campaign_newsletter['newsletter_id'] == $member['current_newsletter_id']){
					$member_newsletter = true;
				}
				$x++;
			}
		}
		if($member_next_newsletter){
			//echo '<strong>'.$member_next_newsletter['subject'] . '</strong> on ';
			$send_time = $member['join_time'] + $member_next_newsletter['send_time'];
			if($send_time <= $now){
				
				// if we missed this send time (ie: it's in the past) then it's time to send this newsletter to this customer.
				
				$send_id = $newsletter->create_send($db,$member_next_newsletter['newsletter_id'],$member['member_id'],true,false,$campaign_id);
				if($send_id){
					$result = $newsletter->send_out_newsletter($db,$send_id,$member['member_id']);
					// we do it similar to the code in send.php so that if we go over any limits we can at least re-start this single send 
					$send_data = $newsletter->get_send($db,$send_id);
				    if(!count($send_data['unsent_members']) ){
				    	$newsletter->send_complete($db,$send_id);
						// and update our member status to the next newsletter.
						$sql = "UPDATE campaign_member SET current_newsletter_id = '".$member_next_newsletter['newsletter_id']."' WHERE campaign_id = '$campaign_id' AND member_id = '".$member['member_id']."'";
						$res = query($sql,$db);
				    }
				}
			}
			//echo date("d M Y h:i:sa",$send_time);
		}else{
			//echo 'None';
		}
	}
}
