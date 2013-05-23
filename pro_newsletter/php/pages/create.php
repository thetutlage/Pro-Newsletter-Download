<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce, phpMailer
 * InSite Contribution :- Andy Charles
 * 
**/

$page_title = "Create Newsletter";

$input_method = 'manual';

$newsletter_id = (int)$_REQUEST['newsletter_id'];
if(!$newsletter_id){
	$newsletter_id = 'new';
}
$newsletter_content_id = ($newsletter_id=='new') ? 'new' : false;
if(isset($_REQUEST['newsletter_content_id'])){
	$newsletter_content_id = (int)$_REQUEST['newsletter_content_id'];
	if(!$newsletter_content_id){
		$newsletter_content_id = 'new';
	}
}

$errors = array();
if($_REQUEST['save']){
	
	// save the newsletter 
	// check required fields.
	$fields = array(
		"template" => $_REQUEST['template'],
		"subject" => $_REQUEST['subject'],
		"from_name" => $_REQUEST['from_name'],
		//"content" => $_REQUEST['newsletter_content'], // not required any more
		"from_email" => $_REQUEST['from_email'],
		"bounce_email" => $_REQUEST['bounce_email'],
	);
	
	// basic error checking, nothing fancy
	foreach($fields as $key=>$val){
		if(!trim($val)){
			$errors [] = 'Required field missing: '.ucwords(str_replace('_', ' ',$key));
		}
	}
	
	if(isset($_REQUEST['newsletter_content'])){
		// old static html way:
		$fields['content'] = $_REQUEST['newsletter_content'];
	}
	if(!$errors){
		$newsletter_id = $newsletter->save($db,$newsletter_id,$fields);
	}
	
	if($newsletter_content_id){
		if(!$errors){
			//$newsletter_id = $newsletter->save($db,$newsletter_id,$fields);
			$newsletter_content_id = $newsletter->save_content($db,$newsletter_id,$newsletter_content_id);
			//echo "Save $newsletter_id  '$newsletter_content_id'";exit;
			if($newsletter_id && $newsletter_content_id){
				// save newsletter content thumb and main image.
				if(is_uploaded_file($_FILES['image_thumb']['tmp_name'])){
					if(!_DEMO_MODE){
						$folder = _IMAGES_DIR.'newsletter-'.$newsletter_id.'/';
						if(!is_dir($folder)){
							mkdir($folder);
						}
						if(is_dir($folder)){
							move_uploaded_file($_FILES['image_thumb']['tmp_name'], $folder.$newsletter_content_id.'-thumb.jpg');
							foreach(glob($folder.'_thumb/'.$newsletter_content_id.'-thumb.jpg*') as $thumb){
								unlink($thumb);
							}
						}
					}else{
						$errors[]="Image uploads disabled in demo mode sorry.";
					}
				}
				if(is_uploaded_file($_FILES['image_main']['tmp_name'])){
					if(!_DEMO_MODE){
						$folder = _IMAGES_DIR.'newsletter-'.$newsletter_id.'/';
						if(!is_dir($folder)){
							mkdir($folder);
						}
						if(is_dir($folder)){
							move_uploaded_file($_FILES['image_main']['tmp_name'], $folder.$newsletter_content_id.'.jpg');
							foreach(glob($folder.'_thumb/'.$newsletter_content_id.'.jpg*') as $thumb){
								unlink($thumb);
							}
						}
					}else{
						$errors[]="Image uploads disabled in demo mode sorry.";
					}
				}
			}else if(!$newsletter_id){
				$errors [] = 'Failed to create newsletter in database';
			}
		}
		
	}
	
	if(is_uploaded_file($_FILES['image']['tmp_name'])){
		if(!_DEMO_MODE){
			move_uploaded_file($_FILES['image']['tmp_name'], _IMAGES_DIR.basename($_FILES['image']['name']));
		}else{
			$errors[]="Image uploads disabled in demo mode sorry.";
		}
	}
	if(is_uploaded_file($_FILES['attachment']['tmp_name'])){
		if(!_DEMO_MODE){
			move_uploaded_file($_FILES['attachment']['tmp_name'], _IMAGES_DIR.basename($_FILES['attachment']['name']));
		}else{
			$errors[]="Attachment uploads disabled in demo mode sorry.";
		}
	}
	if($_REQUEST['next']){
		ob_end_clean();
		header("Location: index.php?p=open&newsletter_id=$newsletter_id");
		exit;
	}

	if(isset($errors) && ($errors >= 1)) {
		echo '<div id="newsletter_error" class="newsletter_error">';
	foreach($errors as $error){
		echo $error.'<br />';
	}
		echo '</div>';
	}
	
	/*if(!$errors){
		if(isset($_REQUEST['next_newsletter_content_id']) && $_REQUEST['next_newsletter_content_id']){
			ob_end_clean();
			header("Location: index.php?p=create&newsletter_id=$newsletter_id&newsletter_content_id=".$_REQUEST['next_newsletter_content_id']);
			exit;
		}
	}*/
}

if(isset($_REQUEST['next_action_key'])){
	switch ($_REQUEST['next_action_key']){
		case 'delete_content':
			if(!$errors){
				$newsletter->delete_content($db,$newsletter_id,(int)$_REQUEST['next_action_val']);
				ob_end_clean();
				header("Location: index.php?p=create&newsletter_id=$newsletter_id");
				exit;
			}
			break;
		case 'swap_content':
			if(!$errors){
				ob_end_clean();
				header("Location: index.php?p=create&newsletter_id=$newsletter_id&newsletter_content_id=".$_REQUEST['next_action_val']);
				exit;
			}
			break;
		case 'preview':
			if(!$errors){
				ob_end_clean();
				header("Location: index.php?p=preview&newsletter_id=$newsletter_id&hide_menu=true");
				exit;
			}
			break;
		case 'preview_email':
			if(!$errors){
				ob_end_clean();
				header("Location: index.php?p=preview&newsletter_id=$newsletter_id&hide_menu=true&email=".urlencode($_REQUEST['next_action_val']));
				exit;
			}
			break;
	}
}

$templates = $newsletter->get_templates();
$default_template = $newsletter->settings['default_template'];

if($newsletter_id!='new'){
	$newsletter_data = $newsletter->get_newsletter($db,$newsletter_id);
	$current_template = $newsletter_data['template'];
}else{
	$current_template = $default_template;
	/*ob_start();
	if(is_file(_TEMPLATE_DIR.$default_template."/inside.html")){
		include(_TEMPLATE_DIR.$default_template."/inside.html");
	}
	$inside_content = ob_get_clean();*/
	// find a new name for this newsletter.
	$newsletter_name = date('F') . ' Newsletter';
	if(_DEMO_MODE){
		$all_newsletters = $newsletter->get_newsletters($db);
		$x=1;
		while(true){
			$this_name = $newsletter_name . " $x";
			$has=false;
			foreach($all_newsletters as $n){
				if($n['subject'] == $this_name){
					$has=true;
					break;
				}
			}
			$x++;
			if(!$has){
				$newsletter_name=$this_name;
				break;
			}
		}
	}
	$newsletter_data = arraY(
		"template"=>$default_template,
		"subject"=>$newsletter_name,
		"from_name"=>$newsletter->settings['from_name'],
		"from_email"=>$newsletter->settings['from_email'],
		"bounce_email"=>$newsletter->settings['bounce_email'],
		//"content"=>htmlspecialchars($inside_content),
	);
}


if($newsletter_id == 'new' || $_REQUEST['template_reload']){
	ob_start();
	if(is_file(_TEMPLATE_DIR.$current_template."/inside.html")){
		include(_TEMPLATE_DIR.$current_template."/inside.html");
	}
	$inside_content = ob_get_clean();
	$newsletter_data['content'] = $inside_content;
}




?>

<script language="javascript" type="text/javascript" src="layout/js/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
tinyMCE.init({
	mode: "exact",
    elements : "newsletter_content",
	theme : "advanced",
	plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,insertdatetime,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,inlinepopups",
	height : '300px',
	width : '650px',
	// Theme options
	theme_advanced_buttons1 : "undo,redo,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor",
	theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	setup : function(ed){
		ed.onNodeChange.add(function(ed, cm, n, co) {
			if(n.nodeName == 'IMG'){
				// selected an image, load the alt tag and size over on the right.
				$('#image_size').val('replace');
				$('#image_alt').val(ed.dom.getAttrib(n, 'alt'));
				image_width = $(n).width();
				image_height = $(n).height();
			}else{
				$('#image_size').val('');
				$('#image_alt').val('Image Description');
				image_width = image_height = 0;
			}
      });

	}
});
</script>



<h1>Create Newsletter</h1>

<form action="?p=create&save=true#editor" method="post" id="create_form" enctype="multipart/form-data">

<input type="hidden" name="newsletter_id" value="<?php echo $newsletter_id;?>">
<input type="hidden" name="template" id="template" value="<?php echo $newsletter_data['template'];?>">
<input type="hidden" name="template_reload" id="template_reload" value="">
<input type="hidden" name="newsletter_content_id" id="newsletter_content_id" value="<?php echo $newsletter_content_id;?>">
<input type="hidden" name="next_action_key" id="next_action_key" value="">
<input type="hidden" name="next_action_val" id="next_action_val" value="">

<h2><span>Step 1:</span> Template</h2>

<div class="box templates">
	<?php
	foreach($templates as $template){
	?>
	
	<div class="template<?php echo ($newsletter_data['template']==$template['name'])?' selected':'';?>" rel="<?php echo $template['name'];?>">
		<img src="<?php echo $template['dir'];?>/preview.jpg" border="0">
		<div style="clear:both; font-size: 10px;"><?php echo $template['name'];?></div>
	</div>
	
	<?php } ?>
	<br class="clear">
</div>
<script language="javascript">
$('.template').click(function(){
	$('.templates .selected').removeClass('selected');
	$(this).addClass('selected');
	$('#template').val($(this).attr('rel'));
	// prompt to re-load with content available
	<?php if($input_method != 'wizard'){ ?>
	var reload_content = confirm('Would you like to use this template inner content (this will replace any existing content below with template defaults)');
	if(reload_content){
		$('#template_reload').val('1');
	}
	<?php } ?>
	$('#create_form')[0].action='?p=create&save=true#editor'; 
	$('#create_form')[0].target='_self';
	$('#create_form')[0].submit();
	return false;
});
</script>


<h2><span>Step 2:</span> Settings</h2>

<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				<label>Email Subject<span class="required">*</span></label>
			</td>
			<td>
			<div class="form_field"><input type="text" class="input" name="subject" value="<?php echo $newsletter_data['subject'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>From Name<span class="required">*</span></label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="from_name" value="<?php echo $newsletter_data['from_name'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>From Email<span class="required">*</span></label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="from_email" value="<?php echo $newsletter_data['from_email'];?>"></div>
			</td>
		</tr>
		<tr>
			<td>
				<label>Bounce Email<span class="required">*</span></label>
			</td>
			<td>
				<div class="form_field"><input type="text" class="input" name="bounce_email" value="<?php echo $newsletter_data['bounce_email'];?>"></div> (bounced newsletters get sent to this address)
			</td>
		</tr>
		<tr>
			<td>
				
			</td>
			<td>
				<input type="submit" name="save_settings" value="Save Settings" class="submit orange">
			</td>
		</tr>
	</table>
</div>


<h2><span>Step 3:</span> Content</h2>


<script language="javascript" type="text/javascript">
	var image_width=0;
	var image_height=0;
</script>

<!--<input type="radio" name="input_method" value="wizard" <?php if(!$input_method||$input_method=='wizard') echo ' checked';?>> Wizard <input type="radio" name="input_method" value="manual"<?php if($input_method=='manual') echo ' checked';?>> Manual HTML -->


<div class="box">
	<!--<p>Here you can copy and paste from Word, or simply type out your newsletter. The above template will be applied to this content.<br>
	Note: if you copy and paste from Word, please use the 'Paste from Word' button. </p>-->
	<a name="editor"></a>
	<?php
	if($input_method == 'wizard'){ 
		// include the wizzard file from the template
		$newsletter_contents = $newsletter->get_newsletter_contents($db, $newsletter_id);
		$group_titles = array();
		if($newsletter_contents){
			foreach($newsletter_contents as $key=>$val){
				$group_titles[$val['group_title']] = true;
			}
		}
		include(_TEMPLATE_DIR.$current_template.'/wizard_ui.php');
	}else{
	?>
<div class="preview_editor">
	<input type="submit" name="preview1" value="Open Preview" onclick="$('#next_action_key').val('preview');">
</div><!-- end preview_editor -->
	<table cellpadding="5">
		<tr>
			<td valign="top">
				<table cellpadding="5">
					<tr>
						<td valign="top">
							<b> Note:- Do not change values inside curley barces "{ }"</b>
						</td>
					</tr>
						<tr>
						<td>
							<textarea name="newsletter_content" id="newsletter_content"><?php echo htmlspecialchars($newsletter_data['content']);?></textarea>
						</td>
						</tr>
						<tr>
						<td valign="top">
							<div id="image_insert">
								<h3>Insert image into article:</h3>
								<?php if($input_method=='wizard'){ ?>
								Your "main image" above will display at the top of your article.<br>
								<?php } ?>
								<div class="form_field">
								<select name="image_url" id="image_url">
								<option value=""> #1: select an image </option>
								<?php
								foreach($newsletter->get_images($db,$newsletter_id) as $attachment){ 
								?>
								<option value="<?php echo $attachment['link'];?>"><?php echo $attachment['name'];?></option>
								<?php } ?>
								</select> 
								</div>
								<br>
								<div class="form_field">
								<select name="image_size" id="image_size" onchange="$('#image_alt')[0].focus().select();">
								<option value=""> #2: select size </option>
								<option value="replace">Replace Existing</option>
								<option value="100x100">Thumbnail #1 - 100x100</option>
								</select>
								</div>
								<br>
								<div class="form_field">
								<input type="text" name="image_alt" id="image_alt" value="Image Description" onfocus="if(this.value=='Image Description')this.value='';">
								</div>
								<br>
								<input type="button" name="image_insert" onclick="insert_image();" value="Insert Image" class="submit gray">
								<a href="#" onclick="$('#image_insert').hide(); $('#image_upload').show(); return false;" class="submit orange">Upload New Image</a>
							</div>
							<div id="image_upload" style="display:none;">
								<h3>Upload new Image:</h3>
								<div class="form_field">
								<input type="file" name="image" value="" size="6">
								</div>
								<br />
								<input type="submit" name="attach" value="Upload" onclick="this.form.action='?p=create&save=true#editor'; this.form.target='_self';" class="submit green">
								<a href="#" onclick="$('#image_upload').hide(); $('#image_insert').show(); return false;" class="submit orange">Insert Existing Image</a>
							</div>
							<script language="javascript">
							function insert_image(){
								var src = $('#image_url').val();
								$('#image_url').val('');
								var size = $('#image_size').val();
								$('#image_size').val('');
								var alt = $('#image_alt').val();
								if(alt=='Image Description')alt='';
								$('#image_alt').val('Image Description');
								// validation:
								if(!src || src == '')return;
								// is the user currently clicking on an image:
								
								var imghtml = '<img src="' + src + '" alt="'+alt+'"';
								if(size == 'replace'){
								}else if(size != ''){
									// split and use size.
									var foo = size.split('x');
									image_width = foo[0];
									image_height= foo[1];
								}else{
									image_width = image_height = 0;
								}
								if(image_width) imghtml += ' width="'+image_width+'"';
								if(image_height) imghtml += ' height="'+image_height+'"';
								imghtml += ' />';
								tinyMCE.execCommand('mceInsertRawHTML',false, imghtml);
							}
							</script>
							<!--<h3>Attachments (beta):</h3>
							<?php
							foreach($newsletter->get_attachments($db,$newsletter_id) as $attachment){ 
							?>
							<a href="<?php echo $attachment['link'];?>" target="_blank"><?php echo $attachment['name'];?></a> <input type="checkbox" name="del_attachment_id[]" value="<?php echo $attachment['name'];?>">delete <br>
							<?php } ?>
							Upload: <input type="file" name="attachment" value="" size="6">
							<hr>
							<input type="submit" name="attach" value="Save">-->
						</td>
					</tr>
				</table>
			</td>
		</tr>
		
	</table>
	<?php } ?>
</div>

<h2><span>Step 4:</span> Preview</h2>
<div class="box">
	<table cellpadding="5">
		<tr>
			<td>
				Preview in Email
			</td>
			<td>
				<div class="form_field">
				 <input type="text" name="preview_email" id="preview_email" value="<?php echo $newsletter->email;?>">
				 </div>
			</td>
			<td><input type="submit" name="preview2" value="Send Preview" onclick="$('#next_action_key').val('preview_email');$('#next_action_val').val($('#preview_email').val());" class="submit gray"> </td>
		</tr>
	</table>
	
</div>


<h2><span>Step 5:</span> Save</h2>

<div class="box">
	<p>Once you are happy with your preview, click this button to go to the next step.</p>
	<input type="submit" name="save_cont" value="Save Newsletter and Continue to next step..." onclick="this.form.action='?p=create&save=true&next=true'; this.form.target='_self';" 				 <input type="submit" name="preview2" value="Send Preview" onclick="$('#next_action_key').val('preview_email');$('#next_action_val').val($('#preview_email').val());" class="submit green"> 
</div>



</form>
