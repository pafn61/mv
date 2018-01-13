<?
/**
 * Manages the users and they rights in user area.
 * Main class for checking the authorization of users in mv.
 * Also manages the users righs to access the mv modules.
 */
class User
{
	// UserSession object to control the session.
	public $session;
	
	//Current SQL table
	private $table;
	
	//Table with users rights
	private $rights_table;
	
	//All data related to current user (login, name, ...)
	private $content;
	
	//Id of current administrator
	private $id;
	
	//Error message
	private $error;
	
	public function __construct($login)
	{
		//Sets tables and needed objects, also gets the users rights
		$this -> registry = Registry :: instance(); //Langs and settings
		$this -> db = DataBase :: instance(); //Manages database
		
		$this -> table = "users"; //Table with users data
		$this -> rights_table = "users_rights"; //Table with users rights

		 //Current user's data
		$this -> content = $this -> db -> getRow("SELECT * FROM `".$this -> table."` 
		                                          WHERE `login`=".$this -> db -> secure($login));
		
		if(!isset($this -> content['id']) || !$this -> content['id'])
			return;
											
		$this -> id = $this -> content['id']; //Sets user id
		
		//Gets user's rights in amdin panel
		$this -> rights = $this -> db -> getAll("SELECT * FROM `".$this -> rights_table."` 
		                                         WHERE `user_id`='".$this -> id."'");
		
		//Changes the format of rights for the class methods
		$this -> rights = Users :: arrangeRights($this -> rights);
		
		if($this -> id)  //Object to control the session for this user	
			$this -> session = new UserSession($this -> id);
	}
	
	public function getContent() { return $this -> content; }
	public function getId() { return $this -> id; }
 	public function getField($field) { return $this -> content[$field]; }
 	public function getError() { return $this -> error; }

	public function checkUserLogin()
	{
 		//We check user's passwod in DB according to passed login
 		$password = md5($this -> content['password']); //Hash of password to compare
 		
 		if(!isset($_SESSION['mv']['user']['token']) || !$_SESSION['mv']['user']['token'])
 			return false;
		
		if($_SESSION['mv']['user']['login'] != $this -> content['login'] || $_SESSION['mv']['user']['password'] != $password
		   || !$this -> session -> checkSession())
			return false;
		else if($this -> id != 1 && !$this -> content['active'])
			return false;
		else
		{
			$this -> session -> continueSession(); //Continues the current session in db
			return true;
		}
	}
	
	public function checkUserLoginFlashUpload()
	{
		if(!isset($_SESSION['mv']['user']['token']) || !$_SESSION['mv']['user']['token'])
			return false;
		
 		$password = md5($this -> content['password']);
		
		if($_SESSION['mv']['user']['login'] != $this -> content['login'] || $_SESSION['mv']['user']['password'] != $password)
			return false;
		else if($this -> id != 1 && !$this -> content['active'])
			return false;
		else
			return true;
	}
 	
 	static public function updateLoginData($login, $password)
 	{
 		$_SESSION['mv']['user']['login'] = $login;
 		
 		if($password)
 			$_SESSION['mv']['user']['password'] = md5(md5($password));
 	}
 	
 	public function saveSettings($settings)
 	{
 		$settings = base64_encode(json_encode($settings));
 		
 		$this -> db -> query("UPDATE `".$this -> table."` 
 							  SET `settings`='".$settings."' 
 							  WHERE `id`='".$this -> id."'");
 	}
 	
 	public function loadSettings()
 	{
 		$data = $this -> db -> getCell("SELECT `settings` 
 										FROM `".$this -> table."`  
 										WHERE `id`='".$this -> id."'");
 		
 		return json_decode(base64_decode($data), true);
 	}
 	
 	public function updateSetting($key, $value)
 	{
 		$settings = $this -> loadSettings();
 		$settings[$key] = $value;
 		$this -> saveSettings($settings);
 		
 		return $this;
 	}
 		
	public function checkModelRights($module, $right)
	{
		//Checks if the right for the modele is exists.
		//Root user has access to any module othe users must have rights via policy
		if($this -> id == 1) 
			return true;
			
		$all_modules = array_merge($this -> registry -> getSetting("Models"), array("users", "log", "garbage", "file_manager"));
		$module = strtolower($module);
		
		if(!isset($this -> rights[$module]) || !in_array($module, $all_modules))
			return false;
				
		return (bool) $this -> rights[$module][$right];
	}

	public function extraCheckModelRights($module, $right)
	{
		//Check the rights inside the any amdin panel page related to module (edit, create, ...) and redirects if no right
		if(!$this -> checkModelRights($module, $right))
		{
			$this -> error = I18n :: locale("error-no-rights");
            include $this -> registry -> getSetting("IncludeAdminPath")."controls/internal-error.php";
		}
	}
	
	public function checkModelRightsJS($module, $right, $href)
	{
		if($this -> checkModelRights($module, $right))
    		return $href;
		else
			return "javascript:dialogs.showAlertMessage('{no_rights}');";
	}
	
	public function getUserSkin()
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		if(isset($_SESSION['mv']['settings']['skin']) && $_SESSION['mv']['settings']['skin'])
			if($_SESSION['mv']['settings']['skin'] == "none")
				return "none";
			else if(is_dir($path.$_SESSION['mv']['settings']['skin']) && 
					is_file($path.$_SESSION['mv']['settings']['skin']."/skin.css"))
				return $_SESSION['mv']['settings']['skin'];
	}
	
	public function getAvailableSkins()
	{
		$skins = array("stars");
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		$folders = scandir($path);
		
		foreach($folders as $folder)
			if($folder != "." && $folder != ".." && $folder != "stars" && $folder != "default")
				$skins[] = $folder;

		$skins[] = "none";
				
		return $skins;
	}
	
	public function setUserSkin($name) 
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		if((is_dir($path.$name) && is_file($path.$name."/skin.css")) || $name == "none")
		{
			$_SESSION['mv']['settings']['skin'] = $name;
			$this -> updateSetting("skin", $name);
			return 1;
		}		
	}
	
	public function displayUserSkinSelect()
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		$html = "<select name=\"admin-panel-skin\" id=\"user-settings-skin-select\">\n";
		$folders = array("none") + scandir($path);
		
		foreach($folders as $folder)
			if($folder != "." && $folder != "..")
			{
				$selected = "";
				
				if(isset($_POST['admin-panel-skin']) && $_POST['admin-panel-skin'] == $folder)
					$selected = ' selected="selected"';
				else if(empty($_POST) && isset($_SESSION['mv']['settings']['skin']) && 
						$_SESSION['mv']['settings']['skin'] == $folder)
					$selected = ' selected="selected"';
				
				$html .= "<option".$selected." value=\"".$folder."\">".$folder."</option>\n";
			}
		
		return $html."</select>\n";
	}
}
?>