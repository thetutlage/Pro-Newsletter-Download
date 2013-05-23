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
	echo '<div class="newsletter_error">Newsletter does not exists, Shown template and stats might not accurate </div>';
}
$send_id = (int)$_REQUEST['send_id'];
if(!$send_id){
	// basic error checking.
	echo '<div class="newsletter_error">Newsletter does not exists, Shown template and stats might not accurate </div>';
}
$newsletter_data = $newsletter->get_newsletter($db,$newsletter_id);
// todo - check this send belongs to this newsletter, oh wel.
$send = $newsletter->get_send($db,$send_id);


// grab the full html content.
if(isset($_REQUEST['iframe'])){
	ob_end_clean();
	
	$template_html = $send['template_html'];
	if(preg_match_all('#<a href=["\'].*ext\.php\?t=lnk&id=(\d+)&#',$template_html,$matches)){
		$processed_links=array();
		foreach($matches[0] as $key => $val){
			$link_id = (int)$matches[1][$key];
			if(isset($processed_links[$link_id]))continue;
			$link = $newsletter->get_link($db,$link_id);
			//open_rates
			$template_html = preg_replace('/' . preg_quote($val,'/') . '/', '<span class="newsletter-click-span">'. count($link['open_rates']) . ' clicks</span>' . $val, $template_html);
			$processed_links[$link_id]=true;
		}
	}
	?>
	<style type="text/css">
	span.newsletter-click-span{
	background-color:#FFFFFF !important;
	border:1px solid #000000 !important;
	color:#000000 !important;
	font-size:10px !important;
	padding:2px !important;
	text-decoration:none !important;
	font-weight:normal !important;
	position:absolute !important;
	margin-left:0px !important;
	filter:alpha(opacity=50);
	-moz-opacity:0.5;
	-khtml-opacity: 0.5;
	opacity: 0.5;

	}
	</style>
	
	<?php
	echo $template_html;
	
	exit;
}


?>

<a href="?p=open&newsletter_id=<?php echo $newsletter_id;?>" class="submit orange right_float">&laquo; Back to newsletter</a>

<h2><span>Newsletter Link Clicks:</span></h2>

<iframe src="?p=stats&iframe=true&newsletter_id=<?php echo $newsletter_id;?>&send_id=<?php echo $send_id;?>" frameborder="0" style="border:1px solid #CCCCCC; width:700px; height:600px;"></iframe>


<h2><span>Newsletter Stats:</span></h2>

<div class="box">
	<table cellpadding="5" class="stats">
		<tr>
			<th>Send Date</th>
			<th>Email Subject</th>
			<th>Sent From</th>
			<th>Sent To</th>
			<th>Opened By</th>
			<th>Unsubscribed</th>
			<th>Bounces</th>
		</tr>
		<tr>
			<td>
				<?php echo date("Y-m-d H:i:s",$send['start_time']);?>
			</td>
			<td>
				<?php echo $newsletter_data['subject'];?>
			</td>
			<td>
				&lt;<?php echo $newsletter_data['from_name'];?>&gt; <?php echo $newsletter_data['from_email'];?> 
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
		</tr>
	</table>
</div>
		

<div class="box">
	<table cellpadding="5" class="stats">
		<tr>
			<th>Sent To</th>
			<th>Opened</th>
			<th>Unsubscribed</th>
			<th>Bounced</th>
		</tr>
		<?php foreach($send['sent_members'] as $sent_member){
			$member_data = $newsletter->get_member($db,$sent_member['member_id']);
			?>
			<tr>
				<td>
					<a href="?p=members&edit_member_id=<?php echo $sent_member['member_id'];?>">&lt;<?php echo $member_data['first_name'].' '.$member_data['last_name'];?>&gt; <?php echo $member_data['email'];?></a>
				</td>
				<td>
					<?php if(isset($member_data['opened'][$send_id])){
						echo 'YES: '.date("Y-m-d H:i:s",$member_data['opened'][$send_id]['open_time']);
					}else{
						echo 'NO';
					}
					?>
				</td>
				<td>
					<?php if(isset($member_data['unsubscribe'][$send_id])){
						echo 'YES: '.$member_data['unsubscribe'][$send_id]['unsubscribe_date'];
					}else{
						echo 'NO';
					}
					?>
				</td>
				<td>
					<?php if(isset($member_data['bounces'][$send_id])){
						echo 'YES: '.date("Y-m-d H:i:s",$member_data['bounces'][$send_id]['bounce_time']);
					}else{
						echo 'NO';
					}
					?>
				</td>
			</tr>
			<?
		}
		?>
	</table>
</div>
	