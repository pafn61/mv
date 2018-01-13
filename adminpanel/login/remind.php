<? 
include_once "../../config/autoload.php";

$login = new Login();
$login -> checkBrowserOldIE();

$i18n = I18n :: instance();
$region = I18n :: defineRegion();
$i18n -> setRegion($region);

$errors = array();

if(!empty($_POST))
{
	if(!isset($_POST['email']) || !trim($_POST['email']))
		$errors[] = I18n :: locale("complete-email");
	else if(!preg_match("/^[-a-z0-9_\.]+@[-a-z0-9_\.]+\.[a-z]{2,5}$/i", trim($_POST['email'])))
		$errors[] = I18n :: locale("error-email-format", array('field' => 'E-mail'));
			
	if(!isset($_POST['captcha']) || !trim($_POST['captcha']))
		$errors[] = I18n :: locale("complete-captcha");
	else if(!isset($_SESSION['login']['captcha']) || md5(trim($_POST['captcha'])) != $_SESSION['login']['captcha'])
		$errors[] = I18n :: locale("wrong-captcha");
	
	if(!isset($_POST["admin-login-csrf-token"]) || $_POST["admin-login-csrf-token"] != $login -> getToken())
		$errors[] = I18n :: locale("error-wrong-token");
	
	if(!count($errors))
	{
		$admin_data = $login -> checkUserEmail(trim($_POST['email']));
		
		if($admin_data && is_array($admin_data) && isset($admin_data['id']))
		{
			if($login -> sendUserPassword($admin_data))
			{
				$_SESSION['login']['remind'] = true;
				$login -> reload("login/remind.php");
			}
			else
				$errors[] = I18n :: locale("error-failed");
		}	
		else
			$errors[] = I18n :: locale("not-user-email");
	}
}

include $registry -> getSetting('IncludeAdminPath')."login/login-header.php";
?>
	<div id="container">
	   <div id="login-area">
           <div id="login-top"></div>
           <div id="login-middle">
	           <div id="header"><? echo I18n :: locale('password-restore'); ?></div>
               <? 
               		if(isset($_SESSION['login']['remind']) && $_SESSION['login']['remind'])
               		{
               			$time = floor($registry -> getSetting("NewPasswordLifeTime") / 3600);
               			$arguments = array("number" => $time, "in-hour" => "*number");
               			
               			echo "<p class=\"done\">".I18n :: locale("change-password-ok", $arguments)."</p>\n";
               			echo "<a href=\"".$registry -> getSetting('AdminPanelPath')."login/\">".I18n :: locale('back')."</a>\n";
               			unset($_SESSION['login']);
               		}
               		else
               			include $registry -> getSetting('IncludeAdminPath')."login/remind-form.php";
               ?>
           </div>
           <div id="login-bottom"></div>
	   </div>
	</div>
</body>
</html>