<?
/**
 * Main frontend object $mv class.
 * Contains main variables, objects and pathes.
 * Also has accessors to create models objects and call of 404 error as well.
 */
class Builder
{
	//Router object to define the view of the current page
	public $router; 
   
	//Object with settings
	public $registry;
   
	//Database manager object
	public $db;
	
	//Cache manager object
	public $cache;
	
	//Path from root of the site
	public $root_path;
	
	//Path to css, images and js files
	public $media_path;
	
	//Path from server root to include the files
	public $include_path;
	
	//Path from server root to the views files (templates)
	public $views_path;
	
	public function __construct()
	{
		$this -> registry = Registry :: instance(); //Langs and settings
      	$this -> db = DataBase :: instance(); //Manages database
      
      	if($this -> registry -> getSetting('SessionSupport'))
        	session_start(); //Starts the session if needed
      
      	$this -> router = new Router(); //Object to analyze the requested page
      
      	$this -> include_path = $this -> registry -> getSetting('IncludePath');
      	$this -> views_path = $this -> registry -> getSetting('IncludePath')."views/";      	
      	$this -> root_path = $this -> registry -> getSetting('MainPath');
      	$this -> media_path = $this -> root_path."media/"; 
      	
      	$time_zone = $this -> registry -> getSetting('TimeZone');
		
		if($time_zone) //Sets local time zone if defined
			date_default_timezone_set($time_zone);
			
		//Starts all plugins
		if(count($this -> registry -> getSetting("Plugins")))
			foreach($this -> registry -> getSetting("Plugins") as $plugin)
			{
				$this -> $plugin = new $plugin();
				$this -> $plugin -> root_path = $this -> registry -> getSetting("MainPath");
			}
			
		$this -> cache = new Cache();
	}
	
	public function __get($name)
	{
		//Automatic models objects creating
		if(in_array($name, $this -> registry -> getSetting("Models")))
		{
			$this -> $name = new $name("frontend");
			return $this -> $name;
		}
	}	
	   		
	public function redirect()
	{
		$arguments = func_get_args();
		$path = $this -> registry -> getSetting("MainPath");
		
		if(isset($arguments[0]) && $arguments[0])
			$path .= $arguments[0];

		header("Location: ".$path);
		exit();
	}
	
	public function reload()
	{
		$path = str_replace($_SERVER["QUERY_STRING"], "", $_SERVER["REQUEST_URI"]);
		$path = str_replace("?", "", $path);
		
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0])
			$path .= $arguments[0];
		
		header("Location: ".$path);
		exit();		
	}
	
	public function display404()
	{
		$arguments = func_get_args();
		
		if(!count($arguments) || (isset($arguments[0]) && !$arguments[0]))
		{
			$mv = $this;
			
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			include $this -> registry -> getSetting("IncludePath")."config/routes.php";
			include $this -> registry -> getSetting("IncludePath")."views/".$mvFrontendRoutes["404"];
			exit();
		}
	}
	
	public function checkUrlPart($index)
	{
		$url_parts = $this -> router -> getUrlParts();

		if(!isset($url_parts[$index]) || !$url_parts[$index])
			$this -> display404();
			
		$arguments = func_get_args();
			
		if(isset($arguments[1]) && $arguments[1] == "numeric" && !is_numeric($url_parts[$index]))
			$this -> display404();
			
		return $url_parts[$index];
	}
}
?>