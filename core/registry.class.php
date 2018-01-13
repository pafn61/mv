<?
/**
 * Keeps all configurations of admin panel and language parts.
 * Works on the singleton pattern.
 */
class Registry
{
	//Current version of core and admin panel (is updated when set up new core)
	private static $version = '2.0';
	
	//Part of singleton pattern to keep only one copy of object.
	private static $instance;
	
	//Main configurations of admin panel taken from /config/ folder
	private static $settings = array();
	
	private function __construct() {}
	
	static public function instance()
	{
		//Creates the self object and loads language parts into self
		if(!isset(self :: $instance))
			self :: $instance = new self();
			
		return self :: $instance;
	}
		
	public function loadSettings($settings_list)
	{
		//Loads the configurations from config files
		self :: $settings = array_merge(self :: $settings, $settings_list);
		
		if(isset(self :: $settings["IncludePath"]) && !isset(self :: $settings["DocumentRoot"]))
			self :: $settings["DocumentRoot"] = preg_replace("/\/$/", "", $_SERVER["DOCUMENT_ROOT"]);
			
		if(isset($settings_list["DomainName"], $settings_list["MainPath"], $settings_list["AdminPanelPath"]))
		{
			$settings_list["DomainName"] = preg_replace("/\/$/", "", $settings_list["DomainName"]);
			self :: $settings["HttpPath"] = $settings_list["DomainName"].$settings_list["MainPath"];
			self :: $settings["HttpAdminPanelPath"] = $settings_list["DomainName"].$settings_list["AdminPanelPath"];
		}
	} 
	
	public function getSetting($key)
	{
		//Gets value of one of config settings		
		if($key == "SecretCode" && (!isset(self :: $settings[$key]) || mb_strlen(self :: $settings[$key], "utf-8") < 32))
		{
			$message = "You must specify 'SecretCode' option in 'config/setup.php' file, at least 32 symbols.";
			Debug :: displayError($message);
		}				
		else if(isset(self :: $settings[$key]))
			return self :: $settings[$key];
	}
	
	public function setSetting($key, $value)
	{
		self :: $settings[$key] = $value;
		return $this;
	}
	
	static public function getDatabaseSetting($key)
	{
		$db = DataBase :: instance();
		return $db -> getCell("SELECT `value` FROM `settings` 
							   WHERE `key`=".$db -> secure($key));
	}
	
	static public function setDatabaseSetting($key, $value)
	{
		$db = DataBase :: instance();
		
		if(!$db -> getCount("settings", "`key`=".$db -> secure($key)))
			return $db -> query("INSERT INTO `settings`(`key`,`value`) 
			  					 VALUES(".$db -> secure($key).",".$db -> secure($value).")");
		else
			return $db -> query("UPDATE `settings` 
								 SET `value`=".$db -> secure($value)."
								 WHERE `key`=".$db -> secure($key));
	}
		
 	public function checkModel($model)
	{
		//Checks in config if the required model is available
		return (in_array(strtolower($model), self :: $settings['Models']) || 
				in_array(strtolower($model), array('garbage','log','users')));
	}
	
	public function getVersion()
	{
		if(isset(self :: $version))
			return self :: $version;
	}
	
	public function getInitialVersion()
	{
		return floatval($this -> getSetting("Version"));
	}
}
?>