<?
include_once "../../config/autoload.php";

$registry = Registry :: instance();
$login = new Login();
$login -> checkBrowserOldIE();

$errors = array();
$login_attempts = $login -> checkAllAttemptsFromIp();
$checked_remember = isset($_POST['remember']) ? " checked=\"checked\"" : "";

if(isset($_GET["region"]))
{
	I18n :: saveRegion($_GET["region"]);
	$login -> reload("login/");
}
else
{
	$i18n = I18n :: instance();
	$region = I18n :: defineRegion();
	$i18n -> setRegion($region);	
}

if(!$checked_remember)
	$login -> cancelRemember();

if(!empty($_POST))
{
	if(!isset($_POST['login']) || !trim($_POST['login']))
		$errors[] = I18n :: locale("complete-login");
	
	if(!isset($_POST['password']) || !trim($_POST['password']))
		$errors[] = I18n :: locale("complete-password");
		
	if($login_attempts >= 2)
	{
		if(!isset($_POST['captcha']) || !trim($_POST['captcha']))
			$errors[] = I18n :: locale("complete-captcha");
		else if(!isset($_SESSION['login']['captcha']) || md5(trim($_POST['captcha'])) != $_SESSION['login']['captcha'])
			$errors[] = I18n :: locale("wrong-captcha");
	}
	
	if(!isset($_POST["admin-login-csrf-token"]) || $_POST["admin-login-csrf-token"] != $login -> getToken())
		$errors[] = I18n :: locale("error-wrong-token");
	
	if(trim($_POST['login']) && trim($_POST['password']) && !count($errors))
		if($id = $login -> loginUser(trim($_POST['login']), trim($_POST['password'])))
		{
			if($checked_remember)
				$login -> rememberUser($id);
			else
				$login -> cancelRemember();
			
			unset($_SESSION['login_captcha']);
			$path = "";
			
			if(isset($_GET["back-path"]) && $_GET["back-path"])
				$path = "?back-path=".trim($_GET["back-path"]);
			
			$user = new User(trim($_POST['login']));
			$user -> updateSetting('region', $region);
			unset($_SESSION['login']);
			
			$login -> reload("login/loading.php".$path);		
		}
		else
		{
			$errors[] = I18n :: locale("login-failed");
			
			if($login_attempts <= 2)
				$login -> addNewLoginAttempt(trim($_POST['login']));
				
			$login_attempts ++;
		}
}

if(isset($_GET['logout'], $_GET['code']) && is_numeric($_GET['logout']))
{	
	$user_id = intval($_GET['logout']);
	
	if($user_id && $_GET['code'] == md5($user_id.session_id().$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']))
	{
		set_time_limit(300);
		$session = new UserSession($user_id);
		$session -> stopSession();
		
		Filemanager :: deleteOldFiles($registry -> getSetting("FilesPath")."tmp/");
		Filemanager :: deleteOldFiles($registry -> getSetting("FilesPath")."tmp/admin_multi/");
		Filemanager :: deleteOldFiles($registry -> getSetting("FilesPath")."tmp/admin_record/");
		Filemanager :: deleteOldFiles($registry -> getSetting("FilesPath")."tmp/redactor/");
		Filemanager :: makeModelsFilesCleanUp();
	}
	
	$login -> cancelRemember() -> reload("login/");
}

if(isset($_GET['action']) && $_GET['action'] == 'confirm' && isset($_GET['code']))
{	
	if($login -> confirmNewPassword($_GET['code']))
		$_SESSION['login']['message'] = I18n :: locale("password-confirmed"); 
	else
		$_SESSION['login']['message'] = I18n :: locale("password-not-confirmed"); 
	
	$login -> reload("login/");
}

include $registry -> getSetting('IncludeAdminPath')."login/login-header.php";
?>
	<div id="container">
	   <div id="login-area">
           <div id="login-middle">
	           <div id="header"><? echo I18n :: locale('authorization'); ?></div>
               <? 
               		if(isset($_SESSION['login']['message']) && $_SESSION['login']['message'])
               			echo "<p>".$_SESSION['login']['message']."</p>\n";
               			
               		unset($_SESSION['login']['message']);
               ?>
	           <form method="post" action="">
	               <table>
                      <tr>
                        <td class="errors" colspan="2">
                           <? echo $login -> displayLoginErrors($errors); ?>
                        </td>
                      </tr>
	                  <tr>
	                     <td class="caption"><? echo I18n :: locale('login'); ?></td>
	                     <td><input type="text" name="login" value="<? echo isset($_POST['login']) ? trim(htmlspecialchars($_POST['login'])) : ""; ?>" autocomplete="off" /></td>
	                  </tr>
	                  <tr>
	                     <td class="caption"><? echo I18n :: locale('password'); ?></td>
	                     <td><input class="password" type="password" name="password" value="" autocomplete="off" /></td>
	                  </tr>
                      <? 
                          if($login_attempts >= 2)
                          	 include $registry -> getSetting('IncludeAdminPath')."login/captcha.php";
                      ?>
                      <tr>
                        <td colspan="2">
                           <div id="remember">
                              <input id="remember-login" type="checkbox" name="remember"<? echo $checked_remember; ?> />
                              <label for="remember-login"><? echo I18n :: locale('remember-me'); ?></label>                              
                           </div>
                           <div class="submit">
                              <input class="submit" type="submit" value="<? echo I18n :: locale('login-action'); ?>" />
                              <input type="hidden" name="admin-login-csrf-token" value="<? echo $login -> getToken(); ?>" />
                           </div>
                           <div class="remind">
                              <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/remind.php" class="fogot-password"><? echo I18n :: locale('fogot-password'); ?></a>
                           </div>
                         </td>
                      </tr>
                      <tr>
                        <td class="caption"><? echo I18n :: locale('language'); ?></td>
                        <td class="language-select">
                           <select name="region" id="select-login-region">
                              <? echo I18n :: displayRegionsSelect($region); ?>
                           </select>
                        </td>
                      </tr>
	               </table> 
	           </form>               
           </div>
	   </div>
	</div>
</body>
</html>