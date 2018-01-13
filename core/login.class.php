<?
/**
 * Class for authorization of users in admin panel.
 * Also contains methods for auto login and recovering admin's password.
 * Creates CSRF token for login and remind password forms.
 */
class Login
{
	//Table with admins 'users'
	private $table;
	
	//Setting singleton object
	private $registry;
	
	//Database manager object
	private $db;
	
	//Internationlization manager object
	private $i18n;

	public function __construct()
	{
		$this -> registry = Registry :: instance(); //Langs and settings
		$this -> db = DataBase :: instance(); //Manages database
		$this -> i18n = I18n :: instance();
		
		$this -> table = "users"; //Table with users data
		
		$time_zone = $this -> registry -> getSetting('TimeZone');
		
		if($time_zone)
			date_default_timezone_set($time_zone);

		if(!isset($_SESSION))
		{
			session_start();
			
			if(!isset($_SESSION['login']['token']))
				$_SESSION['login']['token'] = Service :: randomString(32);
		}
		
		$_SESSION['mv'] = array(); //Delete old session data
	}
	
	public function getToken()
	{
		return md5($_SESSION['login']['token'].$_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	}
	
	public function loginUser($login, $password)
	{
		$row = $this -> db -> getRow("SELECT * FROM `".$this -> table."` 
							  	      WHERE `login`=".$this -> db -> secure($login));
		
		$arguments = func_get_args();
		$autologin = (isset($arguments[2]) && $arguments[2] == "autologin");
		
		//Compares the data came from user and status of user. If the user in blocked we don't let in
		if($row && $row['login'] == $login && ($row['active'] || $row['id'] == 1) && 
		   (($row['password'] == md5($password)) || ($autologin && $row['password'] == $password)))
		{
			$_SESSION['mv']['user']['id'] = $row['id'];
			$_SESSION['mv']['user']['login'] = $row['login'];			
			$_SESSION['mv']['user']['password'] = md5($row['password']);
			$_SESSION['mv']['user']['token'] = Service :: randomString(32);
			
			$data = "`date_last_visit`=".$this -> db -> now('with-seconds');
			
			if(!$row["date_registered"] || $row["date_registered"] == "0000-00-00 00:00:00")
				$data .= ", `date_registered`=".$this -> db -> now('with-seconds');
			
			$this -> db -> query("UPDATE `".$this -> table."` 
			                      SET ".$data." 
								  WHERE `id`='".$row['id']."'"); //Updates the last visit of user
						
			$session = new UserSession($row['id']); //Start new session for this user
			$session -> startSession();
			
			return $row['id'];
		}
		else
			return false;		
	}
	
	public function reload($path)
	{
		header("Location: ".$this -> registry -> getSetting("AdminPanelPath").$path);
		exit();		
	}
	
	public function sendUserPassword($user_data)
	{
		$new_password = Service :: randomString(7); //Generates new password
		$code = Service :: randomString(30); //Code to confirm the changes from email
		
		$this -> addPasswordToConfirm($user_data['id'], $new_password, $code);
		
		//Link for confirmation
		$link = $this -> registry -> getSetting("HttpAdminPanelPath")."login/?action=confirm&code=".$code;	
		$time = floor($this -> registry -> getSetting("NewPasswordLifeTime") / 3600);
   		$arguments = array("number" => $time, "in-hour" => "*number");
   		
   		//Message text
		$message = "<p>".$user_data['name'].",<br />\n";
		$message .= $this -> i18n -> locale("change-password")."</p>\n";
		$message .= "<p>".$this -> i18n -> locale("confirm-time", $arguments)."</p>\n";
		$message .= "<ul>\n<li>".$this -> i18n -> locale("login").": ".$user_data['login']."</li>\n";
   		$message .= "<li>".$this -> i18n -> locale("password").": ".$new_password."</li>\n</ul>\n";
   		$message .= "<p><a href=\"".$link."\">".$link."</a></p>\n";
   		
   		$subject = $this -> i18n -> locale("password-restore");
		
		return Email :: send($user_data['name']." <".$user_data['email'].">", $subject, $message);
	}
	
	public function checkBrowserOldIE()
	{
		$agent = $_SERVER['HTTP_USER_AGENT'];
		//Checks if the browser is old MSIE we deny the access.
		if(preg_match('/MSIE\s\d\.\d/', $agent))
		{
			$version = preg_replace("/.*MSIE\s(\d\.\d).*/", "$1", $agent);
			
			if($version < 9)
			{
				header("Location: ".$this -> registry -> getSetting("AdminPanelPath")."login/error.php?reason=ie");
				exit();
			}
		}
	}
	
	public function displayLoginErrors($errors)
	{	
		if(!is_array($errors) || !count($errors))
			return "";
		
		$html = "";
		
		foreach($errors as $error)
			$html .= "<p>".$error."</p>\n";
			
		return "<div class=\"errors\">".$html."</div>\n";
	}
	
	public function checkUserEmail($email)
	{	
		if($this -> db -> getCount($this -> table, "`email`=".$this -> db -> secure($email)) == 1)
			return $this -> db -> getRow("SELECT * FROM `".$this -> table."` 
										  WHERE `email`=".$this -> db -> secure($email)." 
										  AND `active`='1'");
		return false;
	}
	
	public function addPasswordToConfirm($user_id, $password, $code)
	{
		//Adds new password into DB wait list to confirm from email.
		$table = "users_passwords"; //Table with passwords for confirmation
		$time = $this -> registry -> getSetting("NewPasswordLifeTime");
		
		$this -> db -> query("DELETE FROM `".$table."` WHERE (".$this -> db -> unixTimeStamp('now')."-
							  ".$this -> db -> unixTimeStamp('date').") > ".$time." 
							  OR `user_id`='".$user_id."'"); //Deletes old not valid passwords from list
		
		//Adds new password to wait for the confirmation
		$this -> db -> query("INSERT INTO `".$table."`(`user_id`,`date`,`password`,`code`)
		                      VALUES('".$user_id."', ".$this -> db -> now().",'".md5($password)."', '".$code."')");		
	}
	
	public function confirmNewPassword($code)
	{
		$table = "users_passwords"; //Table with passwords for confirmation
		$time = $this -> registry -> getSetting("NewPasswordLifeTime");

		 //Checks if the password exist according to special code and it has valid time
		$row = $this -> db -> getRow("SELECT * FROM `".$table."`
		                              WHERE (".$this -> db -> unixTimeStamp('now')."-".
									  $this -> db -> unixTimeStamp('date').") < ".$time." 
									  AND `code`=".$this -> db -> secure($code)."");
		
		if(isset($row['user_id']))
		{	 //Updates new password in table with users	
			$this -> db -> query("UPDATE `".$this -> table."` 
								  SET `password`='".$row['password']."' 
								  WHERE `id`='".$row['user_id']."'");
			
			//Deletes data from list
			$this -> db -> query("DELETE FROM `".$table."` 
			                      WHERE `user_id`='".$row['user_id']."'"); 
			return true;
		}
		
		return false;
	}

	public function addNewLoginAttempt($login)
	{
		$login = $this -> db -> secure($login);
		
		$this -> db -> query("INSERT INTO users_logins (`login`,`date`,`ip_address`,`user_agent`) 
		                      VALUES(".$login.",".$this -> db -> now().",'".ip2long($_SERVER['REMOTE_ADDR'])."',
							  '".md5($_SERVER['HTTP_USER_AGENT'])."')");
	}

	public function checkAllAttemptsFromIp()
	{
		$time = $this -> registry -> getSetting("LoginCaptchaLifeTime");
		$table = "users_logins";
		
		//Deletes all old data form table after required period of time
		$this -> db -> query("DELETE FROM `".$table."` 
							  WHERE (".$this -> db -> unixTimeStamp('now')."-".
							  $this -> db -> unixTimeStamp('date').") > ".$time);
							   
		//Checks all attempts to login from current ip address
		return $this -> db -> getCount("users_logins", "`ip_address`='".ip2long($_SERVER['REMOTE_ADDR'])."' 
										AND ((".$this -> db -> unixTimeStamp('now')."-".
							   			$this -> db -> unixTimeStamp('date').") < ".$time.")");
	}
	
	public function rememberUser($id)
	{
		$user = $this -> db -> getRow("SELECT * FROM `".$this -> table."` WHERE `id`='".intval($id)."'");
		$code = md5($id.$user['login'].$user['password'].$_SERVER['HTTP_USER_AGENT']);
		
		$time = $this -> registry -> getSetting("AutoLoginLifeTime");
		$time = $time ? time() + $time : time() + 3600 * 24 * 31;
		$http_only = $this -> registry -> getSetting("HttpOnlyCookie");
		
		setcookie("remember_id", $id, $time, $this -> registry -> getSetting("AdminPanelPath"), null, null, $http_only);
		setcookie("remember_code", $code, $time, $this -> registry -> getSetting("AdminPanelPath"), null, null, $http_only);
	}
	
	public function cancelRemember()
	{
		$time = $this -> registry -> getSetting("AutoLoginLifeTime");
		$time = $time ? time() + $time : time() + 3600 * 24 * 31;
		$http_only = $this -> registry -> getSetting("HttpOnlyCookie");
		
		setcookie("remember_id", "", $time, $this -> registry -> getSetting("AdminPanelPath"), null, null, $http_only);
		setcookie("remember_code", "", $time, $this -> registry -> getSetting("AdminPanelPath"), null, null, $http_only);
				
		return $this;
	}
	
	public function autoLogin($id, $code)
	{
		$user = $this -> db -> getRow("SELECT * FROM `".$this -> table."` WHERE `id`='".intval($id)."'");
		
		if(!$user)
			return false;
		
		$check_code = md5($id.$user['login'].$user['password'].$_SERVER['HTTP_USER_AGENT']);

		if($code == $check_code)
			return $this -> loginUser($user['login'], $user['password'], 'autologin');
		else
			return false;
	}
}
?>