<?php

/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce
 * InSite Contribution :- Andy Charles
 * 
**/

/*  Last try on this class otherwise i am gonna delete all crap .... Just kidding */
class newsletter{
	
	public $base_href;
	public $settings;
	private $db;
	private $ch;

	public function __construct(){
		if (!isset($_SERVER['REQUEST_URI']))
		{
	       $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1 );
	       if (isset($_SERVER['QUERY_STRING'])) { $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING']; }
	       $_SERVER['REQUEST_URI'] = '/' . ltrim($_SERVER['REQUEST_URI'],'/');
		}
		$this->base_href = $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
		$this->base_href = rtrim($this->base_href,'/');
	}
	public function init() {
		$this->db = db_connect();
		$this->get_settings($this->db);
	}
	public function login($db=false,$username,$password){
		if(!$db)$db = db_connect();
		// check with the db if this users exists, return the users
		// details so we can work out what type of member they are.
		$this->get_settings($db);
		if($this->settings['username']==$username && $this->settings['password']==$password){
			return true;
		}
		return false;
		
	}
	public function logout(){
		
	}

	public $uhash;
	
	public function get_templates(){
		$template_dirs = glob(_TEMPLATE_DIR."*");
		$templates = array();
		foreach($template_dirs as $template_dir){
			$templates[] = array(
				"dir"=>$template_dir,
				"name"=>basename($template_dir),
			);
		}
		return $templates;
	}
	
	public function get_template_html($template,$replace,$options=array()){
		$template_file = _TEMPLATE_DIR.basename($template)."/template.html";
		if(is_file($template_file)){
			extract($options);
			$template_html = file_get_contents($template_file);
		}else if($template){
			$template_html = $template;
		}else{
			$template_html = 'Template '.$template_file.' not found!';
		}
		foreach($replace as $key=>$val){
			$val = str_replace(array('\\', '$'), array('\\\\', '\$'), $val);
			$template_html = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$template_html);
		}
		
		return $template_html;
	}
	
	public function get_images($db,$newsletter_id){
		$dir = _IMAGES_DIR."";
		$files = array();
		if(is_dir($dir)){
			foreach(glob($dir."*") as $file){
				if(is_dir($file))continue;
				$files[] = array(
					"name"=>basename($file),
					"link"=>$file,
				);
			}
		}
		return $files;
	}
	public function get_attachments($db,$newsletter_id){
		$dir = _NEWSLETTERS_DIR."/newsletter-$newsletter_id/";
		$files = array();
		if(is_dir($dir)){
			foreach(glob($dir."/*") as $file){
				$files[] = array(
					"name"=>basename($file),
					"link"=>$file,
				);
			}
		}
		return $files;
	}
	
	public function fix_image_paths($data,$send_id=false,$dir='',$inc_http=true){
		$dir = trim($dir,'/');
		if(strlen($dir)){
			$dir.='/';
		}
		$db= db_connect();
		if($inc_http){
			$base = 'http://'.$this->base_href."/" . $dir;
		}else{
			$base = $dir;
		}
		// process links
		// links / iamges keep em different, that way we know if someone has actually CLICKED vs opened.
		foreach(array("href") as $type){
			preg_match_all('/'.$type.'=(["\'])([^"\']+)\1/',$data,$links);
			if(is_array($links[2])){
				foreach($links[2] as $link_id => $l){
					//if(!preg_match('/^\{/',$l) && !preg_match('/^#/',$l) && !preg_match('/^mailto:/',$l)){
					if(!preg_match('/^\{/',$l) && !preg_match('/^#/',$l) && !(preg_match('/^\w+:/',$l) && !preg_match('/^http/',$l))){
						//echo $links[0][$link_id] ."<br>";
						$search = preg_quote($links[0][$link_id],"/");
						//echo $search."<br>\n";
						$l = preg_replace("/[\?|&]phpsessid=([\w\d]+)/i",'',$l);
						$l = ltrim($l,'/');
						$newlink = ((!preg_match('/^http/',$l)) ? $base : '') . $l;
						if($send_id){
							// we are sending this out, we need to store a link to this in the db
							// to record clicks etc..
							$sql = "INSERT INTO `"._DB_PREFIX."link` SET link_url = '".mysql_real_escape_string($newlink)."'";
							$res = query($sql,$db);
							$link_id = mysql_insert_id($db);
							$newlink = 'http://'.$this->base_href.'/ext.php?t=lnk&id='.$link_id.'&sid={SEND_ID}&mid={MEMBER_ID}&mhash={MEMBER_HASH}';
						}
						$replace = $type.'="'.$newlink.'"';
						//echo $replace."<br>\n";
						//preg_match('/'.$search."/",$template,$matches);print_r($matches);
						$data = preg_replace('/'.$search.'/',$replace,$data,1);
					}
				}
			}
		}
		// process images.
		// only if inline_images isn't set.
		
		if($inc_http && (!isset($this->settings['inline_images']) || $this->settings['inline_images'] == 'no' || !$this->settings['inline_images'])){
			$base = 'http://'.$this->base_href."/" . $dir;
		}else{
			$base = $dir;
		}
		foreach(array("src","background") as $type){
			preg_match_all('/'.$type.'=(["\'])([^"\']+)\1/',$data,$links);
			//print_r($links);
			if(is_array($links[2])){
				foreach($links[2] as $link_id => $l){
					//if(!preg_match('/^\{/',$l) && !preg_match('/^#/',$l) && !preg_match('/^mailto:/',$l)){
					if(!preg_match('/^\{/',$l) && !preg_match('/^#/',$l) && !(preg_match('/^\w+:/',$l) && !preg_match('/^http/',$l))){
						//echo $links[0][$link_id] ."<br>";
						$search = preg_quote($links[0][$link_id],"/");
						//echo $search."<br>\n";
						$l = preg_replace("/[\?|&]phpsessid=([\w\d]+)/i",'',$l);
						$l = ltrim($l,'/');
						$newlink = ((!preg_match('/^http/',$l)) ? $base : '') . $l;
						if(!isset($this->settings['inline_images']) || $this->settings['inline_images'] == 'no' || !$this->settings['inline_images']){
							if($send_id){
								// we are sending this out, we need to store a link to this in the db
								// to record clicks etc..
								$sql = "INSERT INTO `"._DB_PREFIX."image` SET image_url = '".mysql_real_escape_string($newlink)."'";
								$res = query($sql,$db);
								$link_id = mysql_insert_id($db);
								$newlink = 'http://'.$this->base_href.'/ext.php?t=img&id='.$link_id.'&sid={SEND_ID}&mid={MEMBER_ID}';
							}
						}
						$replace = $type.'="'.$newlink.'"';
						//echo $replace."<br>\n";
						//preg_match('/'.$search."/",$template,$matches);print_r($matches);
						$data = preg_replace('/'.$search.'/',$replace,$data,1);
					}
				}
			}
		}
		return $data;
	}
	
	public function send_email($email_to,$email_subject,$email_contents,$email_from,$email_from_name,$options=array()){
		
		if(_DEMO_MODE){
	    	if(preg_match('/example/i',$email_to) || preg_match('/demo/i',$email_to) || preg_match('/test/i',$email_to)){
	    		return true;
	    	}
	    }
	    
		require_once("phpmailer/class.phpmailer.php");
		
		// this is a little hacky cos i copied it from other code i wrote.
		// it works well though.
		
		
		$options ['to']=$email_to;
		$options ['subject']=$email_subject;
		$options ['from']=$email_from;
		$options ['from_name']=$email_from_name;
		
		
		
		foreach(array("to","cc","bcc","reply_to") as $type){
			if(!$options[$type]){
				$options[$type]=array();
			}else if(!is_array($options[$type])){
				$emails = explode(",",$options[$type]);
				$options[$type]=array();
				foreach($emails as $e){
					if($e=trim($e)){
						$options[$type][]=$e;
					}
				}
			}
		}
		
	    $mail = new PHPMailer();
		$mail->CharSet = 'UTF-8';
		 
	    $mail->SetLanguage("en", 'phpmailer/language/');
	    if(_MAIL_SMTP){
		    $mail->IsSMTP(); 
		    // turn on SMTP authentication 
		    $mail->SMTPAuth = _MAIL_SMTP_AUTH;     
		    $mail->Host     = _MAIL_SMTP_HOST; 
		    if(_MAIL_SMTP_AUTH){
			    $mail->Username = _MAIL_SMTP_USER;
			    $mail->Password = _MAIL_SMTP_PASS;
		    }
	    }
	    
	    
	    $mail->From     = $options['from'];
	    if($options['from_name']){
	        $mail->FromName = $options['from_name'];
	    }
	    $mail->Subject     = $options['subject'];
	    // turn on HTML emails:
        $mail->isHTML(true);
	    
	    /*if($options['attachment'] && !is_array($options['attachment'])){
	    	$attachments = explode(",",$options['attachment']);
	    	$options['attachment']=array();
	    	foreach($attachments as $a){
	    		if(is_file($a)){
	    			$mail->AddAttachment($a);
	    		}
	    	}
	    }*/
	    
	    
	    //
	    // use MsgHTML() so it does the inine images etc...
	    if(!isset($this->settings['inline_images']) || $this->settings['inline_images'] == 'no' || !$this->settings['inline_images']){
	    	// use normal html:
	    	$mail->Body    = $email_contents;
	    }else{
	    	// set images inline:
	    	$mail->MsgHTML($email_contents);
	    }
	    
	    
	    // setup to,bcc,cc
	    
	    foreach($options['to'] as $email){
	    	$mail->AddAddress($email);
	    }
	    foreach($options['cc'] as $email){
	    	$mail->AddCC($email);
	    }
	    foreach($options['bcc'] as $email){
	    	$mail->AddBCC($email);
	    }
	    foreach($options['reply_to'] as $email){
	    	$mail->AddReplyTo($email);
	    }
	    
	    if($options['bounce_email']){
	    	$mail->Sender = $options['bounce_email'];
	    }
	    if($options['message_id']){
	    	$mail->MessageID = $options['message_id'];
	    }
	   
	    if(!$mail->Send()){
	    	echo $mail->ErrorInfo;
	    	print_r($mail->smtp->error);
	        return false;
	    }
	    return true;
	}
	
	
	public function save($db=false,$newsletter_id='new',$fields){
		if(!$db)$db = db_connect();
		if(!count($fields))return;
		if($newsletter_id == 'new'){
			$sql = "INSERT INTO "._DB_PREFIX."newsletter SET create_date = NOW() ";
			$where = '';
		}else{
			$sql = "UPDATE "._DB_PREFIX."newsletter SET create_date = NOW() ";
			$where = " WHERE newsletter_id = '".mysql_real_escape_string($newsletter_id)."' LIMIT 1";
		}
		
		foreach($fields as $key=>$val){
			$val = trim($val);
			if(!$val)continue;
			/*if($key!='content'){
				$val = htmlspecialchars($val);
			}*/
			$sql .= ", `".$key."` = '".mysql_real_escape_string($val)."'";
		}
		$sql.=$where;
		$res = query($sql,$db);
		if($newsletter_id=='new'){
			$newsletter_id = mysql_insert_id($db);
		}
		
		if($newsletter_id && $fields['content'] && $fields['template']){
			$this->write_newsletter_content($db,$newsletter_id);
		}
		
		return $newsletter_id;
	}
	
	public function delete_content($db=false,$newsletter_id,$newsletter_content_id){
		if(!$db)$db = db_connect();		
		$sql = "DELETE FROM "._DB_PREFIX."newsletter_content WHERE newsletter_id = '".(int)$newsletter_id."' AND newsletter_content_id = '".(int)$newsletter_content_id."'";
		$res = query($sql,$db);
	}
	
	public function save_content($db=false,$newsletter_id,$newsletter_content_id){
		if(!$db)$db = db_connect();
		if(!$_REQUEST['title'])return;
		if($newsletter_content_id == 'new'){
			$sql = "INSERT INTO "._DB_PREFIX."newsletter_content SET newsletter_id = '$newsletter_id', create_date = NOW() ";
			$where = '';
		}else{
			$sql = "UPDATE "._DB_PREFIX."newsletter_content SET create_date = NOW() ";
			$where = " WHERE newsletter_id = '$newsletter_id' AND newsletter_content_id = '".mysql_real_escape_string($newsletter_content_id)."' LIMIT 1";
		}
		$fields = array(
			//'newsletter_id' => $newsletter_id,
			'title' => $_REQUEST['title'],
			'position' => $_REQUEST['position'],
			'group_title' => $_REQUEST['group_title'],
			'content_summary' => $_REQUEST['content_summary'],
			'content_full' => $_REQUEST['content_full'],
		);
		foreach($fields as $key=>$val){
			$val = trim($val);
			if(!$val)continue;
			$sql .= ", `".$key."` = '".mysql_real_escape_string($val)."'";
		}
		$sql.=$where;
		$res = query($sql,$db);
		if($newsletter_content_id=='new'){
			$newsletter_content_id = mysql_insert_id($db);
		}
		$this->write_newsletter_content($db,$newsletter_id);
		return $newsletter_content_id;
	}
	public function write_newsletter_content($db,$newsletter_id){
		// todo - check if we've manually overwritten this file via ftp, dont overwrite ith ere.
		// maybe store a hash of the file or something?
		$newsletter_data = $this->get_newsletter($db, $newsletter_id);
		
		$replace = array(
			// email_body is also the generated content from newsletter_content
			"email_body" =>  $this->fix_image_paths($newsletter_data['content'],false,'',false),
			"unsubscribe_url" => 'http://'.$this->base_href.'/ext.php?t=unsub',
			"view_online"=>'http://'.$this->base_href.'/ext.php?t=view&id='.$newsletter_id.'',
			"SENDTOFRIEND"=>'http://'.$this->base_href.'/ext.php?t=send_to_friend&id='.$newsletter_id.'',
			"link_account"=>'http://'.$this->base_href.'/ext.php?t=update_form',
		);
		$newsletter_html_small = $this->get_template_html($newsletter_data['template'],array(),array('small'=>true,'full'=>false)); // pass options to template, so different full/small email template
		$newsletter_html_small = $this->fix_image_paths($newsletter_html_small,false,_TEMPLATE_DIR.$newsletter_data['template'],false);
		$newsletter_html_small = $this->get_template_html($newsletter_html_small,$replace);
		$newsletter_html_small = $this->fix_image_paths($newsletter_html_small,false,'',false);
		// write the version that gets emailed:
		$file = _NEWSLETTERS_DIR."newsletter-".$newsletter_id.'.html';
		if(!@file_put_contents($file,$newsletter_html_small)){
			echo "Unable to save the file: '$file' please check you have WRITE permissions on this folder.";
			exit;
		}
		// different inner content.
		$replace ['email_body'] = $this->fix_image_paths((isset($newsletter_data['content_full']) ? $newsletter_data['content_full']:$newsletter_data['content']),false,'',false);
		$newsletter_html_full = $this->get_template_html($newsletter_data['template'],array(),array('small'=>false,'full'=>true)); // pass options to template, so different full/small email template
		$newsletter_html_full = $this->fix_image_paths($newsletter_html_full,false,_TEMPLATE_DIR.$newsletter_data['template'],false);
		$newsletter_html_full = $this->get_template_html($newsletter_html_full,$replace);
		$newsletter_html_full = $this->fix_image_paths($newsletter_html_full,false,'',false);
		// write the version that gets dispalyed through read-more link.
		// todo: get newsletter_html from full article list when automated parts are done.
		$file = _NEWSLETTERS_DIR."newsletter-".$newsletter_id.'-full.html';
		if(!@file_put_contents($file,$newsletter_html_full)){
			echo "Unable to save the file: '$file' please check you have WRITE permissions on this folder.";
			exit;
		}
	}
	public function delete_newsletter($db,$newsletter_id){
		$sql = "DELETE FROM "._DB_PREFIX."newsletter WHERE newsletter_id = '".mysql_real_escape_string($newsletter_id)."'";
		query($sql,$db);
		// TODO - clean up data from all the other tables:
		// newsletter_member, send
	}
	public function get_newsletter($db=false,$newsletter_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM "._DB_PREFIX."newsletter WHERE newsletter_id = '".mysql_real_escape_string($newsletter_id)."'";
		$newsletter = array_shift(qa($sql,$db));
		$input_method = 'wizard';
		if($newsletter && $input_method == 'wizard'){
			// generate content with the wizard
			$wizard_file = _TEMPLATE_DIR.basename($newsletter['template'])."/wizard.php";
			if(is_file($wizard_file)){
				// pull out the inner content so we can process it in the wizard file.
				$contents = $this->get_newsletter_contents($db, $newsletter_id);
				$full = false; $small = true;
				ob_start();
				include($wizard_file);
				$newsletter['content'] = ob_get_clean();
				$full = true; $small = false;
				ob_start();
				include($wizard_file);
				$newsletter['content_full'] = ob_get_clean();
				unset($content);
			}
		}
		return $newsletter;
	}
	public function get_newsletters($db=false){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM "._DB_PREFIX."newsletter";
		return qa($sql,$db);
	}
	public function get_group($db,$group_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM `"._DB_PREFIX."group` WHERE `group_id` = '".mysql_real_escape_string($group_id)."'";
		return array_shift(qa($sql,$db));
	}
	public function get_groups($db){
		if(!$db)$db = db_connect();
		$sql = "SELECT *, group_id AS id FROM `"._DB_PREFIX."group` ORDER BY group_name";
		return qa($sql,$db);
	}
	public function save_group($db=false,$group_id='new',$group_name,$public=0){
		if(!$db)$db = db_connect();
		if($group_id == 'new'){
			$sql = "INSERT INTO `"._DB_PREFIX."group` SET group_name = '".mysql_real_escape_string($group_name)."', `public` = '".(int)$public."'";
		}else{
			$sql = "UPDATE `"._DB_PREFIX."group` SET group_name = '".mysql_real_escape_string($group_name)."', `public` = '".(int)$public."' WHERE group_id = '".mysql_real_escape_string($group_id)."'";
		}
		$res = query($sql,$db);
		if($group_id=='new'){
			$group_id = mysql_insert_id($db);
		}
		return $group_id;
	}
	
	public function delete_group($db,$group_id){
		if(_DEMO_MODE){
			echo "Delete disabled in demo sorry - should be able to edit it though.";
			exit;
		}
		$sql = "DELETE FROM `"._DB_PREFIX."group` WHERE group_id = '".mysql_real_escape_string($group_id)."' LIMIT 1";
		$res = query($sql,$db);
		$sql = "DELETE FROM `"._DB_PREFIX."member_group` WHERE group_id = '".mysql_real_escape_string($group_id)."'";
		$res = query($sql,$db);
	}
	
	
	public function get_image($db,$id){
		$sql = "SELECT * FROM `"._DB_PREFIX."image` WHERE image_id = '".mysql_real_escape_string($id)."'";
		return array_shift(qa($sql,$db));
	}
	public function get_link($db,$id){
		$sql = "SELECT * FROM `"._DB_PREFIX."link` WHERE link_id = '".mysql_real_escape_string($id)."'";
		$link = array_shift(qa($sql,$db));
		// find open rates
		$sql = "SELECT * FROM `"._DB_PREFIX."link_open` WHERE link_id = '".mysql_real_escape_string($id)."'";
		$link['open_rates'] = qa($sql,$db);
		return $link;
	}
	public function record_open($db,$send_id,$member_id){
		$sql = "UPDATE `"._DB_PREFIX."newsletter_member` SET open_time = '".time()."' WHERE send_id = '".mysql_real_escape_string($send_id)."' AND member_id = '".mysql_real_escape_string($member_id)."' LIMIT 1";
		$res = query($sql,$db);
		
	}
	
	public function record_link_click($db,$send_id,$member_id,$link_id){
		$this->record_open($db,$send_id,$member_id);
		$sql = "INSERT INTO `"._DB_PREFIX."link_open` SET `timestamp` = '".time()."', link_id = '".(int)$link_id."', member_id = '".mysql_real_escape_string($member_id)."', send_id ='".mysql_real_escape_string($send_id)."'";
		$res = query($sql,$db);
	}
	
	public function get_member($db,$member_id,$full=true){
		$member_id=(int)$member_id;
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM `"._DB_PREFIX."member` WHERE `member_id` = '".mysql_real_escape_string($member_id)."'";
		$member = array_shift(qa($sql,$db));
		
		if($full){
			$member['groups'] = array();
			$sql = "SELECT * FROM `"._DB_PREFIX."member_group` WHERE `member_id` = '".mysql_real_escape_string($member_id)."'";
			foreach(qa($sql,$db) as $group){
				$member['groups'][$group['group_id']] = $group['group_id'];
			}
			
			$member['campaigns'] = array();
			$sql = "SELECT * FROM `"._DB_PREFIX."campaign_member` LEFT JOIN `"._DB_PREFIX."campaign` USING (campaign_id) WHERE `member_id` = '".mysql_real_escape_string($member_id)."'";
			foreach(qa($sql,$db) as $campaign){
				$member['campaigns'][$campaign['campaign_id']] = $campaign;
			}
			$member['sync'] = array();
			$sql = "SELECT * FROM `"._DB_PREFIX."sync_member` LEFT JOIN `"._DB_PREFIX."sync` USING (sync_id) WHERE `member_id` = '".mysql_real_escape_string($member_id)."'";
			foreach(qa($sql,$db) as $sync){
				$member['sync'][$sync['sync_id']] = $sync;
			}
			
			$sql = "SELECT *,nm.send_id AS id FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."send` s USING (send_id) WHERE nm.`member_id` = '".mysql_real_escape_string($member_id)."'";
			$member['sent'] = qa($sql,$db);
			$sql = "SELECT *,nm.send_id AS id FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."send` s USING (send_id) WHERE nm.`member_id` = '".mysql_real_escape_string($member_id)."' AND open_time > 0";
			$member['opened'] = qa($sql,$db);
			$sql = "SELECT *,nm.send_id AS id FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."send` s USING (send_id) WHERE nm.`member_id` = '".mysql_real_escape_string($member_id)."' AND bounce_time > 0";
			$member['bounces'] = qa($sql,$db);
			$sql = "SELECT *,s.send_id AS id FROM `"._DB_PREFIX."member` m LEFT JOIN `"._DB_PREFIX."send` s ON m.unsubscribe_send_id = s.send_id WHERE m.`member_id` = '".mysql_real_escape_string($member_id)."' AND m.unsubscribe_send_id != 0";
			$member['unsubscribe'] = qa($sql,$db);
			
			// custom values
			$sql = "SELECT *,member_field_id AS id FROM "._DB_PREFIX."member_field_value WHERE member_id = '$member_id'";
			$member['custom'] = qa($sql,$db);
		}
		
		return $member;
	}
	public function get_members($db,$group_id=false,$newest_first=false,$limit=false,$search=array()){
		if(!$db)$db = db_connect();
		$sql = "SELECT m.member_id,m.email FROM `"._DB_PREFIX."member` m";
		if(!isset($search['group_id']) || !is_array($search['group_id'])){
			$search['group_id'] = array();
		}
		if($group_id){
			$search['group_id'][$group_id] = true;
		}
		// only join if needed:
		if(count($search['group_id'])){
			$sql .= " LEFT JOIN `"._DB_PREFIX."member_group` mg USING (member_id)";
		}
		$sql .= " WHERE 1 ";
		// easy upgrade, loop it up in the where department:
		foreach($search['group_id'] as $search_group_id => $tf){
			$sql .= " AND mg.group_id = '".(int)$search_group_id."' ";
		//echo "Group $search_group_id ". $sql;
		}
		if(isset($search['name']) && $search['name']){
			$sql .= " AND (m.first_name LIKE '%".mysql_real_escape_string($search['name'])."%' OR m.last_name LIKE '%".mysql_real_escape_string($search['name'])."%')";
		}
		if(isset($search['email']) && $search['email']){
			$sql .= " AND m.email LIKE '%".mysql_real_escape_string($search['email'])."%' ";
		}
		
		if(isset($search['start-letter']) && $search['start-letter']){
			if($search['start-letter'] == '#'){
				// pull all non-alpha starting emails
				$sql .= " AND m.email NOT RLIKE '^[a-zA-Z]' ";
			}else{
				$sql .= " AND m.email LIKE '".mysql_real_escape_string($search['start-letter'])."%' ";
			}
		}
		
		// find only non-unsubscribed members
		$sql .= " AND m.unsubscribe_date = '0000-00-00' ";
		// find only double opt in registered members
		$sql .= " AND m.join_date != '0000-00-00' ";
		$sql .= " GROUP BY m.member_id ";
		if($newest_first){
			$sql .= " ORDER BY m.join_date DESC";
		}else{
			$sql .= " ORDER BY m.email";
		}
		if($limit){
			$limit = (int)$limit;
			// find out what page we are on. 
			$page_number = (isset($_REQUEST['ps'])) ? (int)$_REQUEST['ps'] : 0;
			$limit_start = $page_number * $limit;
			$sql .= " LIMIT $limit_start, $limit";
		}
		return query($sql,$db);
		//return qa($sql,$db);
	}
	public function get_member_fields($db){
		if(!$this->custom_fields){
			$sql = "SELECT *,member_field_id AS id FROM "._DB_PREFIX."member_field";
			$this->custom_fields = qa($sql,$db);
		}
		return $this->custom_fields;
	}
	public function save_member($db=false,$member_id='new',$fields,$from_public_sub=false){
		if(!$db)$db = db_connect();
		if(!count($fields))return;
		if(!preg_match( "/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $fields['email'])){
			return false;
		}
		$groups = $fields['group_id'];
		$campaigns = $fields['campaign_id'];
		$custom = (isset($fields['custom']) && is_array($fields['custom'])) ? $fields['custom'] : array();
		unset($fields['group_id']);
		unset($fields['campaign_id']);
		unset($fields['custom']);
		if($fields['email']){
			$fields['email'] = strtolower(trim($fields['email']));
			// see if this member already exists
			$sql = "SELECT * FROM `"._DB_PREFIX."member` WHERE email = '".mysql_real_escape_string($fields['email'])."'";
			$existing = array_shift(qa($sql,$db));
			if($existing){
				$member_id = $existing['member_id'];
			}
		}
		if($member_id == 'new'){
			// do we do double opt in?
			$join_date = 'NOW()';
			if($from_public_sub){
				// means we can process double opt in
				if(isset($this->settings['double_opt_in']) && strtolower($this->settings['double_opt_in']) == 'yes'){
					$join_date = "'0000-00-00'";
				}
			}
			$sql = "INSERT INTO `"._DB_PREFIX."member` SET join_date = $join_date ";
			$where = '';
		}else{
			$sql = "UPDATE `"._DB_PREFIX."member` SET unsubscribe_date = '0000-00-00' ";
			$where = " WHERE member_id = '".mysql_real_escape_string($member_id)."' LIMIT 1";
		}
		foreach($fields as $key=>$val){
			$val = trim($val);
			if(!$val)continue;
			$sql .= ", `".$key."` = '".mysql_real_escape_string($val)."'";
		}
		$sql.=$where;
		$res = query($sql,$db);
		if($member_id=='new'){
			$member_id = mysql_insert_id($db);
		}
		if($member_id){
			$member_data = $this->get_member($db, $member_id);
		}
		if($member_id && is_array($groups)){
			$sql = "DELETE FROM `"._DB_PREFIX."member_group` WHERE member_id = '".mysql_real_escape_string($member_id)."'";
			$res = query($sql,$db);
			foreach($groups as $group_id){
				if(!(int)$group_id)continue;
				$sql = "INSERT INTO `"._DB_PREFIX."member_group` SET member_id = '".mysql_real_escape_string($member_id)."', group_id = '".mysql_real_escape_string($group_id)."'";
				$res = query($sql,$db);
			}
		}
		if($member_id){
			if(!is_array($campaigns))$campaigns = array();
			// add any new ones, and remove any removed ones
			$existing_campaigns = $member_data['campaigns'];
			foreach($campaigns as $campaign_id){
				$campaign_id = (int)$campaign_id; 
				// is this a new one?
				if(!isset($existing_campaigns[$campaign_id])){
					// add member to campaign.
					$this->campaign_add_member($db, $campaign_id, $member_id);
				}else{
					// already exists, make no changes.
					// remove it from the existing_campaigns array so we know what we have left to remove at the end.
					unset($existing_campaigns[$campaign_id]);
				}
			}
			foreach($existing_campaigns as $campaign_id_to_remove => $campaign_data){
				$this->campaign_remove_member($db, $campaign_id_to_remove, $member_id);
			}
			foreach($custom as $key=>$val){
				$this->save_member_custom($db,$member_id,$key,$val);
			}
		}
		return $member_id;
	}
	public function save_member_custom($db,$member_id,$key,$val,$admin=false){
		$member_id = (int)$member_id;
		$existing_custom_fields = $this->get_member_fields($db);
		$member_field_id = false;
		// could be bad if someone wants a custom field as  "1" or something weird like that.. meh screw em.
		if(is_numeric($key) && isset($existing_custom_fields[$key])){
			$member_field_id = $key;
		}
		// find a matching name - if in admin mode we add a new name if none found
		// prevents people adding their own custom fields from public signup pages.
		foreach($existing_custom_fields as $field){
			if($field['field_name'] == $key){
				$member_field_id = $field['member_field_id'];
			}
		}
		if(!$member_field_id && $admin){
			$sql = "INSERT INTO "._DB_PREFIX."member_field SET field_name = '".mysql_real_escape_string($key)."', field_type = 'text'";
			$res = query($sql,$db);
			$member_field_id = mysql_insert_id($db);
		}
		if($member_field_id){
			// save the member field value in the db.
			$sql = "REPLACE INTO "._DB_PREFIX."member_field_value SET member_id = '$member_id', member_field_id = '$member_field_id', value = '".mysql_real_escape_string($val)."'";
			$res = query($sql,$db);
		}
	}
	public function delete_member($db,$member_id){
		$sql = "DELETE FROM `"._DB_PREFIX."member` WHERE member_id = '".mysql_real_escape_string($member_id)."' LIMIT 1";
		$res = query($sql,$db);
		$sql = "DELETE FROM `"._DB_PREFIX."member_group` WHERE member_id = '".mysql_real_escape_string($member_id)."'";
		$res = query($sql,$db);
	}
	public function unsubscribe($db,$member_id,$send_id=false){
		$sql = "UPDATE `"._DB_PREFIX."member` SET unsubscribe_date = NOW()";
		if($send_id){
			$sql .= ", unsubscribe_send_id = '$send_id' ";
		}
		$sql .= " WHERE member_id = '".mysql_real_escape_string($member_id)."' LIMIT 1";
		$res = query($sql,$db);
		$sql = "DELETE FROM `"._DB_PREFIX."member_group` WHERE member_id = '".mysql_real_escape_string($member_id)."'";
		$res = query($sql,$db);
	}
	
	public function get_settings($db){
		$sql = "SELECT * FROM `"._DB_PREFIX."settings` ORDER BY `key`";
		$this->settings = array();
		foreach(qa($sql,$db) as $setting){
			$this->settings[$setting['key']] = $setting['val'];
		}
		$this->{'uh'.'ash'}  = '?b='.base64_encode(_NEWSLETTER_VERSION.'|'.$_SERVER['REMOTE_ADDR'].'|'. $_SERVER['HTTP_HOST'].'|'.$_SERVER['REQUEST_URI']);
		return $this->settings;
	}
	public function save_settings($db,$settings){
		if(!is_array($settings))$settings = array();
		if($settings){
//			$sql = "DELETE FROM `"._DB_PREFIX."settings`";
//			$res = query($sql,$db);
		}
		foreach($settings as $key=>$item){
			$newkey = trim($item['key']);
			$newval = trim($item['val']);
			if(!$newkey)continue;
//			$sql = mysql_query("UPDATE `"._DB_PREFIX."settings` SET `key` = '".mysql_real_escape_string($newkey)."', `val` = '".mysql_real_escape_string($newval)."'");
//			$sql = "REPLACE INTO `"._DB_PREFIX."settings` SET `key` = '".mysql_real_escape_string($newkey)."', `val` = '".mysql_real_escape_string($newval)."'";
//			$res = query($sql,$db);
		}
		// reload settings.
//		return $this->get_settings($db);
	}
	
	
	
	public function create_send($db,$newsletter_id,$send_groups,$dont_sent_duplicates,$send_later_date=false,$campaign_id=0){
		
		// before creating a send, we run all the imports so we have the latest member information
		$this->run_syncs($db);
		
		// first work out if there are members to send to.
		$send_members = array();
		if($campaign_id){
			// we treat the send_groups array as a member_id to send to.
			$send_members[] = $send_groups;
		}else{
			foreach($send_groups as $group_id){
				if($group_id=='ALL'){
					$members = $this->get_members($db);
				}else{
					$members = $this->get_members($db,$group_id);
				}
				//foreach($members as $member){
				while($member = mysql_fetch_assoc($members)){
					if($dont_sent_duplicates){
						// check if this member id has received thsi newsletter before.
						$sql = "SELECT * FROM "._DB_PREFIX."newsletter_member nm LEFT JOIN `"._DB_PREFIX."send` s USING (send_id) WHERE nm.member_id = '".mysql_real_escape_string($member['member_id'])."' AND s.newsletter_id = '".mysql_real_escape_string($newsletter_id)."'";
						if(count(qa($sql,$db))){
							continue;
						}
					}
					$send_members[] = $member['member_id'];
				}
			}
		}
		//print_r($send_groups);
		//print_r($send_members);exit;
		
		if($send_members){
			$sql = "INSERT INTO `"._DB_PREFIX."send` SET newsletter_id = '".mysql_real_escape_string($newsletter_id)."', campaign_id = '".mysql_real_escape_string($campaign_id)."', `status` = 1";
			// work out if we're sending later:
			if($send_later_date){
				//$fields['start_time'] = strtotime($fields['send_later']);
				//$sql = "UPDATE `"._DB_PREFIX."send` SET start_time = '".strtotime($send_later_date)."' WHERE send_id = '$send_id' LIMIT 1";
				//$res = query($sql,$db);
				$sql .= ", start_time = '".strtotime($send_later_date)."'";
			}else{
				$sql .= ", start_time = '".time()."'";
			}
			$res = query($sql,$db);
			$send_id = mysql_insert_id($db);
			if($send_id){
				foreach($send_members as $member_id){
					$sql = "REPLACE INTO `"._DB_PREFIX."newsletter_member` SET send_id = '$send_id', member_id = '$member_id', status = 1";
					$res = query($sql,$db);
				}
				$newsletter_data = $this->get_newsletter($db,$newsletter_id);
				$template = $newsletter_data['template'];
				// fix image paths with no newsletter id, so we dont put a tracking code in these ones.
				if (is_file(_NEWSLETTERS_DIR."newsletter-".$newsletter_id.".html")){
					$newsletter_html = file_get_contents(_NEWSLETTERS_DIR."newsletter-".$newsletter_id.".html");
					$newsletter_html = preg_replace('#([\'"])\.\./#','$1',$newsletter_html);
					$newsletter_html = $this->fix_image_paths($newsletter_html,$send_id,'');
					$sql = "UPDATE `"._DB_PREFIX."send` SET template_html = '".mysql_real_escape_string($newsletter_html)."' WHERE send_id = '$send_id' LIMIT 1";
					$res = query($sql,$db);
					unset($newsletter_html);
				}
				// and the full version:
				if(is_file(_NEWSLETTERS_DIR."newsletter-".$newsletter_id."-full.html")){
					$newsletter_html = file_get_contents(_NEWSLETTERS_DIR."newsletter-".$newsletter_id."-full.html");
					$newsletter_html = preg_replace('#([\'"])\.\./#','$1',$newsletter_html);
					$newsletter_html = $this->fix_image_paths($newsletter_html,$send_id,'');
					$sql = "UPDATE `"._DB_PREFIX."send` SET full_html = '".mysql_real_escape_string($newsletter_html)."' WHERE send_id = '$send_id' LIMIT 1";
					$res = query($sql,$db);
					unset($newsletter_html);
				}
			}
			return $send_id;
		}
		return false;
		
	}
	
	public function get_send($db,$send_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM `"._DB_PREFIX."send` WHERE `send_id` = '".mysql_real_escape_string($send_id)."'";
		$send = array_shift(qa($sql,$db));
		$sql = "SELECT * FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."member` m USING (member_id) WHERE nm.send_id = '".mysql_real_escape_string($send_id)."' AND nm.status = 1 AND m.unsubscribe_date = '0000-00-00'";
		$send['unsent_members'] = qa($sql,$db);
		$sql = "SELECT * FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."member` m USING (member_id) WHERE nm.send_id = '".mysql_real_escape_string($send_id)."' AND nm.status != 1";
		$send['sent_members'] = qa($sql,$db);
		$sql = "SELECT * FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."member` m USING (member_id) WHERE nm.send_id = '".mysql_real_escape_string($send_id)."' AND m.unsubscribe_date != '0000-00-00' AND m.unsubscribe_send_id = '".mysql_real_escape_string($send_id)."'";
		$send['unsub_members'] = qa($sql,$db);
		$sql = "SELECT * FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."member` m USING (member_id) WHERE nm.send_id = '".mysql_real_escape_string($send_id)."' AND nm.open_time > 0";
		$send['opened_members'] = qa($sql,$db);
		$sql = "SELECT * FROM `"._DB_PREFIX."newsletter_member` nm LEFT JOIN `"._DB_PREFIX."member` m USING (member_id) WHERE nm.send_id = '".mysql_real_escape_string($send_id)."' AND nm.bounce_time > 0";
		$send['bounce_members'] = qa($sql,$db);
		return $send;
	}
	
	public function pause_send($db,$send_id){
		if(!$db)$db = db_connect();
		$sql = "UPDATE `"._DB_PREFIX."send` SET status = 6 WHERE `send_id` = '".mysql_real_escape_string($send_id)."'";
		return query($sql,$db);
	}
	public function un_pause_send($db,$send_id){
		if(!$db)$db = db_connect();
		$sql = "UPDATE `"._DB_PREFIX."send` SET status = 1 WHERE `send_id` = '".mysql_real_escape_string($send_id)."'";
		return query($sql,$db);
	}
	public function send_complete($db,$send_id){
		if(!$db)$db = db_connect();
		$sql = "UPDATE `"._DB_PREFIX."send` SET status = 3, finish_time = '".time()."' WHERE `send_id` = '".mysql_real_escape_string($send_id)."'";
		return query($sql,$db);
	}
	
	public function get_newsletter_sends($db,$newsletter_id){
		$sql = "SELECT * FROM `"._DB_PREFIX."send` WHERE newsletter_id = '".mysql_real_escape_string($newsletter_id)."' AND start_time <= '".time()."'";
		return qa($sql,$db);
	}
	public function get_past_sends($db){
		$sql = "SELECT * FROM `"._DB_PREFIX."send` WHERE `status` = 3 ORDER BY finish_time DESC";
		return qa($sql,$db);
	}
	
	public function get_pending_sends($db,$newsletter_id=false){
		$newsletter_id = (int)$newsletter_id;
		// find any newsletters that have started a send, but not yet finished.
		// these could be ones scheduled in the future.
		$sql = "SELECT * FROM `"._DB_PREFIX."send` s LEFT JOIN `"._DB_PREFIX."newsletter` n USING (newsletter_id) WHERE finish_time = 0";
		if($newsletter_id) $sql .= " AND n.newsletter_id = '$newsletter_id'";
		$sends = qa($sql,$db);
		foreach($sends as &$send){
			// work out the progress of this send.
			$send['progress'] = '';
			// work out pending count
			$sql = "SELECT count(member_id) AS item_count FROM `"._DB_PREFIX."newsletter_member` WHERE send_id = '".$send['send_id']."' AND sent_time = 0";
			$unsent = array_shift(qa($sql,$db));
			$unsent = $unsent['item_count'];
			// work out total count
			$sql = "SELECT count(member_id) AS item_count FROM `"._DB_PREFIX."newsletter_member` WHERE send_id = '".$send['send_id']."'";
			$total = array_shift(qa($sql,$db));
			$total = $total['item_count'];
			$send['progress'] = "$unsent of $total left to send";
			$send['start_date'] = date("Y-m-d",$send['start_time']);
		}
		return $sends;
	}
	
	public function send_out_newsletter($db,$send_id,$member_id,$newsletter_id=false,$force=false){
		
		
		// status is false if we have gone over limit.
		$status = $this->is_email_limit_ok($db);
		
		if($status){
			$send_id = (int)$send_id;
			$member_id = (int)$member_id;
		
			$send_data = $this->get_send($db, $send_id);
			if(!$force && $send_data['status']!='1')return false;
			$newsletter_id = $send_data['newsletter_id'];
		
			$newsletter_data = $this->get_newsletter($db,$newsletter_id);
			$member_data = $this->get_member($db,$member_id,true);
			
			
			$newsletter_html = $send_data['template_html'];
			
			
			$replace = array(
				"email_subject" => $newsletter_data['subject'],
				"from_name" => $newsletter_data['from_name'],
				"from_email" => $newsletter_data['from_email'],
				"to_email" => $member_data['email'],
				"sent_date" => date("jS M, Y"),
				"sent_month" => date("M Y"),
				/*"unsubscribe_url" => 'http://'.$this->base_href.'/ext.php?t=unsub&sid='.$send_id.'&mid='.$member_data['member_id'].'&hash='.md5("Unsub ".$member_data['member_id']."from $send_id").'',
				"view_online"=>'http://'.$this->base_href.'/ext.php?t=view&id='.$send_data['newsletter_id'].'&sid='.$send_id.'&mid='.$member_data['member_id'].'&hash='.md5("view link ".$member_data['member_id']."from $send_id").'',
				"link_account" => $this->settings['url_update'],*/
				"member_id" => $member_data['member_id'],
				"send_id" => $send_id,
				"MEMBER_HASH" => md5("Member Hash for $send_id with member_id $member_id"),
				"first_name"=>$member_data['first_name'],
				"last_name"=>$member_data['last_name'],
				"email"=>$member_data['email'],
				
			);
			
			foreach($replace as $key=>$val){
				$newsletter_html = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$newsletter_html);
			}
			
			$options=array(
				"bounce_email"=>$newsletter_data['bounce_email'],
				"message_id" => "Newsletter-$send_id-$member_id-".md5("bounce check for $member_id in send $send_id"),
			);
			
			$send_email_status = $this->send_email($replace['to_email'],$replace['email_subject'],$newsletter_html,$replace['from_email'],$replace['from_name'],$options);
			
			if($send_email_status){
				// all worked correctly.
				$sql = "UPDATE "._DB_PREFIX."newsletter_member SET `status` = 3, sent_time = '".time()."' WHERE `member_id` = '".mysql_real_escape_string($member_data['member_id'])."' AND send_id = '".$send_id."' LIMIT 1";
				$res = query($sql,$db);
			}else{
				// something failed. mark is as bounced as well. or do we? hm
				$sql = "UPDATE "._DB_PREFIX."newsletter_member SET `status` = 4, sent_time = '".time()."', bounce_time = '".time()."' WHERE `member_id` = '".mysql_real_escape_string($member_data['member_id'])."' AND send_id = '".$send_id."' LIMIT 1";
				$res = query($sql,$db);
			}
			
		}
		
		return array(
			'send_status' => $send_email_status,
			'status' => $status,
			'message' => (!$status) ? 'Email limit exceeded - please try again later (or if you have setup cron, we will try automatically for you).' : '',
		);
	}
	
	public function is_email_limit_ok($db){
		// find the settigns, they can be:
		// limit_day
		// limit_month
		// limit_hour
		$limit_ok = true;
		foreach($this->settings as $key=>$val){
			$start_time = false;
			$send_limit = false;
			switch($key){
				case 'limit_day':
					// how many in past 24 hours
					$start_time = strtotime("-24 hours");
					$send_limit = (int)$val;
					break;
				case 'limit_month':
					$start_time = strtotime("-1 month");
					$send_limit = (int)$val;
					break;
				case 'limit_hour':
					$start_time = strtotime("-1 hour");
					$send_limit = (int)$val;
					break;
			}
			if($start_time && $send_limit){
				// found a limit, see if it's broken
				$sql = "SELECT COUNT(send_id) AS send_count FROM `"._DB_PREFIX."newsletter_member` WHERE sent_time > '$start_time'";
				$res = array_shift(qa($sql,$db));
				if($res && $res['send_count']){
					// newsletters have been sent out - is it over the limit?
					if($res['send_count'] >= $send_limit){
						$limit_ok = false;
					}
				}
			}
		}
		
		return $limit_ok;
	}
	
	public function version_url(){
		$url = "http://tf.dtbaker.com.au/newsletter/version.php";
		$url .= $this->uhash;
		return $url;
	}
	
	public function campaign_add_member($db, $campaign_id, $member_id){
		$campaign_id = (int)$campaign_id;
		$member_id = (int)$member_id;
		$campaign_data = $this->get_campaign($db, $campaign_id);
		if($campaign_data){
			$sql = "INSERT INTO "._DB_PREFIX."campaign_member SET campaign_id = '$campaign_id', member_id = '$member_id', join_time = '".time()."'";
			$res = query($sql,$db);
		}
	}
	public function campaign_add_newsletter($db,$campaign_id,$newsletter_id,$send_days){
		$send_days = abs((int)$send_days);
		$send_time = 86400 * $send_days;
		if(!$send_time){
			$send_time = 86400;
		}
		$newsletter_id = (int)$newsletter_id;
		$campaign_id = (int)$campaign_id;
		$campaign_data = $this->get_campaign($db, $campaign_id);
		if($campaign_data){
			$sql = "INSERT INTO "._DB_PREFIX."campaign_newsletter SET newsletter_id = '$newsletter_id', campaign_id = '$campaign_id', send_time = '$send_time'";
			$res = query($sql,$db);
		}
	
	}
	public function campaign_remove_member($db, $campaign_id, $member_id){
		$campaign_id = (int)$campaign_id;
		$member_id = (int)$member_id;
		$sql = "DELETE FROM "._DB_PREFIX."campaign_member WHERE campaign_id = '$campaign_id' AND member_id = '$member_id'";
		$res = query($sql,$db);
	}
	
	public function delete_campaign_newsletter($db, $campaign_id, $newsletter_id){
		$campaign_id = (int)$campaign_id;
		$newsletter_id = (int)$newsletter_id;
		$sql = "DELETE FROM "._DB_PREFIX."campaign_newsletter WHERE campaign_id = '$campaign_id' AND newsletter_id = '$newsletter_id'";
		$res = query($sql,$db);
	}
	
	public function get_campaign($db=false,$campaign_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM "._DB_PREFIX."campaign WHERE campaign_id = '".mysql_real_escape_string($campaign_id)."'";
		$campaign = array_shift(qa($sql,$db));
		$sql = "SELECT * FROM "._DB_PREFIX."campaign_member cm LEFT JOIN "._DB_PREFIX."member m USING (member_id) WHERE campaign_id = '".mysql_real_escape_string($campaign_id)."'";
		$campaign['members_rs'] = query($sql,$db);
		$sql = "SELECT * FROM "._DB_PREFIX."campaign_newsletter cm LEFT JOIN "._DB_PREFIX."newsletter n USING (newsletter_id) WHERE campaign_id = '".mysql_real_escape_string($campaign_id)."' ORDER BY send_time ASC";
		$campaign['newsletter_rs'] = query($sql,$db);
		return $campaign;
	}
	public function get_campaigns($db=false){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM "._DB_PREFIX."campaign";
		return qa($sql,$db);
	}
	public function delete_campaign($db,$campaign_id){
		$sql = "DELETE FROM "._DB_PREFIX."campaign WHERE campaign_id = '$campaign_id'";
		query($sql,$db);
		$sql = "DELETE FROM "._DB_PREFIX."campaign_newsletter WHERE campaign_id = '$campaign_id'";
		query($sql,$db);
		$sql = "DELETE FROM "._DB_PREFIX."campaign_member WHERE campaign_id = '$campaign_id'";
		query($sql,$db);
	}
	public function save_campaign($db,$fields,$campaign_id){
		if(!$db)$db = db_connect();
		if(!count($fields))return;
		if($campaign_id == 'new'){
			$sql = "INSERT INTO "._DB_PREFIX."campaign SET create_date = NOW() ";
			$where = '';
		}else{
			$sql = "UPDATE "._DB_PREFIX."campaign SET create_date = NOW() ";
			$where = " WHERE campaign_id = '".mysql_real_escape_string($campaign_id)."' LIMIT 1";
		}
		
		foreach($fields as $key=>$val){
			$val = trim($val);
			if(!$val)continue;
			if($key!='content'){
				//$val = htmlspecialchars($val);
			}
			$sql .= ", `".$key."` = '".mysql_real_escape_string($val)."'";
		}
		$sql.=$where;
		$res = query($sql,$db);
		if($campaign_id=='new'){
			$campaign_id = mysql_insert_id($db);
		}
		
		return $campaign_id;
	}
	
	
	
	public function get_sync($db=false,$sync_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM `"._DB_PREFIX."sync` WHERE sync_id = '".mysql_real_escape_string($sync_id)."'";
		$sync = array_shift(qa($sql,$db));
		$sql = "SELECT COUNT(sync_id) AS c FROM "._DB_PREFIX."sync_member WHERE sync_id = '".mysql_real_escape_string($sync_id)."'";
		$res = array_shift(qa($sql,$db));
		$sync['member_count'] = $res['c'];
		$sql = "SELECT group_id AS id FROM "._DB_PREFIX."sync_group WHERE sync_id = '".mysql_real_escape_string($sync_id)."'";
		$sync['groups'] = qa($sql,$db);
		return $sync;
	}
	public function get_syncs($db=false){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM "._DB_PREFIX."sync";
		return qa($sql,$db);
	}
	public function delete_sync($db,$sync_id){
		$sql = "DELETE FROM "._DB_PREFIX."sync WHERE sync_id = '$sync_id'";
		query($sql,$db);
		$sql = "DELETE FROM "._DB_PREFIX."sync_newsletter WHERE sync_id = '$sync_id'";
		query($sql,$db);
		$sql = "DELETE FROM "._DB_PREFIX."sync_member WHERE sync_id = '$sync_id'";
		query($sql,$db);
	}
	public function save_sync($db,$fields,$sync_id){
		if(!$db)$db = db_connect();
		if(!count($fields))return;
		$groups = $fields['groups'];
		unset($fields['groups']);
		if($sync_id == 'new'){
			$sql = "INSERT INTO "._DB_PREFIX."sync SET create_date = NOW() ";
			$where = '';
		}else{
			$sql = "UPDATE "._DB_PREFIX."sync SET create_date = NOW() ";
			$where = " WHERE sync_id = '".mysql_real_escape_string($sync_id)."' LIMIT 1";
		}
		
		foreach($fields as $key=>$val){
			$val = trim($val);
			if(!$val)continue;
			$sql .= ", `".$key."` = '".mysql_real_escape_string($val)."'";
		}
		$sql.=$where;
		$res = query($sql,$db);
		if($sync_id=='new'){
			$sync_id = mysql_insert_id($db);
		}
		if($sync_id && is_array($groups)){
			$sql = "DELETE FROM `"._DB_PREFIX."sync_group` WHERE sync_id = '".mysql_real_escape_string($sync_id)."'";
			$res = query($sql,$db);
			foreach($groups as $group_id){
				if(!(int)$group_id)continue;
				$sql = "INSERT INTO `"._DB_PREFIX."sync_group` SET sync_id = '".mysql_real_escape_string($sync_id)."', group_id = '".mysql_real_escape_string($group_id)."'";
				$res = query($sql,$db);
			}
		}
		return $sync_id;
	}
	public function test_sync($db,$sync_id){
		$sync = $this->get_sync($db, $sync_id);
		$error = false;
		$db_host2 = $sync['db_host'];
		$db_user2 = $sync['db_username'];
		$db_pass2 = $sync['db_password'];
		$syncdbcnx = @mysql_connect($db_host2,$db_user2,$db_pass2); // or die("Failed on '$db_host2' '$db_user2' '$db_pass2'".mysql_error());
		if(!$syncdbcnx){
			echo 'Sync failed to connect to database. Please check username/password/host.' . mysql_error($syncdbcnx);
			exit;
		}
		$syncdb = @mysql_select_db($sync['db_name']);
		if(!$syncdb){
			ob_end_clean();
			echo 'Sync failed to select the database \''.$sync['db_name'].'\'. ' . mysql_error();
			exit;
		}
		$sql = "SELECT `".mysql_real_escape_string($sync['db_table_key'])."`, `".mysql_real_escape_string($sync['db_table_email_key'])."` ";
		if($sync['db_table_fname_key']) $sql .= ", `".mysql_real_escape_string($sync['db_table_fname_key'])."`";
		if($sync['db_table_lname_key']) $sql .= ", `".mysql_real_escape_string($sync['db_table_lname_key'])."`";
		$sql .= " FROM `".mysql_real_escape_string($sync['db_table'])."` LIMIT 1";
		$res = @mysql_query($sql,$syncdbcnx);
		if(!$res){
			ob_end_clean();
			echo 'Failed to select members. Please ensure your table name, primary key and email key are correct. '.mysql_errno();
			exit;
		}
		return $syncdbcnx;
	}
	public function run_syncs($db){
		$syncs = $this->get_syncs($db);
		foreach($syncs as $sync){
			$this->run_sync($db, $sync['sync_id']);
		}
	}
	public function run_sync($db,$sync_id){
		$sync = $this->get_sync($db, $sync_id);
		$syncdbcnx = $this->test_sync($db,$sync_id);
		
		if($syncdbcnx){
			// do a selection on the table, load unique members into our system, linking them with this sync id.
			$sql = "SELECT `".mysql_real_escape_string($sync['db_table_key'])."`, `".mysql_real_escape_string($sync['db_table_email_key'])."` ";
			if($sync['db_table_fname_key']) $sql .= ", `".mysql_real_escape_string($sync['db_table_fname_key'])."`";
			if($sync['db_table_lname_key']) $sql .= ", `".mysql_real_escape_string($sync['db_table_lname_key'])."`";
			$sql .= " FROM `".mysql_real_escape_string($sync['db_table'])."`";
			
			$res = mysql_query($sql,$syncdbcnx);
			$unique_hash = array();
			while($row = mysql_fetch_assoc($res)){
				$sync_unique_id = (int)$row[$sync['db_table_key']];
				if($sync_unique_id){
					if(isset($unique_hash[$sync_unique_id])){
						echo "Please select a correct unique table key (ie: primary key).";
						echo " The primary key `".$sync['db_table_key']."` has duplicate entry: '$sync_unique_id'";
						exit;
					}
					$email_address = strtolower(trim($row[$sync['db_table_email_key']]));
					//echo " Email address $email_address <br> ";
					if($email_address){
						$member_id = false;
						$unique_hash[$sync_unique_id]=true;
						// check if this sync member already exists.
						$sql = "SELECT * FROM `"._DB_PREFIX."sync_member` WHERE sync_id = '$sync_id' AND sync_unique_id = '$sync_unique_id'";
						$check = query($sql,$db);
						if(mysql_num_rows($check)){
							// already exists.
							$member = mysql_fetch_assoc($check);
							$member_id = $member['member_id'];
							// update this member's details
							
							// hmm, what if a member changes their email address to an existing address in this system.
							// do we join stats somehow? similar issue if someone udpates their email address to an existing one
							// in this sytem... need some way to link accounts. or just dont worry about it and only check for dups on send.
						}
						// check if this member email address already exists in the system.
						if(!$member_id){
							$sql = "SELECT * FROM `"._DB_PREFIX."member` WHERE `email` = '".mysql_real_escape_string($email_address)."'";
							$check = query($sql,$db);
							if(mysql_num_rows($check)){
								// found existing member by email address.
								$member = mysql_fetch_assoc($check);
								$member_id = $member['member_id'];
							}
						}
						if(!$member_id){
							// create a new member
							$sql = "INSERT INTO `"._DB_PREFIX."member` SET `join_date` = NOW(), `email` = 'sync'";
							$create = query($sql,$db);
							$member_id = mysql_insert_id($db);
						}
						if($member_id){
							// save member details based on the import table.
							//`email` = '".mysql_real_escape_string($email_address)."',
							$sql = "UPDATE `"._DB_PREFIX."member` SET `email` = '".mysql_real_escape_string($email_address)."' ";
							if($sync['db_table_fname_key']) $sql .= ", first_name = '".mysql_real_escape_string($row[$sync['db_table_fname_key']])."'";
							if($sync['db_table_lname_key']) $sql .= ", last_name = '".mysql_real_escape_string($row[$sync['db_table_lname_key']])."'";
							$sql .= " WHERE member_id = '$member_id'";
							query($sql,$db);
							// add any selected groups to this member.
							foreach($sync['groups'] as $group_id => $group){
								$sql = "REPLACE INTO `"._DB_PREFIX."member_group` SET member_id = '$member_id', group_id = '$group_id'";
								query($sql,$db);
							}
							$sql = "REPLACE INTO `"._DB_PREFIX."sync_member` SET member_id = '$member_id', sync_id = '$sync_id', sync_unique_id = '$sync_unique_id'";
							query($sql,$db);
						}
					}
				}
				
			}
			
			$sql = "UPDATE `"._DB_PREFIX."sync` SET last_sync = '".time()."' WHERE sync_id = '$sync_id'";
			query($sql,$db);
							
			mysql_close($syncdbcnx);
		}
		return true;
	}
	
	/*** FORM ***/
	
	public function get_form($db,$include_form_tag = false,$member_id=false,$admin=false){
		$member_data = array();
		if($member_id){
			$member_data = $this->get_member($db,$member_id);
		}
		$campaigns = $this->get_campaigns($db);
		$member_fields = $this->get_member_fields($db);
		$groups = $this->get_groups($db);
		ob_start();
		?>
		
		<?php if($include_form_tag){ ?>
		<div id="subscribe_form">
		<form action="http://<?php echo $this->base_href;?>/ext.php?t=signup" method="post">
		<?php } ?>
		

			<div class="form_elements">
				<label>First Name</label>
				<input type="text" class="text_input" name="first_name" value="<?php echo htmlspecialchars($member_data['first_name']);?>">
			</div>

			<div class="form_elements">
				<label>Last Name</label>
				<input type="text" class="text_input" name="last_name" value="<?php echo htmlspecialchars($member_data['last_name']);?>">
			</div>

			<div class="form_elements">
				<label>Email</label>
				<input type="text" class="text_input" name="email" value="<?php echo htmlspecialchars($member_data['email']);?>">
			</div>

			<?php
			foreach($member_fields as $member_field){
				?>
				<div class="form_elements">
						<label>
							<?php echo $member_field['field_name'];?>
							<?php if($member_field['required']){ ?>
						</label>
						<?php } ?>
						<input type="text" class="text_input" name="mem_custom_val[<?php echo $member_field['field_name'];?>]" value="<?php echo $member_data['custom'][$member_field['member_field_id']]['value'];?>">
				</div>
				<?php
			}
			if($admin){
			?>
				<div class="form_elements">
					<input type="text" name="mem_custom_new_key" value="">
				</div>
				<div class="form_elements">
					<input type="text" name="mem_custom_new_val" value="">
				</div>

			<?php } ?>
				<div class="form_elements">
					<label>Subscribe Under</label>
					<?php foreach($groups as $group){ 
					if(!$group['public'])continue;
					?>
					<input type="checkbox" name="group_id[]" value="<?php echo $group['group_id'];?>" <?php if(!$member_id || $member_data['groups'][$group['group_id']]) echo 'checked';?>>
					<?php echo $group['group_name'];?>
					<br />
						<?php } ?>
						<?php 
						if($campaigns){
						foreach($campaigns as $campaign){ 
							if(!$campaign['public'])continue;
							?>
						<input type="checkbox" name="campaign_id[]" value="<?php echo $campaign['campaign_id'];?>" <?php if(!$member_id || $member_data['campaigns'][$campaign['campaign_id']]) echo ' checked';?>>
						<?php echo $campaign['campaign_name'];?>
						<br />
						</div>
						<?php }
						} ?>
				<div class="form_elements">
				<input type="submit" name="submit" value="Subscribe"> <br/>
				</div>
		<?php if($include_form_tag){ ?>
		</form>
		</div>
		<?php } ?>
		
		<?php 
		return ob_get_clean();
	}
	
	public function check_bounces($db){
		
		$email_address = $this->settings['bounce_email']; // not used
		$email_username = $this->settings['bounce_username'];
		$email_password = $this->settings['bounce_password'];
		$email_host = $this->settings['bounce_host'];
		$email_port = ($this->settings['bounce_port']) ? $this->settings['bounce_port'] : 110; // pop3
		
		$mbox = imap_open ('{'.$email_host.':'.$email_port.'/pop3/novalidate-cert}INBOX', $email_username, $email_password) or die(imap_last_error());
		if(!$mbox){
			// send email letting them know bounce checking failed?
			// meh. later.
			echo 'Failed to connect';
		}else{
			$MC = imap_check($mbox);
			$result = imap_fetch_overview($mbox,"1:{$MC->Nmsgs}",0);
			foreach ($result as $overview) {
				$this_subject = (string)$overview->subject;
    			//echo "#{$overview->msgno} ({$overview->date}) - From: {$overview->from} <br> {$this_subject} <br>\n";
			    $tmp_file = tempnam('/tmp/','newsletter_bounce');
			    // TODO - tmp files for windows hosting.
			    imap_savebody  ($mbox, $tmp_file, $overview->msgno);
			    $body = file_get_contents($tmp_file);
				if(preg_match('/Message-ID:\s*<?Newsletter-(\d+)-(\d+)-([A-Fa-f0-9]{32})/imsU',$body,$matches)){
					// we have a newsletter message id, check the hash and mark a bounce.
					//"message_id" => "Newsletter-$send_id-$member_id-".md5("bounce check for $member_id in send $send_id"),
					$send_id = (int)$matches[1];
					$member_id = (int)$matches[2];
					$provided_hash = trim($matches[3]);
					$real_hash = md5("bounce check for $member_id in send $send_id");
					if($provided_hash == $real_hash){
						$sql = "UPDATE "._DB_PREFIX."newsletter_member SET `status` = 4, bounce_time = '".time()."' WHERE `member_id` = '".$member_id."' AND send_id = '".$send_id."' AND `status` = 3 LIMIT 1";
						$res = query($sql,$db);
						imap_delete($mbox, $overview->msgno);
					}else{
						// bad hash, report.
					}
				}
				unlink($tmp_file);
			}
			imap_expunge($mbox);
			imap_close($mbox);
		}

	}
	
	
	public function get_newsletter_contents($db=false,$newsletter_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT newsletter_content_id FROM "._DB_PREFIX."newsletter_content WHERE newsletter_id = '".(int)$newsletter_id."' ORDER BY `position`";
		$content = array();
		foreach(qa($sql,$db) as $c){
			$content[$c['newsletter_content_id']] = $this->get_newsletter_content($db,$c['newsletter_content_id']);
		}
		return $content;
	}
	public function get_newsletter_content($db=false,$newsletter_content_id){
		if(!$db)$db = db_connect();
		$sql = "SELECT * FROM "._DB_PREFIX."newsletter_content WHERE newsletter_content_id = '".(int)$newsletter_content_id."'";
		$res = array_shift(qa($sql,$db));
		$folder = _IMAGES_DIR.'newsletter-'.$res['newsletter_id'].'/';
		if(is_file($folder.$newsletter_content_id.'-thumb.jpg')){
			$res['image_thumb'] = $folder.$newsletter_content_id.'-thumb.jpg';
		}
		if(is_file($folder.$newsletter_content_id.'.jpg')){
			$res['image_main'] = $folder.$newsletter_content_id.'.jpg';
		}
		return $res;
	}
}
