<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

if($sync_id!='new' && $sync_id){
	$sync = $newsletter->get_sync($db,$sync_id);
}else{
	$sync = array();
}
$groups = $newsletter->get_groups($db);
?>

<h2>Sync Settings:</h2>
<form action="?p=members_sync&save=true" method="post" id="create_form" enctype="multipart/form-data">
<input type="hidden" name="sync_id" value="<?php echo $sync_id;?>">

<div class="box">
	<p>Name this syncronization (eg: Shopping cart members)</p>
	<table cellpadding="4">
		<tr>
			<td>Sync Name:</td>
			<td><input type="text" name="sync_name" id="sync_name" value="<?php echo $sync['sync_name'];?>"></td>
		</tr>
	</table>
	
	
	<p>Please enter the MySQL connection details for the database you wish to sync with:</p>
	<table cellpadding="4">
		<tr>
			<td>Database Name:</td>
			<td><input type="text" name="db_name" id="db_name" value="<?php echo $sync['db_name'];?>"></td>
		</tr>
		<tr>
			<td>Database Username:</td>
			<td><input type="text" name="db_username" id="db_username" value="<?php echo $sync['db_username'];?>"></td>
		</tr>
		<tr>
			<td>Database Password:</td>
			<td><input type="text" name="db_password" id="db_password" value="<?php echo $sync['db_password'];?>"></td>
		</tr>
		<tr>
			<td>Database Host:</td>
			<td><input type="text" name="db_host" id="db_host" value="<?php echo $sync['db_host'];?>"></td>
		</tr>
	</table>
	
	<p>Please enter the information for the database table you wish to sync with:</p>
	<table cellpadding="4">
		<tr>
			<td>Table Name:</td>
			<td><input type="text" name="db_table" id="db_table" value="<?php echo $sync['db_table'];?>"></td>
		</tr>
		<tr>
			<td>Primary Key Name:</td>
			<td><input type="text" name="db_table_key" id="db_table_key" value="<?php echo $sync['db_table_key'];?>"></td>
		</tr>
		<tr>
			<td>Email Key:</td>
			<td><input type="text" name="db_table_email_key" id="db_table_email_key" value="<?php echo $sync['db_table_email_key'];?>"></td>
		</tr>
		<tr>
			<td>First Name Key:</td>
			<td><input type="text" name="db_table_fname_key" id="db_table_fname_key" value="<?php echo $sync['db_table_fname_key'];?>"></td>
		</tr>
		<tr>
			<td>Last Name Key:</td>
			<td><input type="text" name="db_table_lname_key" id="db_table_lname_key" value="<?php echo $sync['db_table_lname_key'];?>"></td>
		</tr>
	</table>
	
	<p>All members in this sync should be added to these local groups:</p>
	<table cellpadding="4">
		<tr>
			<td>Groups:</td>
			<td>
				<?php
				foreach($groups as $group){ ?>
				<input type="checkbox" name="group_id[]" value="<?php echo $group['group_id'];?>" <?php echo ($sync['groups'][$group['group_id']])?'checked':'';?>> <?php echo $group['group_name'];?> <br>
				<?php } ?>
			</td>
		</tr>
	</table>
	
	<p>Advanced</p>
	<table cellpadding="4">
		<tr>
			<td>Redirect to this url when trying to edit this members details. {USER_ID} dynamic field:</td>
			<td><input type="text" name="edit_url" id="edit_url" value="<?php echo $sync['edit_url'];?>"></td>
		</tr>
	</table>
	
	<p>Once you are happy with these details click save below and we will test the connection.</p>
	<table cellpadding="4">
		<tr>
			<td><input type="submit" name="save" value="Save &amp; Import Members"></td>
		</tr>
	</table>
	
</div>
</form>