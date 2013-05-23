<?php
/**
 * Pro Newsletter System
 * Author: Aman Virk
 * Version: 1.0 
 * Open Source Contribution :- mailchimp.com, tinyMce
 * InSite Contribution :- Andy Charles
 * 
**/

if(version_compare(PHP_VERSION, '5.0.0', '<')){
	echo "I'm sorry, PHP version 5 is needed to run this website. <br>";
	echo "The current PHP version is: ". phpversion() . "<br>";
	echo "Ask your hosting provider to upgrade it for you.";
	exit;
}
define("_NEWSLETTER_VERSION",1.8);

session_start();

header('Content-Type: text/html; charset=UTF-8');

ob_start();// so we can header:redirect later on

if(is_file("config.php")){
	require_once("config.php");
}
require_once("php/functions.php");
require_once("php/class.newsletter.php");
$newsletter = new newsletter();

if(defined("_DB_NAME")){
	
	require_once("php/database.php");
	
	$db = db_connect();
	
	if($_REQUEST['p']!='setup'){
		$newsletter->init();
		require_once("php/auth.php");
	}

}

$show_menu = (isset($_REQUEST['hide_menu'])) ? false : true;

ob_start();
if(defined("_DB_NAME") && $show_menu){ ?>
<?php } ?>
	<?php if(defined("_DB_NAME")){ ?>
		<?php if($show_menu){ ?>
		<div class="navbar">
			<div class="navbar-inner">
				<div class="container">
				<ul class="nav">
					<span class="site_icon"><img src="images/tutlage_icons.png" alt="tutlage_icons" /></span>
					<span class="spacer">&nbsp;</span>
					<li><a href="?p=home"> Dashboard </a></li>
						<li class="divider-vertical"></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown"> Newsletter <b class="caret"></b> </a>
						<ul class="dropdown-menu">
							<li><a href="?p=create"> Create Newsletter </a></li>
							<li><a href="?p=past"> View Newsletter </a></li>
						</ul>
					</li>
					<li class="divider-vertical"></li>
					<li class="dropdown">
						<a href="?p=campaign" class="dropdown-toggle" data-toggle="dropdown"> Campaign </a>
					</li>
					<li class="divider-vertical"></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown"> Members <b class="caret"></b> </a>
						<ul class="dropdown-menu">
							<li><a href="?p=members_add"> Add Members </a></li>
							<li><a href="?p=members"> View Members </a></li>
						</ul>
					</li>
					<li class="divider-vertical"></li>
					<li class="dropdown">
						<a href="?p=groups" class="dropdown-toggle" data-toggle="dropdown"> Groups </a>
					</li>
					<li class="divider-vertical"></li>
					<li class="dropdown">
						<a href="?p=settings" class="dropdown-toggle" data-toggle="dropdown"> Settings </a>
					</li>


				</ul>
				<ul class="nav pull-right">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown"> Welcome <?php echo $_SESSION['user_logged_in']; ?> <b class="caret"></b> </a>
						<ul class="dropdown-menu">
							<li> <a href="?logout"> Logout </a></li>
						</ul>
					</li>
				<ul>

				</div><!-- end container -->
			</div><!-- end navbar-inner -->
		</div><!-- end navbar -->

	<div class="innerContent">
		<?php
		}
		$page=false;
		if(isset($_REQUEST['p'])){
			$page = basename($_REQUEST['p']);
		}
		if(!$page || !is_file("php/pages/".$page.".php")){
			$page = "home";
		}
		include("php/pages/".$page.".php");
	
	}else{
		
		include("php/pages/setup.php");
	}
	?>
	</div>
<?php
$inner_content = ob_get_clean();
include("layout/system_header.php");
echo $inner_content;
include("layout/system_footer.php");
?>
