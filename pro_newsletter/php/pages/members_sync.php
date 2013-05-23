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

<h1>Members Sync</h1>

<p>Syncronize your members list with an existing mysql database table.</p>

<?php if($_REQUEST['sync_id']){
	$sync_id = $_REQUEST['sync_id'];
	if($_REQUEST['save']){
		$fields = array(
			"sync_name"=>$_REQUEST['sync_name'],
			"edit_url"=>$_REQUEST['edit_url'],
			"db_username"=>$_REQUEST['db_username'],
			"db_password"=>$_REQUEST['db_password'],
			"db_host"=>$_REQUEST['db_host'],
			"db_name"=>$_REQUEST['db_name'],
			"db_table"=>$_REQUEST['db_table'],
			"db_table_key"=>$_REQUEST['db_table_key'],
			"db_table_email_key"=>$_REQUEST['db_table_email_key'],
			"db_table_fname_key"=>$_REQUEST['db_table_fname_key'],
			"db_table_lname_key"=>$_REQUEST['db_table_lname_key'],
			"groups"=>$_REQUEST['group_id'],
		);
		$sync_id = $newsletter->save_sync($db,$fields,$sync_id);
		$status = $newsletter->test_sync($db,$sync_id);
		if($status){
			$newsletter->run_sync($db,$sync_id);
		}
		//echo 'Sync Complete';exit;
		ob_end_clean();
		header("Location: index.php?p=members_sync&saved=true");
		exit;
	}
	include("members_sync_form.php");
}else{ 
	$syncs = $newsletter->get_syncs($db);
	?>
	<a href="?p=members_sync&sync_id=new">Create new sync</a>
	<?php
	if($syncs){ ?>
	<h2>Existing Syncronizations:</h2>
	
	<table cellpadding="5">
		<thead>
			<tr>
				<th>Sync Name</th>
				<th>DB Host</th>
				<th>DB Name</th>
				<th>Table Name</th>
				<th>Number of Members</th>
				<th>Last Sync</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($syncs as $sync){  
				$sync = $newsletter->get_sync($db,$sync['sync_id']);
				?>
				<tr>
					<td><?php echo $sync['sync_name'];?></td>
					<td><?php echo $sync['db_host'];?></td>
					<td><?php echo $sync['db_name'];?></td>
					<td><?php echo $sync['db_table'];?></td>
					<td><?php echo $sync['member_count'];?></td>
					<td><?php echo print_date($sync['last_sync'],true);?></td>
					<td><a href="?p=members_sync&sync_id=<?php echo $sync['sync_id'];?>">Edit</a></td>
				</tr>
			
			<?php } ?>
		</tbody>
	</table>
	<?php
	}
}
?>
	

