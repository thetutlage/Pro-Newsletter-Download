<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

$settings = $newsletter->get_settings($db);
if($_REQUEST['save']){
	if(_DEMO_MODE){
		ob_end_clean();
		echo "Adjusting settings disabled in demo mode sorry";
		exit;
	}

	foreach($_REQUEST['settings'] as $key => $value)
	{
		$sql = mysql_query("UPDATE settings SET val = '".mysql_real_escape_string($value)."' WHERE `key` = '$key'") or die(mysql_error());
	}
	ob_end_clean();
	header("Location: index.php?p=settings");
	exit;
}
?>

<h1>&nbsp;</h1>

<form action="?p=settings&save=true" method="post" id="create_form">


<fieldset class="two_col left_col" style="width: 30%;">
<legend> Main Settings </legend>
		<?php
		foreach($settings as $key => $setting){
			?>
			<label><?php echo $key; ?></label>
			<div class="form_field"><input type="text" name="settings[<?php echo $key; ?>]" class="input" value="<?php echo $setting;?>"></div>
		<?php } ?>
		<br />
		<input type="submit" name="save" value="Save Settings" class="submit green">
</fieldset>
</form>

<fieldset class="two_col right_col">
	<legend> Help </legend>
		<label class="next_label">What's This ?</label>
		<div class="single_info grid_2">
			<label class="inline_label">Bounce Email</label>
			<p> Where bounce emails will get sent. create a new email account for this if possible.</p>
		</div>
		<div class="single_info grid_3">
			<label class="inline_label">Default Template</label>
			<p>Folder name of the default template to use.</p>
		</div>

		<div class="single_info grid_2">
			<label class="inline_label">From Email </label>
			<p>Email id to send newsletters.</p>
		</div>

		<div class="single_info grid_3">
			<label class="inline_label">From Name </label>
			<p>Default name to send newsletters.</p>
		</div>

		<div class="single_info grid_2">
			<label class="inline_label">Username</label>
			<p> Username is required to login to this system </p>
		</div>


		<div class="single_info grid_3">
			<label class="inline_label">Password</label>
			<p>Your password to login to this account</p>
		</div>


</fieldset>	

<div class="clear"></div>
<?php
$groups = $newsletter->get_groups($db);
$campaigns = $newsletter->get_campaigns($db);
$form = $newsletter->get_form($db);
?>

<h2><span>Embed Subscribe Form</span></h2>

<div class="box">
<p>Copy and Paste this HTML code to embed the newsletter subscribe form.</p>
<table cellpadding="5">

<tr>
	<td> <div class="embed_buttons"> <a href="ext.php?t=signup_form" target="_blank" class="submit orange">Subscribe Form</a></div></td>
	<td> <div class="embed_buttons"> <a href="ext.php?t=update_form" target="_blank" class="submit orange">Update Subscription Form</a> </div></td>
	<td> <div class="embed_buttons"> <a href="ext.php?t=unsub_form" target="_blank" class="submit orange">Unsubscribe Form</a> </div></td>
</tr>
<tr>
<td valign="top">
<textarea cols="60" class="input" rows="20" spellcheck="false">
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Subscribe</title>
<style type="text/css">
body,html{
height: 100%;
margin: 0;
padding: 0;
}
</style>
</head>
<body>
<?php //echo htmlspecialchars('<form action="http://'.$newsletter->base_href.'/ext.php?t=signup" method="post">'.$form.'</form>'); ?>
<?php echo htmlspecialchars('<iframe src="http://'.$newsletter->base_href.'/ext.php?t=signup_form" width="100%" height="100%" style="border: none; height: 100%;"></iframe>'); ?>
</body>
</html>
</textarea>
</td>
<td valign="top">
<textarea cols="60" class="input" rows="20" spellcheck="false">
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Subscribe</title>
<style type="text/css">
body,html{
height: 100%;
margin: 0;
padding: 0;
}
</style>
</head>
<body>
<?php //echo htmlspecialchars('<form action="http://'.$newsletter->base_href.'/ext.php?t=signup" method="post">'.$form.'</form>'); ?>
<?php echo htmlspecialchars('<iframe src="http://'.$newsletter->base_href.'/ext.php?t=update_form" width="100%" height="100%" style="border: none; height: 100%;"></iframe>'); ?>
</body>
</html>
</textarea>
</td>


<td valign="top">
<textarea cols="60" class="input" rows="20" spellcheck="false">
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Subscribe</title>
<style type="text/css">
body,html{
height: 100%;
margin: 0;
padding: 0;
}
</style>
</head>
<body>
<?php //echo htmlspecialchars('<form action="http://'.$newsletter->base_href.'/ext.php?t=signup" method="post">'.$form.'</form>'); ?>
<?php echo htmlspecialchars('<iframe src="http://'.$newsletter->base_href.'/ext.php?t=unsub_form" width="100%" height="100%" style="border: none; height: 100%;"></iframe>'); ?>
</body>
</html>
</textarea>
</td>


</tr>
</table>
</div>
<!--<h2><span>Sending CRON Job (beta)</span></h2>
<div class="box">
	<p>The CRON job will process scheduled newsletter sends and any campaigns that are setup.</p>
	<p>
		You can run the cron job manually yourself by <a href="cron.php" target="_blank">clicking here</a> (this may take a while to load - it will show a blank screen when done)
	</p>
	<p>
		For cron setup instructions please <a href="cron.php?t" target="_blank">click here</a>.
	</p>
</div>
<h2><span>Bounce Checking CRON Job (beta)</span></h2>
<div class="box">
	<p>The CRON job will process bounced emails for statistics.</p>
	<p>
		You can run the cron job manually yourself by <a href="cron_bounce.php" target="_blank">clicking here</a> (this may take a while to load - it will show a blank screen when done)
	</p>
	<p>
		For cron setup instructions please <a href="cron_bounce.php?t" target="_blank">click here</a>.
	</p>
</div> -->