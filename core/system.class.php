<?
/**
 * Main object in admin panel. 
 * Contains object of current model, settings and other tools.
 * Checks the authorization of current administartor.
 */ 
class System
{
	//Current user object
	public $user;
	
	//Current module of cms (usually for Simple Module Factory)
	public $model;

	//Object to draw the main menus of interface of backend
	public $menu;
	
	//Object for languages and settings, configurations of sysytem
	public $registry;
	
	//Database manager object
	public $db;
	
	//Localization and regiona standarts
	public $i18n;
	
	//Versions manager object
	public $versions;
	
	//Log manager object
	public $log;
	
	//Internal error of script
	public $error;
	
	public function __construct()
	{
		session_start();
		$this -> registry = Registry :: instance(); //Langs and settings
		
		$time_zone = $this -> registry -> getSetting('TimeZone');
		
		if($time_zone)
			date_default_timezone_set($time_zone);
		
		$this -> db = Database :: instance(); //Manages database
		$this -> i18n = I18n :: instance();
				
		$arguments = func_get_args(); //Checks some extra params
		
		//If we at some page called by ajax
		$ajax_request = (isset($arguments[0]) && $arguments[0] == 'ajax');
		
		if(!$ajax_request && !isset($_SESSION['mv']['user']['login'], $_SESSION['mv']['user']['password']))
			if(isset($_COOKIE["remember_id"], $_COOKIE["remember_code"])) //If auto login
			{
				$login = new Login();
								
				if($id = $login -> autoLogin($_COOKIE["remember_id"], $_COOKIE["remember_code"]))
				{
					$login -> rememberUser($id); //Prolong auto login time
					header("Location: ".$_SERVER['REQUEST_URI']);
					exit();
				}
				else
					$login -> cancelRemember();
			}
		
		if(isset($_SESSION['mv']['user']['login'], $_SESSION['mv']['user']['password']))
			$this -> user = new User($_SESSION['mv']['user']['login']);
		else if(!$ajax_request)
			$this -> backToLogin();
			
		if($ajax_request)
		{
			$region = isset($_SESSION['mv']['settings']['region']) ? $_SESSION['mv']['settings']['region'] : I18n :: defineRegion();
			$this -> i18n -> setRegion($region);
			
			return; //If it's ajax we stop further construction 
		}
		
		if(!$this -> user -> checkUserLogin())
		{
			if(isset($_COOKIE["remember_id"], $_COOKIE["remember_code"]))
			{
				$login = new Login();
				$autologin_id = $login -> autoLogin($_COOKIE["remember_id"], $_COOKIE["remember_code"]);

				if($autologin_id)
					$login -> rememberUser($autologin_id); //Prolongs auto login time
				else
					$login -> cancelRemember();
			}
			
			if(!isset($autologin_id) || !$autologin_id)
				$this -> backToLogin();
		}
		
		if(!isset($_SESSION['mv']['settings']))
			$_SESSION['mv']['settings'] = $this -> user -> loadSettings();
			
		if(isset($_SESSION['mv']['settings']['region']) && I18n :: checkRegion($_SESSION['mv']['settings']['region']))
			$region = $_SESSION['mv']['settings']['region'];
		else
		{
			$region = I18n :: defineRegion();
			$this -> user -> updateSetting('region', $region);
		}

		$this -> i18n -> setRegion($region);
		
		$this -> menu = new Menu(); //Runs object for menu building
		$this -> menu -> setUser($this -> user);

		if(isset($arguments[0]) && $arguments[0]) //Runs module if name was passed
			$this -> runModel(strtolower($arguments[0]));
	}
	
	public function backToLogin()
	{
		$url = preg_replace("/\?.*$/", "", $_SERVER['REQUEST_URI']);
		
		if($url != $this -> registry -> getSetting("AdminPanelPath"))
		{
			$url = str_replace($this -> registry -> getSetting("AdminPanelPath"), "", $_SERVER['REQUEST_URI']);
			$search = array("&action=create", "&action=update", "&continue", "&updated", "&created", "&edit");
			$url = str_replace($search, "", $url);
			
			$url = "?back-path=".base64_encode($url);
		}
		else
			$url = "";
		
		header("Location: ".$this -> registry -> getSetting("AdminPanelPath")."login/".$url);
		exit();
	}
	
	public function ajaxRequestCheck()
	{
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
			return false;
		
		if(isset($_SESSION['mv']['user']['login'], $_SESSION['mv']['user']['password']))
			$this -> user = new User($_SESSION['mv']['user']['login']);
		
		if(!is_object($this -> user) || !$this -> user -> checkUserLogin())
		{
			$autologin = false;
				
			if(isset($_COOKIE["remember_id"], $_COOKIE["remember_code"]))
			{
				$login = new Login();
				$autologin = $login -> autoLogin($_COOKIE["remember_id"], $_COOKIE["remember_code"]);
		
				if($autologin)
				{
					$login -> rememberUser($autologin);
					
					if(isset($_SESSION['mv']['user']['login'], $_SESSION['mv']['user']['password']))
						$this -> user = new User($_SESSION['mv']['user']['login']);
					else
						return false;
				}
				else
					$login -> cancelRemember();
			}
			
			return $autologin;
		}
		
		return true;
	}
	
	public function ajaxRequestContinueOrExit()
	{
		if(!$this -> ajaxRequestCheck())
			exit();		
	}
	
	public function getError() { return $this -> error; }
	public function getModel() { return $this -> model; }
	
	public function detectModel()
	{
		if(isset($_GET['model']) && $_GET['model'])
			$this -> runModel($_GET['model']);
		else
			$this -> displayInternalError('error-params-needed');
			
		return $this;
	}
	
	public function runModel($class_name)
	{
		if(in_array($class_name, $this -> registry -> getSetting('Models')) || 
		   in_array($class_name, array('garbage','users','log'))) //If the module in on
		{			
			$this -> model = new $class_name("backend"); //Creates object of module with possible arguments
			$this -> model -> loadRelatedData();
			
			if(get_parent_class($this -> model) != "Model_Simple")
				$this -> model -> runPagerFilterSorter();
			
			$this -> model -> setUser($this -> user); //Passes user into module			
		}
		else
		{
			$message = "Undefined model '".$class_name."'. To run the model you need to create class file in folder ~/models/ ";
			$message .= "and add the model name into setting 'Models' of config file ~/config/models.php";
			Debug :: displayError($message);
		}
		
		return $this;
	}
	
	public function runVersions()
	{
		$this -> versions = new Versions($this -> model -> getModelClass(), $this -> model -> getId());
		$this -> versions -> setLimit($this -> model -> getVersionsLimit());
		
		return $this;
	}
	
	public function passVersionContent()
	{
		$this -> model -> read($this -> versions -> load());
		
		return $this;
	}
	
	public function displayInternalError($error_key)
	{
		$this -> error = I18n :: locale($error_key);
        include $this -> registry -> getSetting("IncludeAdminPath")."controls/internal-error.php";
	}
	
	public function reload($path)
	{
		header($_SERVER["SERVER_PROTOCOL"]." 302 Found");
		header("Location: ".$this -> registry -> getSetting("AdminPanelPath").$path);
		exit();
	}
	
	public function searchInAllModels($request)
	{
		$result = $this -> searchInAllModelsAjax($request, true);
		$request_re = Service :: prepareRegularExpression($request);
		$number = 0;
		$html_strings = $sorted_result = array();
		
		if(!is_array($result) || !count($result) || !isset($result["rows"]))
			return array("number" => 0, "html" => "");
		
		foreach($result["rows"] as $key => $row) //Makes some relevence 
			if(!isset($row["simple_model"]))
			{
				$model = $result["models"][$row["model"]];
				$name = $model -> tryToDefineName($row);
			
				if(preg_match("/^".$request_re."$/ui", $name)) //Exact un name matches go upper
				{
					array_unshift($sorted_result, $row);
					unset($result["rows"][$key]);
				}
				else if(preg_match("/".$request_re."/ui", $name))
				{
					$sorted_result[] = $row;					
					unset($result["rows"][$key]);
				}
			}
		
		$sorted_result = array_merge($sorted_result, $result["rows"]);
		
		//Final check if we have needed phraze in results
		foreach($sorted_result as $key => $row)
		{
			$found = false;
			
			foreach($row as $value)
				if(preg_match("/".$request_re."/ui", strip_tags($value)))
				{
					$found = true;		
					break;
				}
			
			if(!$found)
				unset($sorted_result[$key]);
		}
		
		foreach($sorted_result as $row) //Html output process
		{
			$model = $result["models"][$row["model"]];
			$html = "<div>\n";
			$url = $this -> registry -> getSetting("AdminPanelPath")."model/";
			$name = "";
			
			if(isset($row["simple_model"])) //Name of result for simple nodel
			{
				$url .= "index-simple.php?model=".$row["model"];				
				$html .= "<p class=\"found-name\">".(++ $number).". ".I18n :: locale("simple-module");
				$html .= " <a class=\"name\" href=\"".$url."\">".$model -> getName()."</a></p>\n";
			}
			else //Name of result for regular model
			{
				$html .= "<p class=\"found-name\">".(++ $number).". <a class=\"name\" href=\"".$url;
				$html .= "update.php?model=".$row["model"]."&id=".$row["id"]."\">\n";
				
				$name = $model -> tryToDefineName($row);
				
				$html .= preg_replace("/(".$request_re.")/ui", "<span>$1</span>", $name);				
				$html .= "</a> Модуль: <a href=\"".$url."?model=".$row["model"]."\">";
				$html .= $model -> getName()."</a></p>\n";
			}
				
			$description = false;
			$fields_types = array("char", "email", "redirect", "url", "text");
				
			foreach($row as $field => $value) //If field contains request text we mark it with span
				if($field != "id" && $field != "model" && preg_match("/".$request_re."/ui", $value))
				{
					if(!$object = $model -> getElement($field))
						continue;

					$type = $object -> getType();
						
					if(!in_array($type, $fields_types) || ($name && $name == $value))
						continue;
					else if($type == "text") //Text field process, cut off text parts
					{
						$text = strip_tags($value);
						
						if(!preg_match("/".$request_re."/ui", $text))
							continue;
						
						$start = mb_stripos($text, $request, 0, "utf-8");
								
						if($start > 50)
						{
							$text = mb_substr($text, $start - 40, 400, "utf-8");
							$text = "... ".trim(preg_replace("/^[^\s]*/ui", "", $text));
						}
								
						$value = Service :: cutText($text, 370, " ...");
					}

					if(!$description)
					{
						$description = true;
						$html .= "<p class=\"found-text\">";
					}
						
					$html .= $model -> getCaption($field).": ";
					$html .= preg_replace("/(".$request_re.")/ui", "<span>$1</span>", $value)."<br />";
				}
					
			if($description)
			{
				$html = preg_replace("/<br \/>$/", "", $html);
				$html .= "</p>\n";
			}
			
			$html .= "</div>\n";
			
			$html_strings[] = $html;
		}
		
		return array("number" => count($sorted_result), "html" => $html_strings);
	}
	
	public function searchInAllModelsAjax($request, $full_search)
	{
		$fields_types = array("char", "email", "redirect", "url");
		$results = $model_objects = $full_results = array();
		$request_sql = $this -> db -> secure("%".$request."%"); //Prepare search phrase
		$limit = 10;
		
		if(mb_strlen($request, "utf-8") < 2) //Too short request
			return $results;
		
		foreach($this -> registry -> getSetting("Models") as $model_name)
			$model_objects[$model_name] = new $model_name(); //Creates models objects
		
		//Search in all allowed fields exept for text type
		$search_data = $this -> searchData($model_objects, $request_sql, $fields_types);
		
		if($full_search) //Data for full search in admin panel
			foreach($search_data["rows"] as $row)
				$full_results[$row["model"]][$row["id"]] = $row;
		
		foreach($search_data["rows"] as $row) //Autocomplete data process
			foreach($row as $field => $value)
				if(in_array($field, $search_data["fields"])) //If it's allowed field
				{
					$value = mb_strtolower(strip_tags($value), "utf-8");
					$value = htmlspecialchars_decode($value, ENT_QUOTES);
								
					if(preg_match("/".Service :: prepareRegularExpression($request)."/ui", $value))
						if(!in_array($value, $results))
							$results[] = $value; //Adds new value for autocompllete
										
					if(count($results) >= $limit && !$full_search) //If its limit for autocomplete
						return $results;
				}

		if($full_search || count($results) < $limit) //Next step of search, goes throught text fields
		{
			$search_data = $this -> searchData($model_objects, $request_sql, array("text"));
			
			if($full_search) //Search results preparing for search page of admin panel
			{
				foreach($search_data["rows"] as $row)
					if(isset($row["simple_model"]))
					{
						foreach($row as $field => $value)
							if($field != "model" && $field != "simple_model" && $field != "id")
								$full_results[$row["model"]][0][$field] = $value;
							
						$full_results[$row["model"]][0]["model"] = $row["model"];
						$full_results[$row["model"]][0]["id"] = 0;
					}
					else if(!isset($full_results[$row["model"]][$row["id"]]))
						$full_results[$row["model"]][$row["id"]] = $row;
						
				$final_full_results = array();
						
				foreach($full_results as $key => $rows)
					$final_full_results = array_merge($final_full_results, $rows);
						
				return array("models" => $model_objects, "rows" => $final_full_results);
			}
			
			foreach($search_data["rows"] as $row) //Ajax autocomplete results process
				foreach($row as $field => $value)
					if(in_array($field, $search_data["fields"]))
					{
						$value = strip_tags($value);
						$value = htmlspecialchars_decode($value, ENT_QUOTES);
						$re = "\s\.,:;!\?\"'\+\(\)\[\}\^\$\*";
						preg_match("/[^".$re."]*".$request."[^".$re."]*/ui", $value, $matches);
							
						foreach($matches as $text)
						{
							$text = mb_strtolower($text, "utf-8");
								
							if(!in_array($text, $results))
								$results[] = $text;
									
							if(count($results) >= $limit && !$full_search)
								return $results;
						}
					}
		}
		
		return $results;
	}
	
	private function searchData($models, $request_sql, $types)
	{
		$rows = $fields = array();
		
		foreach($models as $model) //Search in all passed models
		{
			$simple_model = (get_parent_class($model) == "Model_Simple");
			$query = array();
			
			foreach($model -> getElements() as $object)
				if(in_array($object -> getType(), $types))
				{
					if($object -> getType() == "text" && $object -> getProperty("display_method"))
						continue;
					
					$fields[] = $object -> getName();
					
					//SQL query preparing
					if($simple_model)
						$query[] = "(`key`='".$object -> getName()."' AND `value` LIKE ".$request_sql.")";
					else
						$query[] = "`".$object -> getName()."` LIKE ".$request_sql;
				}
				
			if(!count($query)) //If no fields for search
				continue;
									
			$query = "SELECT * FROM `".$model -> getTable()."` WHERE ".implode(" OR ", $query);
			$found_rows = $this -> db -> getAll($query); //Search SQL query
			
			if(!count($found_rows))			
				continue;
			
			if($simple_model) //Results from simple models process
			{
				$simple_row = array();
				
				foreach($found_rows as $row)
					$simple_row[$row["key"]] = $row["value"];
					
				$simple_row["id"] = 0;
				$simple_row["simple_model"] = true;
				$found_rows = array($simple_row);
			}
			
			foreach($found_rows as $key => $row)
				$found_rows[$key]["model"] = $model -> getModelClass();
				
			$rows = array_merge($rows, $found_rows); //Result rows			
		}			
		
		return array("rows" => $rows, "fields" => $fields);
	}
	
	public function displayWarningMessages()
	{
		if(isset($_SESSION['mv']['closed-warnings']) && $_SESSION['mv']['closed-warnings'])
			return;
			
		$message = array();
		$router = new Router();
		
		if(!$router -> isLocalHost())
		 	if($this -> registry -> getSetting("Mode") == "development" || $this -> registry -> getSetting("Mode") == "test")
				$message[] = I18n :: locale("warning-development-mode");
			
		$root_password = $this -> db -> getCell("SELECT `password` FROM `users` WHERE `id`='1'");
			
		if(!$router -> isLocalHost() && $root_password == md5("root"))
			$message[] = I18n :: locale("warning-root-password");

		$logs_folder = $this -> registry -> getSetting("IncludePath")."log/";
		
		if(is_dir($logs_folder) && !is_writable($logs_folder))
			$message[] = I18n :: locale("warning-logs-folder");
			
		$files_folders = array("", "files/", "images/", "models/", "tmp/", "tmp/filemanager/");
		$files_root = $this -> registry -> getSetting("FilesPath");
		
		foreach($files_folders as $folder)
			if(is_dir($files_root.$folder) && !is_writable($files_root.$folder))
			{
				$message[] = I18n :: locale("warning-userfiles-folder");
				break;
			}
		
		if(count($message))
		{
			$html = "<div id=\"admin-system-warnings\">\n";

			foreach($message as $string)
				$html .= "<p>".$string."</p>\n";
   
			return $html."<span id=\"hide-system-warnings\">".I18n :: locale("hide")."</span>\n</div>\n";
		}
	}
	
	public function getToken()
	{
		$token = $_SESSION['mv']['user']['token'].$_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"];
		$token .= $this -> user -> getField("login").$this -> user -> getField("password");
		
		return md5($token);
	}
}
?>