<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

$newsletter_id = (int)$_REQUEST['newsletter_id'];
if(!$newsletter_id){
	// basic error checking.
	echo '<div class="newsletter_error">This newsletter might have been deleted , or reslect the newsletter </div>';
}

if(isset($_REQUEST['delete'])){
	if(_DEMO_MODE){
		echo "Sorry, cant delete newsletters in demo mode... ";
		exit;
	}
	$newsletter->delete_newsletter($db,$newsletter_id);
	ob_end_clean();
	header("Location: index.php?p=past");
	exit;
}

$errors = array();
if(isset($_REQUEST['save']) && $_REQUEST['save']){
	
	// save the newsletter 
	// check required fields.
	
	$fields = array(
		//"template" => $_REQUEST['template'],
		"subject" => $_REQUEST['subject'],
		"from_name" => $_REQUEST['from_name'],
		//"content" => $_REQUEST['newsletter_content'],
		"from_email" => $_REQUEST['from_email'],
		"bounce_email" => $_REQUEST['bounce_email'],
	);
	
	// basic error checking, nothing fancy
	foreach($fields as $key=>$val){
		if(!trim($val)){
			$errors [] = 'Required field missing: '.ucwords(str_replace('_', ' ',$key));
		}
	}
	
	if(!$errors){
		
		$newsletter_id = $newsletter->save($db,$newsletter_id,$fields);
		if($newsletter_id){
			if($_REQUEST['send']){
				// user wants to send this newsletter!! create a send a start away..
				
				if(isset($_REQUEST['dont_send_duplicate']) && $_REQUEST['dont_send_duplicate']){
					$dont_sent_duplicates = true;
				}else{
					$dont_sent_duplicates = false;
				}
				if(is_array($_REQUEST['group_id'])){
					$send_groups = $_REQUEST['group_id'];
				}else{
					$errors [] = "Please select a group to send to";
				}
				
				if(!$errors){
					
					$send_id = $newsletter->create_send($db,$newsletter_id,$send_groups,$dont_sent_duplicates,$_REQUEST['send_later']);
					
					if(!$send_id){
						$errors[] = "No members found to send to";
					}else{
						ob_end_clean();
						header("Location: index.php?p=send&send_id=$send_id");
						exit;
					}
				}
			}else{
				ob_end_clean();
				header("Location: index.php?p=open&newsletter_id=$newsletter_id");
				exit;
				}
		}else{
			$errors [] = 'Failed to create newsletter in database';
		}
	}
	
	
	foreach($errors as $error){
		echo '<div class="newsletter_error">'.$error . '</div>';
	}
	
	
}


$newsletter_data = $newsletter->get_newsletter($db,$newsletter_id);

$sends = $newsletter->get_newsletter_sends($db,$newsletter_id);
?>

<h1>Newsletter</h1>

<form action="?p=open&save=true" method="post" id="create_form">

<input type="hidden" name="newsletter_id" value="<?php echo $newsletter_id;?>">

<a href="#" onclick="$('#other_settings').slideToggle(); return false;" class="submit orange right_float">show settings / edit newsletter again</a>

<div id="other_settings" style="display:none;">
<h2><span>Subject: <?php echo $newsletter_data['subject'];?></span></h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				<label>Email Subject</label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="subject" value="<?php echo $newsletter_data['subject'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>From Name</label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="from_name" value="<?php echo $newsletter_data['from_name'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>From Email</label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="from_email" value="<?php echo $newsletter_data['from_email'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>Bounce Email</label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="bounce_email" value="<?php echo $newsletter_data['bounce_email'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				
			</td>
			<td>
				<input type="submit" name="save" value="Save" class="submit green">
				<a href="?p=create&newsletter_id=<?php echo $newsletter_data['newsletter_id'];?>" class="submit orange right_float">Complete Edit</a>
			</td>
		</tr>
	</table>

</div>


<h2><span>Preview (optional)</span></h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				<label>Preview in Browser</label>
			</td>
			<td>
				<div class="form_field"><input type="submit" name="preview1" value="Open Preview" onclick="this.form.action='preview.php'; popupwin=window.open('about:blank','popupwin','width=700,height=800,scrollbars=1,resizeable=1'); if(!popupwin){alert('Please disable popup blocker'); return false;} this.form.target='popupwin';"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>Preview in Email</label>
			</td>
			<td>
				<div class="form_field"><input type="text" name="preview_email" id="preview_email" value="<?php echo $newsletter_data['from_email'];?>"><input type="submit" name="preview2" value="Send Preview" onclick="this.form.action='preview.php?email=true'; popupwin=window.open('about:blank','popupwin','width=500,height=400,scrollbars=1,resizeable=1'); if(!popupwin){alert('Please disable popup blocker'); return false;} this.form.target='popupwin';"></div>
			</td>
		</tr>
	</table>
	
</div>

</div>

<h2><span>Send <?php echo (count($sends))?' newsletter out again':'';?></span></h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				<label>Tick which groups you would like to send to</label>
			</td>
			<td>
				<input type="checkbox" name="group_id[]" value="ALL"> <b>All Members</b><br>
				<?php
				$groups = $newsletter->get_groups($db);
				foreach($groups as $group){ ?>
				<input type="checkbox" name="group_id[]" value="<?php echo $group['group_id'];?>"> <?php echo $group['group_name'];?> <br>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<td>
				<label>Don't send to people who have already received this newsletter</label>
			</td>
			<td>
				 <input type="checkbox" name="dont_send_duplicate" value="true" checked>
			</td>
		</tr>
		<tr>
			<td>
				<label>Schedule send for a later date </label>
			</td>
			<td>
				<div class="form_field"><input type="text" name="send_later" value="" size="10"></div> (date format: YYYY-MM-DD)
			</td>
		</tr>
		<tr>
			<td>
				
			</td>
			<td>
				 <input type="submit" name="send" value="Send<?php echo (count($sends))?' again':'';?>!" class="submit green">
			</td>
		</tr>
	</table>
</div>


<?php
// see if pending sends exist:
$pending = $newsletter->get_pending_sends($db,$newsletter_id);
if($pending){
	?>
	
	

		<h2><span>Pending Sends for this newsletter:</span></h2>
		
		<div class="box">
			<table cellpadding="5">
				<tr>
					<td>Newsletter</td>
					<td>Start Send</td>
					<td>Progress</td>
					<td>Action</td>
				</tr>
				<?php
				foreach($pending as $send){
					?>
					<tr>
						<td><?php echo $send['subject'];?></td>
						<td><?php echo $send['start_date'];?></td>
						<td><?php echo $send['progress'];?></td>
						<td><a href="?p=send&send_id=<?php echo $send['send_id'];?>">Continue Sending</a></td>
					</tr>
					<?php
				}
				?>
			</table>
				
		</div>
	<?
}


// see if previous sends exist
if($sends){
	?>
	
<h2><span>Previous Sends of this Newsletter</span></h2>
	
<div class="box">
	<table cellpadding="5">
		<tr>
			<td>Sent Date</td>
			<td>Sent To</td>
			<td>Opened By</td>
			<td>Unsubscribed</td>
			<td>Bounces</td>
			<td>Link Clicks</td>
			<td></td>
		</tr>
		<?php
		foreach($sends as $send){ 
			$send = $newsletter->get_send($db,$send['send_id']);
			?>
		<tr>
			<td>
				<?php echo date("Y-m-d",$send['start_time']);?>
			</td>
			<td>
				<?php echo count($send['sent_members']);?> members
			</td>
			<td>
				<?php echo count($send['opened_members']);?> members
			</td>
			<td>
				<?php echo count($send['unsub_members']);?> members
			</td>
			<td>
				<?php echo count($send['bounce_members']);?> members
			</td>
			<td>
				<a href="?p=stats&newsletter_id=<?php echo $newsletter_id;?>&send_id=<?php echo $send['send_id'];?>">View Stats</a>
			</td>
		</tr>
		<?php } ?>
		
	</table>
</div>

	<?php
}
?>

<h2><span>Other actions</span></h2>
<div class="box">
	<a href="#" onclick="if(confirm('Are you sure, you want to delete this newsletter ?')){ window.location.href='?p=open&newsletter_id=<?php echo $newsletter_id;?>&delete=true'; } return false;" class="submit orange">Delete Newsletter</a>
</div>
</form>