<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce
 * InSite Contribution :- Andy Charles
 * 
**/

session_start();


require_once("config.php");
require_once("php/database.php");
require_once("php/functions.php");
$db = db_connect();

require_once("php/class.newsletter.php");
$newsletter = new newsletter();
$newsletter -> init();

require_once("php/auth.php");

// previewing an existing newsletter?
if(isset($_REQUEST['newsletter_id']) ){
	$newsletter_data = $newsletter->get_newsletter($db,(int)$_REQUEST['newsletter_id']);
	if(!$_REQUEST['template'])
		$_REQUEST['template'] = $newsletter_data['template'];
	if(!$_REQUEST['newsletter_content'])
		$_REQUEST['newsletter_content'] = stripslashes($newsletter_data['content']);
}

$template = basename($_REQUEST['template']);

if(!$template){
	echo "No template selected, sorry...";
	exit;
}
if(isset($_GET['email'])){
	$preview_email = trim(htmlspecialchars($_REQUEST['preview_email']));
	if(!$preview_email){
		echo 'Please enter a preview email first.';
		exit;
	}
	$to_email = $preview_email;
}else{
	$preview_email = false;
	$to_email = $_REQUEST['from_email'];
}

// pull out newsletter html and replace our variables.
$template_html = $newsletter->get_template_html($template,array());
// fix image paths with no newsletter id, so we dont put a tracking code in these ones.
$template_html = $newsletter->fix_image_paths($template_html,false,'templates/'.$template,false);


// do teh same as above, but this time we add the body content.
$replace_two = array(
	"email_body" => $newsletter->fix_image_paths($_REQUEST['newsletter_content'],false,'',false),
);
$template_html = $newsletter->get_template_html($template_html,$replace_two);



$replace = array(
	"email_subject" => $_REQUEST['subject'],
	"from_name" => $_REQUEST['from_name'],
	"from_email" => $_REQUEST['from_email'],
	"to_email" => $to_email,
	"sent_date" => date("jS M, Y"),
	"unsubscribe_url" => '#',
	"first_name" => 'John(sample)',
	"last_name" => 'Smith(sample)',
	"email" => $_REQUEST['from_email'],
	"view_online" => '#',
	
);

$template_html = $newsletter->get_template_html($template_html,$replace);


if($preview_email){
	// we send this html to the preview email address
	//send_email($email_to,$email_subject,$email_contents,$email_from,$email_from_name,$base_dir='')
	$template_html = $newsletter->fix_image_paths($template_html,false,'');
	if($newsletter->send_email($preview_email,"[PREVIEW] ".$replace['email_subject'],$template_html,$replace['from_email'],$replace['from_name'])){
		echo 'Email successfully sent to '.$preview_email;
		echo '<br>';
		echo 'You can now close this window.';
	}else{
		echo 'Sorry, failed to send preview to '.$preview_email;
	}
}else{

	?>
	
	<script language="javascript">
	if(window.opener && !window.opener.closed){
		if(typeof window.opener.document.getElementById('create_form') != 'undefined'){
			window.opener.document.getElementById('create_form').action = '';
			window.opener.document.getElementById('create_form').target = '_self';
		}
	}
	</script>
	<?php
	
	echo $template_html;
}
?>