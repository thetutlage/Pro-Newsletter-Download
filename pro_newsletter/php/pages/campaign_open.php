<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

$campaign_id = $_REQUEST['campaign_id'];
if(!$campaign_id){
	// basic error checking.
	echo 'Please go back and pick a campaign';
	exit;
}

if(isset($_REQUEST['delete'])){
	if(_DEMO_MODE){
		echo "Sorry, cant delete campaigns in demo mode... ";
		exit;
	}
	$newsletter->delete_campaign($db,$campaign_id);
	ob_end_clean();
	header("Location: index.php?p=campaign");
	exit;
}
if(isset($_REQUEST['remove_newsletter_id'])){
	if(_DEMO_MODE){
		echo "Sorry, cant delete campaign newsletters in demo mode... ";
		exit;
	}
	$newsletter->delete_campaign_newsletter($db,$campaign_id,$_REQUEST['remove_newsletter_id']);
	ob_end_clean();
	header("Location: index.php?p=campaign_open&campaign_id=$campaign_id");
	exit;
}

$errors = array();
if(isset($_REQUEST['save']) && $_REQUEST['save']){
	
	
	$fields = array(
		"campaign_name" => $_REQUEST['campaign_name'],
	);
	
	// basic error checking, nothing fancy
	foreach($fields as $key=>$val){
		if(!trim($val)){
			$errors [] = 'Required field missing: '.ucwords(str_replace('_', ' ',$key));
		}
	}
	
	if(!$errors){
		
		$campaign_id = $newsletter->save_campaign($db,$fields,$campaign_id);
		if($campaign_id){
			
			if($_REQUEST['add_newsletter_id'] && $_REQUEST['add_send_time']){
				
				$newsletter->campaign_add_newsletter($db,$campaign_id,$_REQUEST['add_newsletter_id'],$_REQUEST['add_send_time']);
			}
		
			ob_end_clean();
			header("Location: index.php?p=campaign_open&campaign_id=$campaign_id");
			exit;
		
		}else{
			$errors [] = 'Failed to create campaign in database';
		}
	}
	
	
	foreach($errors as $error){
		echo '<div style="font-weight:bold; color:#FF0000; font-size:20px;">'.$error . '</div>';
	}
	
	
}


$campaign_data = $newsletter->get_campaign($db,$campaign_id);

?>

<h1>Campaigns</h1>

<form action="?p=campaign_open&save=true" method="post" id="create_form">

<input type="hidden" name="campaign_id" value="<?php echo $campaign_id;?>">

<h2><span>Campaign Details:</span></h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				<label>Campaign Name</label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="campaign_name" value="<?php echo $campaign_data['campaign_name'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				
			</td>
			<td>
				<input type="submit" name="save" value="Save" class="submit green">
			</td>
		</tr>
	</table>
</div>




<h2><span>Campaign Newsletters:</span></h2>

<div class="box">
	<table cellpadding="5" class="stats">
		<tr>
			<th>Newsletter</th>
			<th>Send When</th>
			<th>Action</th>
		</tr>
		<?php
		$campaign_newsletters = array();
		while($newsletter_row = mysql_fetch_assoc($campaign_data['newsletter_rs'])){
			$campaign_newsletters[] = $newsletter_row;
		}
		$newsletter_count = count($campaign_newsletters);
		foreach($campaign_newsletters as $newsletter_row){
			?>
			<tr>
				<td><?php echo $newsletter_row['subject'];?></td>
				<td><?php echo floor($newsletter_row['send_time']/86400);?> days after join</td>
				<td>
					<a href="?p=open&newsletter_id=<?php echo $newsletter_row['newsletter_id'];?>">Edit Newsletter</a>
					<a href="?p=campaign_open&campaign_id=<?php echo $campaign_id;?>&remove_newsletter_id=<?php echo $newsletter_row['newsletter_id'];?>" onclick="return confirm('Really remove from campaign?');" style="color:#FF0000;">Remove From Campaign</a>
				</td>
			</tr>
			<?php
		}
		?>
	</table>
	
</div>

<h2><span>Add Newsletter to Campaign:</span></h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>Choose Newsletter</td>
			<td>
				<div class="form_field"><select name="add_newsletter_id">
					<option value="">Select a newsletter</option>
					<?php 
					$newsletters = $newsletter->get_newsletters($db);
					foreach($newsletters as $newsletter){
						?>
						<option value="<?php echo $newsletter['newsletter_id'];?>"><?php echo $newsletter['subject'];?></option>
						<?php
					}
					?>
					
				</select>
				</div>
			</td>
		</tr>
		<tr>
			<td>Send Newsletter</td>
			<td>
				<div class="form_field"><input type="text" name="add_send_time" size="3" value="10"></div> days after member joins campaign
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" name="add" value="Add Newsletter" class="submit green">
			</td>
		</tr>
	</table>
	
</div>




<h2><span>Campaign Customer Status:</span></h2>

<p>Current server time: <?php echo date("d M Y h:i:sa");?> </p>

<div class="box">
	<table cellpadding="5" class="stats">
		<tr>
			<th>Member Name</th>
			<th>Email</th>
			<th>Joined Campaign</th>
			<th>Progress</th>
			<th>Next Newsletter</th>
			<th>Action</th>
		</tr>
		<?php
		while($member = mysql_fetch_assoc($campaign_data['members_rs'])){
			?>
			<tr>
				<td><?php echo $member['first_name'];?></td>
				<td><?php echo $member['email'];?></td>
				<td><?php echo date("d M Y h:i:sa",$member['join_time']);?></td>
				<td>
					<?php
					$member_next_newsletter = false;
					reset($campaign_newsletters);
					if(!$member['current_newsletter_id']){
						echo 'Nothing sent yet.';
						$member_next_newsletter = current($campaign_newsletters);
					}else{
						$x = 0;
						$member_newsletter = false;
						foreach($campaign_newsletters as $newsletter){
							if($member_newsletter){
								$member_next_newsletter = $newsletter;
								break;
							}
							if($newsletter['newsletter_id'] == $member['current_newsletter_id']){
								$member_newsletter = true;
							}
							$x++;
						}
						if($x == $newsletter_count){
							echo "Sent all $newsletter_count.";
						}else{
							echo "Sent $x of ".$newsletter_count.".";
						}
					}
					?>
				</td>
				<td>
					<?php
					// work out when the next newsletter will be sent to customer.
					if($member_next_newsletter){
						echo '<strong>'.$member_next_newsletter['subject'] . '</strong> on ';
						$send_time = $member['join_time'] + $member_next_newsletter['send_time'];
						echo date("d M Y h:i:sa",$send_time);
					}else{
						echo 'None';
					}
					?>
				</td>
				<td>
					<a href="?p=members&edit_member_id=<?php echo $member['member_id'];?>" class="submit gray">Edit Member</a>
					<a href="?p=campaign_open&delete_member_id=<?php echo $member['member_id'];?>" onclick="if(confirm('Really remove member from campaign?'))return true;else return false;" class="submit gray">Remove from Campaign</a>
				</td>
			</tr>
			<?php
		}
		?>
	</table>
		
</div>



<h2><span>Other actions</span></h2>
	
<div class="box">
	<a href="#" onclick="if(confirm('Really delete this campaign and all campaign history? Cannot undo!')){ window.location.href='?p=campaign_open&campaign_id=<?php echo $campaign_id;?>&delete=true'; } return false;" class="submit orange">Delete Campaign</a>
</div>


</form>