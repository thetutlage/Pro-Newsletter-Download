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

<h1>Newsletter Campaigns</h1>


<h2 style="float: left;"><span>List of All Campaigns</span></h2>


<a href="?p=campaign_open&campaign_id=new" class="submit orange right_float">Create new Campaign</a>


<div class="box">
	<table cellpadding="5" class="stats">
		<tr>
			<th>Campaign Name</th>
			<th>Number of Members</th>
			<th>Number of Newsletters</th>
			<th>Action</th>
		</tr>
		<?php
		$campaigns = $newsletter->get_campaigns($db);
		foreach($campaigns as $n){ 
			$n = $newsletter->get_campaign($db,$n['campaign_id']);
			?>
		<tr>
			<td>
				<?php echo $n['campaign_name'];?>
			</td>
			<td>
				<?php echo mysql_num_rows($n['members_rs']); ?>
			</td>
			<td>
				<?php echo mysql_num_rows($n['newsletter_rs']); ?>
			</td>
			<td>
				<a href="?p=campaign_open&campaign_id=<?php echo $n['campaign_id'];?>" class="submit gray">Open</a>
			</td>
		</tr>
		<?php } ?>
		
	</table>
</div>


