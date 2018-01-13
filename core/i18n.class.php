<?
/*
 * Class with methods for localization.
 * Works with translations, dates and numbers.
 * Takes source files from /adminpanel/i18n/ and /customs/i18n/ folders.
 */
class I18n
{
	//Singleton pattern
	private static $instance;
	
	//Current region key (en, am, de, ru)
	private static  $region;
	
	//Current format of date 
	private static $date_format;
	
	//Delimeter of date parts (".", "/" or "-")
	private static $date_separator;
	
	//National rules for translations
	private static $plural_rules;
	
	//File with JavaScript translations for admin panel
	private static $js_translation_file;
	
	//Special translations for non-latin languages
	private static $translit_rules;
	
	//Main translations array with words
	private static $translation;
	
	//Months names
	private static $month;
	
	//Additional months names for non-latin languages 
	private static $month_case;
	
	//Week days names
	private static $week_days;
	
	//Decimal mark for float numbers
	private static $decimal_mark;
	
	private function __construct() {}
	
	static public function instance()
	{
		//Creates the self object and loads language parts into self
		if(!isset(self :: $instance))
			self :: $instance = new self();
			
		return self :: $instance;
	}
	
	static public function setRegion($region)
	{
		$registry = Registry :: instance();	
		$registry  -> setSetting("AmericanFix", false);
		
		//American locale is the same as UK but date format, and it works with 'en' folder
		if($region == "am")
		{
			$region = "en";
			$registry -> setSetting("Region", "en") -> setSetting("AmericanFix", true);
		}
		else
			$registry -> setSetting("Region", $region);
		
		self :: $region = $region;			
		$region_folder = $registry -> getSetting("IncludeAdminPath")."i18n/".$region."/";
		
		//Main locale settings and translations
		if(is_dir($region_folder) && file_exists($region_folder."locale.js") && file_exists($region_folder."locale.php"))
		{
			include $region_folder."locale.php";
			
			if(isset($regionalData, $regionalData['date_format'], $regionalData['translation'], $regionalData['plural_rules']))
			{
				self :: $date_format = $registry  -> getSetting("AmericanFix") ? "mm/dd/yyyy" : $regionalData['date_format'];
				self :: $plural_rules = $regionalData['plural_rules'];
				self :: $translation = $regionalData['translation'];
				self :: $month = $regionalData['month'];
				self :: $month_case = $regionalData['month_case'];
				self :: $week_days = $regionalData['week_days'];
				self :: $decimal_mark = $regionalData['decimal_mark'];
				self :: $js_translation_file = $registry -> getSetting("AdminFolder")."i18n/".$region."/locale.js";
				
				$registry -> setSetting("DecimalMark", $regionalData['decimal_mark']);
				
				if(strpos($regionalData['date_format'], '.') !== false)
					self :: $date_separator = '.';
				if(strpos($regionalData['date_format'], '-') !== false)
					self :: $date_separator = '-';
				if(strpos($regionalData['date_format'], '/') !== false)
					self :: $date_separator = '/';
			}
			
			//Special file for non-english laguages to convert names into Latin
			if(file_exists($region_folder."translit.php"))
			{
				include $region_folder."translit.php";
				
				if(isset($translitRules))
					self :: $translit_rules = $translitRules;
			}
			
			//Additional custom translations if exist 
			$extra = $registry -> getSetting("IncludePath")."customs/i18n/locale-".$region.".php";
			
			if(file_exists($extra))
			{
				include $extra;
				
				if(isset($translations) && count($translations))
					self :: $translation = array_merge(self :: $translation, $translations);
			}
		}
	}
	
	static public function locale($key)
	{
		//Gets language string for lacalization
		if(isset(self :: $translation[$key]) && self :: $translation[$key] != "")
		{
			$string = self :: $translation[$key];
			$string = preg_replace("/'([^']+)'/", "&laquo;$1&raquo;", $string);
			
			$arguments = func_get_args();
			
			if(isset($arguments[1]) && is_array($arguments[1]))
				foreach($arguments[1] as $pattern => $value)
					if(preg_match("/^\*[a-z-_]+$/", $value) && array_key_exists(str_replace('*', '', $value), $arguments[1]))
					{
						$number = $arguments[1][str_replace('*', '', $value)];
						$defined_type = 'other';
						
						foreach(self :: $plural_rules as $type => $re)
							if(is_numeric($number) && preg_match($re, $number) && isset(self :: $translation[$pattern][$type]))
							{
								$defined_type = $type;
								break;
							}
						
						$string = str_replace('['.$pattern.']', self :: $translation[$pattern][$defined_type], $string);						
					}
					else
						$string = str_replace('{'.$pattern.'}', $value, $string);
			
			return $string;
		}
		else
			return "{".$key."_".self :: $region."}"; //If key not found we show the key + lang prefix
	}
	
	static public function formatIntNumber($value)
	{
		return number_format($value, 0, self :: $decimal_mark, " ");
	}
	
	static public function formatFloatNumber($value)
	{
		$arguments = func_get_args();
		$decimals = isset($arguments[1]) ? intval($arguments[1]) : 2;
		
		return number_format($value, $decimals, self :: $decimal_mark, " ");
	}
	
	static public function getWeekDayName($date)
	{
		$day_of_week = date("w", strtotime($date));
		$day_of_week = $day_of_week ? $day_of_week - 1 : 6;
		
		if(isset(self :: $week_days[$day_of_week]))
			return self :: $week_days[$day_of_week];
	}
	
	static public function checkDateFormat($date)
	{
		$re = "/^".str_replace(array("d","m","y",".","/"), array("\d","\d","\d","\.","\/"), self :: $date_format);
		
		$arguments = func_get_args();
		$re .= (isset($arguments[1]) && $arguments[1] == "with-time") ? "(\s\d\d:\d\d(:\d\d)?)$/" : "$/";
		
		return preg_match($re, $date);
	}
	
	static public function dateForSQL($date)
	{
		if(!preg_match("/^\d{2,4}(\.|-|\/)\d{2,4}(\.|-|\/)\d{2,4}(\s\d{2}:\d{2}(:\d{2})?)?$/", $date))
			return "";
		
		if(preg_match('/\s\d\d:\d\d(:\d\d)?$/', $date))
		{
			$parts = explode(' ', $date);
			$date_parts = explode(self :: $date_separator, $parts[0]);
			$time = $parts[1];
		}
		else
			$date_parts = explode(self :: $date_separator, $date);
			
		if(count($date_parts) != 3)
			return "";
			
		$positions = array_flip(explode(self :: $date_separator, self :: $date_format));
		
		$result = $date_parts[$positions['yyyy']];
		$result .= '-'.$date_parts[$positions['mm']];
		$result .= '-'.$date_parts[$positions['dd']];
		$result .= isset($time) ? ' '.$time : '';
		
		return $result;
	}
	
	static public function dateFromSQL($date)
	{
		if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2})?(:\d{2})?$/", $date) || 
			preg_match("/^0{4}-0{2}-0{2}(\s0{2}:0{2})?(:0{2})?$/", $date))
			return "";
		
		$parts = explode(' ', $date);
		$parts[0] = explode('-', $parts[0]);
		$date_parts = array('yyyy' => $parts[0][0], 'mm' => $parts[0][1], 'dd' => $parts[0][2]);
		
		$positions = explode(self :: $date_separator, self :: $date_format);
		
		$result = array();
		
		foreach($positions as $key)
			$result[] = $date_parts[$key];
		
		$result = implode(self :: $date_separator, $result);
		
		if(isset($parts[1]))
		{
			$time_parts = explode(":", $parts[1]);

			$arguments = func_get_args();
			
			if(isset($arguments[1]))
				if($arguments[1] == "no-seconds")
					unset($time_parts[2]);
				else if($arguments[1] == "only-date")
					$time_parts = array();
			
			if(count($time_parts))
				$result .= " ".implode(':', $time_parts);
		}
			
		return $result;
	}
	
	static public function formatDate($date)
	{
		$arguments = func_get_args();	
		$format = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
		
		if(!$format || $format == "no-seconds" || $format == "only-date")
			return  self :: dateFromSQL($date, $format);
		else
			return date($format, self :: dateToTimestamp($date));
	}

	static public function getDecimalMark()
	{
		return self :: $decimal_mark;
	}
	
	static public function getDateFormat()
	{
		return self :: $date_format;
	}
	
	static public function getDateTimeFormat()
	{
		return self :: $date_format." hh:mi";
	}
	
	static public function getCurrentDate()
	{
		$arguments = func_get_args();
		$date = date("Y-m-d");
		
		return (isset($arguments[0]) && $arguments[0] == "SQL") ? $date : self :: dateFromSQL($date);
	}
	
	static public function getCurrentDateTime()
	{
		$arguments = func_get_args();
		$date = date("Y-m-d H:i:s");
		
		return (isset($arguments[0]) && $arguments[0] == "SQL") ? $date : self :: dateFromSQL($date);
	}
	
	static public function timestampToDate($timestamp)
	{
		return self :: dateFromSQL(date("Y-m-d H:i:s", $timestamp));	
	}
	
	static public function dateToTimestamp($date)
	{
		if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2})?(:\d{2})?$/", $date))
			return;
		
		$date = explode(" ", $date);
		$day = explode("-", $date[0]);
		$time = isset($date[1]) ? explode(":", $date[1]) : array(0, 0, 0);
		
		return mktime(intval($time[0]), intval($time[1]), intval($time[2]), 
					  intval($day[1]), intval($day[2]), intval($day[0]));
	}
	
	static public function convertFileSize($size)
	{
		//Converts the size of file from bites to KB or MB.
		if($size >= 1048576)
			return round($size / 1048576, 1)." ".I18n :: locale("size-mb");
			
		if($size >= 1024)
			return round($size / 1024)." ".I18n :: locale("size-kb");
		else
			return round($size / 1024, 2)." ".I18n :: locale("size-kb");
	}
	
	static public function getMonth($number)
	{
		if(isset(self :: $month[$number - 1]))
			return self :: $month[$number - 1];
	}
	
	static public function getMonthCase($number)
	{
		if(isset(self :: $month_case[$number - 1]))
			return self :: $month_case[$number - 1];
	}
	
	static public function translitUrl($string)
	{
		$url = mb_strtolower($string, "utf-8");
		
		if(self :: $translit_rules && count(self :: $translit_rules))
			$url = strtr($url, self :: $translit_rules);
		else
			$url = str_replace(" ", "-", $url);
		
		$url = htmlspecialchars_decode($url, ENT_QUOTES);
		$url = str_replace("_", "-", $url);
		$url = preg_replace("/[^a-z0-9-]+/ui", "", $url);		
		$url = preg_replace("/-+/", "-", $url);
		$url = preg_replace("/^-?(.*[^-]+)-?$/", "$1", $url);
			 
		return ($url == "-") ? "" : $url;		
	}
	
	static public function getRegionsOptions()
	{
		$registry = Registry :: instance();
		$values = array();
		$regions = $registry -> getSetting('SupportedRegions');
		
		if(!is_array($regions) || !count($regions))
		{
			$region = $registry -> getSetting('Region');
			$regions = ($region == "en" && $registry -> getSetting('AmericanFix')) ? array("am") : array($region);
		}
		
		foreach($regions as $region)
		{
			if($region == "am")
			{
				$values[$region] = "English (US)";
				continue;
			}
			
			$path = $registry -> getSetting('IncludeAdminPath')."i18n/".$region."/locale.php";
			
			if(!is_file($path))
				continue;
				
			include $path;
			
			$values[$region] = $regionalData["caption"];
		}
		
		return $values;
	}
	
	static public function displayRegionsSelect($active)
	{
		$html = "";
		
		foreach(self :: getRegionsOptions() as $key => $caption)
		{
			$selected = ($active == $key) ? ' selected="selected"' : "";
			$html .= "<option value=\"".$key."\"".$selected.">".$caption."</option>\n";
		}
		
		return $html;
	}
	
	static public function checkRegion($region)
	{
		$registry = Registry :: instance();
		$regions = $registry -> getSetting('SupportedRegions');
		$regions = (is_array($regions) && count($regions)) ? $regions : array($registry -> getSetting('Region'));
		
		return in_array($region, $regions);
	}
	
	static public function defineRegion()
	{
		$registry = Registry :: instance();

		if(isset($_COOKIE["region"]) && $_COOKIE["region"] && self :: checkRegion($_COOKIE["region"]))
			return $_COOKIE["region"];
		else
		{
			$region = $registry -> getSetting('Region');
			return ($region == "en" && $registry -> getSetting('AmericanFix')) ? "am" : $region;
		}
	}
	
	static public function saveRegion($region)
	{
		$registry = Registry :: instance();
		
		if(!self :: checkRegion($region))
			return;

		$http_only = $registry -> getSetting("HttpOnlyCookie");
		$time = time() + 3600 * 24 * 365;
		
		setcookie("region", $region, $time, $registry -> getSetting("AdminPanelPath"), null, null, $http_only);
	}
}
?>