<?
/**
 * Class for detecting bugs, time measuring and browser detection.
 */
class Debug
{	
	//Says if we need to display the debug info at the end of the site page. 
	private $show_info;
		
	public function __construct($show_info)
	{
		//Starts the time measuring if the param is passed
		if($show_info)
		{
			$this -> show_info = true;
			$this -> timeStart();
		}
	}
	
	public static function pre($var)
	{
		//To make code shorter adds <pre> tags and prints the var.
		echo "\n<pre>";
		print_r($var);
		echo "</pre>\n";
	}	

	static public function browser()
	{
		//Detects the name of browser
	
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return;		
		
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

		if(preg_match('/opr\/\d+/', $agent) || preg_match('/opera/', $agent))
			return 'opera';			
		if(strpos($agent, 'yabrowser') !== false)
			return 'yandex';		
		if(strpos($agent, 'chrome') !== false)
			return 'chrome';
		if(strpos($agent, 'firefox') !== false)
			return 'firefox';
		if(strpos($agent, 'msie') !== false || strpos($agent, 'trident/') !== false)
			return 'ie';
		if(strpos($agent, 'safari') !== false)
			return 'safari';
	}
	
	static public function isMobile()
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return;

		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
		if(strpos($agent, 'windows phone') !== false)
			return 'windows';
		if(strpos($agent, 'android') !== false && strpos($agent, 'mobile') !== false)
			return 'android';
		if(strpos($agent, 'iphone') !== false)
			return 'iphone';
		if(strpos($agent, 'ipod') !== false)
			return 'ipod';
		if(strpos($agent, 'blackberry') !== false)
			return 'blackberry';
		if(strpos($agent, 'iemobile') !== false)
			return 'ie';
	}
	
	static public function isTablet()
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return;
	
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
		if(strpos($agent, 'windows') !== false && strpos($agent, 'touch') !== false)
			return 'windows';
		if(strpos($agent, 'ipad') !== false)
			return 'ipad';
		if(strpos($agent, 'android') !== false && strpos($agent, 'mobile') === false)
			return 'android';
	}
		
	public function timeStart()
	{
		//Starts timer to find out the time of process execution
		$this -> time_start = @gettimeofday();
	}
	
	public function timeEnd()
	{
		//Stops timer to get the time passed
		$this -> time_end = @gettimeofday();
	}	

	public function displayTime()
	{
		//Shows time between start and stop
		$time_sec = ($this -> time_end['sec'] - $this -> time_start['sec']);
		$time_msec = ($this -> time_end['usec'] - $this -> time_start['usec']) / 1000000;
		
		return "<p>Time: ".($time_sec + $time_msec)."</p>";
	}
	
	static public function displayTotalQueries()
	{
		//Total number of SQL queries done during the work of script
		$count = count(explode("-*//*-", Database :: $total)) -1;
		return "<p>Total SQL queries: ".$count."</p>";
	}

	static public function displayAllQueries()
	{
		//Dispalays all queries which was already done
		$queries = explode("-*//*-", Database :: $total);
		unset($queries[0]);
		
		$queries = preg_replace("/[\n\r\t]+/", "", $queries);
		$queries = preg_replace("/\s+/", " ", $queries);
		
		$html = "";
		foreach($queries as $number => $query)
			$html .= "<span>".$number."</span> ".$query."<br />\n";
			
		return $html;
	}
	
	public function displayInfo(Router $router)
	{
		//Displays the time and sql queries if we passed the param before
		if($this -> show_info)
		{
			$registry = Registry :: instance();
			echo "<style type=\"text/css\">\n#debug-info{padding:10px 10px 5px 10px; font-size:14px; font-family:Arial;";
			echo "border-top:2px solid #ff4343; border-bottom:2px solid #ff4343; background:#eee; clear:both}\n";
			echo "#debug-info p{padding:0 0 7px 0; color:#222; margin:0}\n";
			echo "#debug-info span{padding-right: 7px;}\n</style>\n";
			echo "<div id=\"debug-info\">\n<p>Route: views/".$router -> getRoute()."</p>\n";
			$this -> timeEnd();
		    echo $this -> displayTime();
		    echo $this -> displayTotalQueries();
		    echo "<p>".$this -> displayAllQueries()."</p>\n";
		    echo "<p>MV framework, version ".$registry -> getVersion()."</p>\n";
		   	echo "</div>\n";
		}
	}
	
	static public function displayError($error)
	{
		$registry = Registry :: instance();
		
		if($registry -> getSetting('Mode') == 'development')
		{
			$error_header = $registry -> getSetting('IncludeAdminPath')."controls/debug-error.php";
			include $error_header;
		}
		else
		{
			$backtrace = debug_backtrace();
			$error = "Error! ".$error;
			
			if(isset($backtrace[2]['line'], $backtrace[2]['file']))
				$error .= " in line ".$backtrace[2]['line']." of file ".$backtrace[2]['file'];
			
			Log :: add($error);
		}
		
		$registry -> setSetting("ErrorAlreadyLogged", true);
		
		exit();
	}
}