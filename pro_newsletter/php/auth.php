<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/
if(isset($_REQUEST['logout'])){
	unset($_SESSION['_newsletter_loggedin']);
	$newsletter->logout();
	header("Location: index.php");
	exit;
}

$login_status = (isset($_SESSION['_newsletter_loggedin']) && $_SESSION['_newsletter_loggedin']);

if(isset($_REQUEST['username']) && isset($_REQUEST['password'])){
	$login_status = $newsletter->login($db,$_REQUEST['username'],$_REQUEST['password']);
}

if($login_status){
	// support for multiple logins at one time.
	$_SESSION['_newsletter_loggedin'] = $login_status;
	$_SESSION['user_logged_in'] = $_REQUEST['username'];
}
else{
	$error = '<div class="newsletter_error"> Invalid Credentials </div>';
}

if(!$login_status){
	?>
	<html>
	<head>
		<title>Login</title>
		<link rel="stylesheet" href="layout/css/styles.css" type="text/css" />
	</head>

	<body>
	<div id="wrapper" style="width: 900px; margin: auto;">
	<h1>Newsletter Dashboard</h1>
	<?php if(isset($error)) { echo $error; }?>
	<fieldset class="two_col left_col" style="width: 30%;">
		<legend> Newsletter Dashboard </legend>
		<form action="" method="post">
			<label>Username</label>
			<div class="form_field">
				<input type="text" name="username" value="<?php echo (_DEMO_MODE)?$newsletter->settings['username']:'';?>">
			</div>
			
			<label>Password</label>
			<div class="form_field">
				<input type="password" name="password" value="<?php echo (_DEMO_MODE)?$newsletter->settings['password']:'';?>">
			</div>
			<br />
			<input type="submit" name="login_button" value="Login" class="submit green">
		</form>
	</fieldset><!-- end two_col -->
	
	<fieldset class="two_col right_col">
		<legend> Tips </legend>
		<label class="next_label">What's Next ?</label>
		<div class="single_info grid_2">
			<label class="inline_label">Create Newsletter</label>
			<p> Once you are done, you can create unlimited newsletters. </p>
		</div>
		<div class="single_info grid_3">
			<label class="inline_label">Design Templates</label>
			<p>Don't stick to one layout, make different templates as many as you can</p>
		</div>

		<div class="single_info grid_2">
			<label class="inline_label">Create Mailing List</label>
			<p>Create mailing list for your clients</p>
		</div>

		<div class="single_info grid_3">
			<label class="inline_label">Become Smart User</label>
			<p>Subscribe to get these awesome scripts <a href="http://www.thetutlage.com/subscribe"> Do it now ?</a></p>
		</div>
	</fieldset>


	</div>
	</body>
	</html>
	<?php 
	exit;
}