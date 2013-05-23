<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

$errors=array();
$newsletter_id = (int)$_REQUEST['newsletter_id'];
// previewing an existing newsletter?
if($newsletter_id){
	$newsletter->write_newsletter_content($db,$newsletter_id);
	$newsletter_data = $newsletter->get_newsletter($db,$newsletter_id);
}else{
	$errors[]='No newsletter selected.';
}

if(!$errors){
	ob_start();
	if( isset($_REQUEST['full']) && is_file(_NEWSLETTERS_DIR . 'newsletter-'.$newsletter_id.'-full.html')){
		include(_NEWSLETTERS_DIR . 'newsletter-'.$newsletter_id.'-full.html');
	}else if(is_file(_NEWSLETTERS_DIR . 'newsletter-'.$newsletter_id.'.html')){
		// then normal version
		include(_NEWSLETTERS_DIR . 'newsletter-'.$newsletter_id.'.html');
	}else{
		echo 'Sorry, Newsletter No Longer Exists';
	}
	$newsletter_html = ob_get_clean();
	$newsletter_html = $newsletter->fix_image_paths($newsletter_html,false,'',true);
	// later: preview as member: 
	$member_id = false;
	if($member_id){ 
		$member_data = $newsletter->get_member($db,$member_id,true);
		$replace = array(
			"email_subject" => $newsletter_data['subject'],
			"from_name" => $newsletter_data['from_name'],
			"from_email" => $newsletter_data['from_email'],
			"to_email" => $member_data['email'],
			"sent_date" => date("jS M, Y"),
			"sent_month" => date("M Y"),
			"member_id" => $member_data['member_id'],
			"send_id" => $send_id,
			"MEMBER_HASH" => md5("Member Hash for $send_id with member_id $member_id"),
			"first_name"=>$member_data['first_name'],
			"last_name"=>$member_data['last_name'],
			"email"=>$member_data['email'],
			// backwards compatiblility:
			"unsubscribe_url" => '',
			"view_online"=>'http://'.$newsletter->base_href.'/ext.php?t=view&id='.$newsletter_id,
			"link_account" => $newsletter->settings['url_update'],
		);
		
	}else{
		// for public things like share on facebook
		$replace = array(
			"email_subject" => $newsletter_data['subject'],
			"from_name" => $newsletter_data['from_name'],
			"from_email" => $newsletter_data['from_email'],
			"to_email" => $newsletter_data['from_email'],
			"sent_date" => date("jS M, Y"),
			"sent_month" => date("M Y"),
			"member_id" => '',
			"send_id" => '',
			"MEMBER_HASH" => '',
			"first_name"=>'Member',
			"last_name"=>'',
			"email"=>'',
			// backwards compatiblility:
			"unsubscribe_url" => '',
			"view_online"=>'http://'.$newsletter->base_href.'/ext.php?t=view&id='.$id,
			"link_account" => $newsletter->settings['url_update'],
		);
	}
	foreach($replace as $key=>$val){
		$newsletter_html = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$newsletter_html);
	}
}

if(isset($_REQUEST['email'])){
	$preview_email = urldecode($_REQUEST['email']);
	if(!$preview_email){
		$errors[]='Please enter a preview email first.';
	}
}else{
	$preview_email = false;
}


if(isset($_REQUEST['iframe']) && !$errors){

	ob_end_clean();
	echo $newsletter_html;
	exit;
}else{
	?>
	
	<?php if($preview_email){
		// we send this html to the preview email address
		//send_email($email_to,$email_subject,$email_contents,$email_from,$email_from_name,$base_dir='')
		//$template_html = $newsletter->fix_image_paths($template_html,false,'');
		if($newsletter->send_email($preview_email,"[PREVIEW] ".$newsletter_data['subject'],$newsletter_html,$newsletter_data['from_email'],$newsletter_data['from_name'])){
			echo 'Email successfully sent to '.htmlspecialchars($preview_email);
			echo '<br>';
			echo 'You can now return to the editor.';
		}else{
			echo 'Sorry, failed to send preview to '.$preview_email;
		}
	}else{ 
	?>

<fieldset class="two_col left_col" style="width: 96%; margin-left: 10px;">
	<h2><span>Newsletter:</span> <?php echo htmlspecialchars($newsletter_data['subject']);?></h2>
	<p><a href="?p=create&newsletter_id=<?php echo $newsletter_id;?>#editor" class="submit orange">&laquo; Return to Newsletter Editor</a></p>
	<iframe src="?p=preview&small&iframe=true&newsletter_id=<?php echo $newsletter_id;?>" frameborder="0" style="border:1px solid #CCCCCC; width:100%; height:600px;"></iframe>
</fieldset>

<!--
	<iframe src="?p=preview&small&iframe=true&newsletter_id=<?php echo $newsletter_id;?>" frameborder="0" style="border:1px solid #CCCCCC; width:100%; height:600px;"></iframe>
	<iframe src="?p=preview&full&iframe=true&newsletter_id=<?php echo $newsletter_id;?>" frameborder="0" style="border:1px solid #CCCCCC; width:100%; height:600px;"></iframe>
-->
	<?php
	}
}
?>