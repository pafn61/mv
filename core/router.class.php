<?
/**
 * This class checks the requested URL, analyzes it and includes needed pattern (view) to display the page.
 * Also it cleans thr url against dangerous elements.
 */
class Router
{   
   	//Object with settings and localization
   	private $registry;
   
   	//Database object
   	private $db;
   
   	//Initial url came from browser.
   	private $url;
   	
   	//Parts of requested url
   	private $url_parts = array();  
   
   	//Route, the needed view file include.
   	private $route;

   	public function __construct()
   	{
	  	$this -> db = Database :: instance();
      	$this -> registry = Registry :: instance(); //Langs and settings
      	
      	//Cut off the GET parameters and index.php from url
      	$url = trim($_SERVER['REQUEST_URI']);
      	$url = str_replace(array("'", "index.php", "?".$_SERVER['QUERY_STRING']), "", $url);
      	$url = ($url != "/") ? explode("/", $url) : array();
      	
      	//Devides into single parts
      	foreach($url as $part)
      		if($part)
      			$this -> url_parts[] = trim($part);
      	
      	//Cuts local host part of url if the site is not in the root folder
      	if($url_cut = $this -> defineUrlCut())
         	array_splice($this -> url_parts, 0, $url_cut);
         
        //Final clean url
        $this -> url = count($this -> url_parts) ? implode("/", $this -> url_parts)."/" : "/";
   	}
   
	//Accessors
	public function getUrl() { return $this -> url; }
	public function getUrlParts() { return $this -> url_parts; }
	public function getRoute() { return $this -> route; }
	
	public function setUrlParts($url_parts)
	{
		$this -> url_parts = $url_parts;
		return $this; 
	}
	
	public function setUrl($url)
	{
		$this -> url = $url;
		return $this; 
	}
	
	public function getUrlPart($index)
	{
		if(isset($this -> url_parts[$index]))
			return $this -> url_parts[$index];
	}
   
   	public function isIndex()
   	{
   	  	//Defines if we are at the index page of site.
      	return (!isset($this -> url_parts[0]) || !$this -> url_parts[0] || $this -> url_parts[0] == "index");
   	}
   
   	public function defineRoute()
   	{
		require_once $this -> registry -> getSetting('IncludePath')."config/routes.php";
		
		if(isset($this -> url_parts[0], $this -> url_parts[1]) && $this -> url_parts[0] && $this -> url_parts[1])
			$long_path = $this -> url_parts[0]."/".$this -> url_parts[1]; //In case of long route format (2 steps)
		else
			$long_path = false;
   	 	
   	 	//Includes required file of view to display site page.
      	if($this -> isIndex()) //Index page of site
          	$this -> route = $mvFrontendRoutes["index"];
      	else if(array_key_exists($this -> url, $mvFrontendRoutes)) //Exact route
          	$this -> route = $mvFrontendRoutes[$this -> url];
        else if($long_path && count($this -> url_parts) == 3 && array_key_exists($long_path."/*/", $mvFrontendRoutes))
        	$this -> route = $mvFrontendRoutes[$long_path."/*/"]; 
        else if($long_path && array_key_exists($long_path."->", $mvFrontendRoutes))
        	$this -> route = $mvFrontendRoutes[$long_path."->"];          	
        else if(count($this -> url_parts) == 2 && array_key_exists($this -> url_parts[0]."/*/", $mvFrontendRoutes))
        	$this -> route = $mvFrontendRoutes[$this -> url_parts[0]."/*/"]; //Special route format 2 parts
        else if(array_key_exists($this -> url_parts[0]."->", $mvFrontendRoutes))
          	$this -> route = $mvFrontendRoutes[$this -> url_parts[0]."->"]; //Special format
     	else //Default route
          	$this -> route = $mvFrontendRoutes["default"];
          
       //Name of needed file (view)
       $file = $this -> registry -> getSetting('IncludePath')."views/".$this -> route;
      
      	if(!file_exists($file))
         	Debug :: displayError("File of requested view not found ~".$file);
         	
      	return $file; //Name of file to include
   	}
   
   	public function isLocalHost()
   	{
   	  	//Determines if the project located on local host
   	  	return ($_SERVER["REMOTE_ADDR"] == "127.0.0.1" || $_SERVER["REMOTE_ADDR"] == "::1");
   	}
   	
   	private function defineUrlCut()
   	{
   	  	//Determines if the site is not in the root folder of server
      	$site_path = $this -> registry -> getSetting('MainPath');
      
      	if($site_path != '/')
         	return substr_count($site_path, '/') - 1;
      	else
         	return false;
   	}
   	
   	public function defineCurrentPage($start_key)
   	{
   		if(is_numeric($start_key))
   		{
	   		if(isset($this -> url_parts[$start_key]) && $this -> url_parts[$start_key] == "page" && 
	   		   isset($this -> url_parts[$start_key + 1]) && is_numeric($this -> url_parts[$start_key + 1]))
	   		   return intval($this -> url_parts[$start_key + 1]);
   		}
   		else if(isset($_GET[$start_key]) && $_GET[$start_key])
   			return intval($_GET[$start_key]);   			
   	}
   	
   	public function defineSelectParams($index)
   	{
   		$arguments = func_get_args();
   		
   		$url_field = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
   		
   		if(!isset($this -> url_parts[$index]) || !$this -> url_parts[$index])
   			return false;
		else if(is_numeric($this -> url_parts[$index]))
			return array("id" => intval($this -> url_parts[$index]));
		else if($url_field)
			return array($url_field => $this -> url_parts[$index]);
		else
			return false;		
   	}
}
?>