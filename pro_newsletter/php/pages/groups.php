<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/


if($_REQUEST['save'] && $_REQUEST['group_name']){
	
	$group_id = $newsletter->save_group($db,$_REQUEST['group_id'],$_REQUEST['group_name'],$_REQUEST['public']);
	if($group_id){
		ob_end_clean();
		header("Location: index.php?p=groups");
		exit;
	}
	
}
if((int)$_REQUEST['delete']){
	
	$newsletter->delete_group($db,(int)$_REQUEST['delete']);
	ob_end_clean();
	header("Location: index.php?p=groups");
	exit;

	
}

?>

<h1>Groups</h1>

<p>
Manage your groups and their visibility with your clients and users.
</p>
<form action="?p=groups&save=true" method="post" id="create_form">
<?php
if($_REQUEST['edit_group_id']){ 
	$group_id = (int)$_REQUEST['edit_group_id'];
	$group = $newsletter->get_group($db,$group_id);
	?>

	<input type="hidden" name="group_id" value="<?php echo $group_id;?>">
	
	<h2><span>Edit Group</span></h2>
	
	<div class="box">
		<table cellpadding="5">
			<tr>
				<td>
					<label>Group Name</label>
				</td>
				<td>
					<div class="form_field"><input type="text" name="group_name" value="<?php echo $group['group_name'];?>"></div>
				</td>
			</tr>
			<tr>
				<td>
					<label>Public</label>
				</td>
				<td>
					<input type="checkbox" name="public" value="1" <?php echo ($group['public']) ? ' checked':'';?>>public users can join this group
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
	
	
	<div class="box">
		<input type="button" name="del" value="Delete this group" onclick="if(confirm('Really remove this group?')){ window.location.href='?p=groups&delete=<?php echo $group_id;?>'; }" class="submit orange">
	</div>

<?php
}else{ 
	
?>
<h2><span>List of Groups</span></h2>

<input type="hidden" name="group_id" value="new">

<div class="box">
	<table cellpadding="5" width="100%">
		<tr>
			<td>Group Name</td>
			<td>Public</td>
			<td>Members</td>
			<td></td>
		</tr>
		<?php
		$groups = $newsletter->get_groups($db);
		foreach($groups as $group){ 
			$members = $newsletter->get_members($db,$group['group_id']);
			?>
		<tr>
			<td>
				<?php echo $group['group_name'];?>
			</td>
			<td>
				<?php echo ($group['public']) ? 'Yes' : 'No';?>
			</td>
			<td>
				<?php echo mysql_num_rows($members);?>
			</td>
			<td>
				<a href="?p=groups&edit_group_id=<?php echo $group['group_id'];?>" class="submit gray">Edit Group</a>
			</td>
		</tr>
		<?php } ?>
		
	</table>
</div>


<h2><span>Add New Group</span></h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				<label>Group Name</label>
			</td>
			<td>
				<div class="form_field"><input type="text" name="group_name" id="group_name" value=""></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>Public</label>
			</td>
			<td>
				<input type="checkbox" name="public" id="public" value="1"> public users can join this group
			</td>
		</tr>
		<tr>
			<td>
				
			</td>
			<td>
				 <input type="submit" name="save" value="Add" class="submit green">
			</td>
		</tr>
	</table>
	
</div>

<?php } ?>




</form>