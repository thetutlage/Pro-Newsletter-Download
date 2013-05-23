<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

?>

<h1>Past Newsletters</h1>


<h2><span>List of All Newsletters</span></h2>


<div class="box">
	<table cellpadding="5" class="stats">
		<tr>
			<th>Email Subject</th>
			<th>Sent From</th>
			<th>Sent To</th>
			<th>Opened By</th>
			<th>Unsubscribed</th>
			<th>Bounces</th>
			<th>Action</th>
		</tr>
		<?php
		$newsletters = $newsletter->get_newsletters($db);
		foreach($newsletters as $n){ 
			$n = $newsletter->get_newsletter($db,$n['newsletter_id']);
			$sends = $newsletter->get_newsletter_sends($db,$n['newsletter_id']);
			?>
		<tr>
			<td>
				<?php echo $n['subject'];?>
			</td>
			<td>
				&lt;<?php echo $n['from_name'];?>&gt; <?php echo $n['from_email'];?> 
			</td>
			<td>
				<?php
				foreach($sends as $send){ 
					$send = $newsletter->get_send($db,$send['send_id']);
					?>
					
					<?php echo count($send['sent_members']);?> members on <?php echo date("Y-m-d",$send['start_time']);?> <br>
					
				<?php } ?>
			</td>
			<td>
				<?php
				foreach($sends as $send){ 
					$send = $newsletter->get_send($db,$send['send_id']);
					?>
					
					<?php echo count($send['opened_members']);?> members <br>
					
				<?php } ?>
			</td>
			<td>
				<?php
				foreach($sends as $send){ 
					$send = $newsletter->get_send($db,$send['send_id']);
					?>
					
					<?php echo count($send['unsub_members']);?> members <br>
					
				<?php } ?>
			</td>
			<td>
				<?php
				foreach($sends as $send){ 
					$send = $newsletter->get_send($db,$send['send_id']);
					?>
					
					<?php echo count($send['bounce_members']);?> members <br>
					
				<?php } ?>
			</td>
			<td>
				<a href="?p=open&newsletter_id=<?php echo $n['newsletter_id'];?>" class="submit gray">Stats/Send</a>
				<a href="?p=create&newsletter_id=<?php echo $n['newsletter_id'];?>" class="submit orange">Edit</a>
			</td>
		</tr>
		<?php } ?>
		
	</table>
</div>


