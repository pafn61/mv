<?
class Cache
{
	//Key of cache fragment when we cache mix of php and html
	private $current_key;
	
	//Tbale with completed html fragments
	static private $content_table = "cache";
	
	//Table which contains links for dropping old cache data 
	static private $clean_table = "cache_clean";

	public function __construct()
	{
      	$this -> db = DataBase :: instance();
      	$this -> registry = Registry :: instance();
	}
	
	static public function checkIfEnabled()
	{
		$registry = Registry :: instance();
		return $registry -> getSetting("EnableCache");
	}
	
	public function save($key, $content)
	{
		if(!self :: checkIfEnabled())
			return $content;
		
		if(!preg_match("/[A-z]/", $key))
			Debug :: displayError("Key '".$key."' of cache must contain letters.");
		
		$key = str_replace("'", "", $key);
		
		if(is_object($content) || is_array($content))
			return;
		
		//Prepearing for caching
		$arguments = func_get_args();
		$models = (isset($arguments[2]) && is_array($arguments[2])) ? $arguments[2] : array();
		
		$registry = Registry :: instance();
		$checked_models = array();

		foreach($models as $value) //Checks if passed mkdels names are valid
			if($registry -> checkModel($value))
				$checked_models[] = $value;
				
		if(!count($checked_models))
			$checked_models[] = "*"; //If this cache deletes by any action in admin panel
				
		$this -> db -> beginTransaction();

		$this -> cleanByKey($key); //Deletes old cache with this key

		$this -> db -> query("INSERT INTO `".self :: $content_table."` (`key`,`content`)
							  VALUES(".$this -> db -> secure($key).",".$this -> db -> secure($content).")");
		
		foreach($checked_models as $model) //Adds links for cache cleaning
			$this -> db -> query("INSERT INTO `".self :: $clean_table."` (`key`,`model`)
								  VALUES(".$this -> db -> secure($key).",".$this -> db -> secure($model).")");
				
		$this -> db -> commitTransaction();
		
		return $content;
	}
	
	public function saveAndDisplay($key, $content)
	{
		$arguments = func_get_args();
		$models = (isset($arguments[2]) && is_array($arguments[2])) ? $arguments[2] : array();
		
		$this -> save($key, $content, $models);
		
		echo $content;
	}
	
	public function find($key)
	{
		if(!self :: checkIfEnabled())
			return;
		
		return $this -> db -> getCell("SELECT `content` FROM `".self :: $content_table."` WHERE `key`=".$this -> db -> secure($key));
	}
	
	public function findAndDisplay($key)
	{
		if($content = $this -> find($key))
			echo $content;
		
		return $content ? true : false;
	}
	
	static public function cleanByKey($key)
	{
		if(!self :: checkIfEnabled())
			return;
		
		$db = DataBase :: instance();
		
		$db -> query("DELETE FROM `".self :: $content_table."` WHERE `key`=".$db -> secure($key));
		
		if($db -> getCount(self :: $clean_table, "`key`=".$db -> secure($key)))
			$db -> query("DELETE FROM `".self :: $clean_table."` WHERE `key`=".$db -> secure($key));
	}
	
	static public function cleanByModel($model)
	{		
		if(!self :: checkIfEnabled())
			return;
		
		$db = DataBase :: instance();
		
		$keys = $db -> getColumn("SELECT `key` FROM `".self :: $clean_table."` WHERE `model`='*' OR `model`='".$model."'");

		if(count($keys)) //If we have any cache to delete by this model
		{
			$keys = "'".implode("', '", $keys)."'";
			$db -> query("DELETE FROM `".self :: $content_table."` WHERE `key` IN(".$keys.")");
			$db -> query("DELETE FROM `".self :: $clean_table."` WHERE `key` IN(".$keys.")");
		}
	}
	
	static public function cleanAll()
	{
		if(!self :: checkIfEnabled())
			return;
		
		$db = DataBase :: instance();
		$registry = Registry :: instance();
		
		if($registry -> getSetting("DbEngine") == "sqlite")
		{
			$db -> query("DELETE FROM `".self :: $content_table."`");
			$db -> query("DELETE FROM `".self :: $clean_table."`");
		}
		else
		{
			$db -> query("TRUNCATE `".self :: $content_table."`");
			$db -> query("TRUNCATE `".self :: $clean_table."`");
		}
	}
		
	public function displayOrStart($key)
	{
		if($content = $this -> find($key)) //We found cache by current key
		{
			echo $content;
			return true;
		}
		else //We have no cache with current key so we start the caching process
		{
			$this -> current_key = $key; //Saves the key name
			ob_start();
			return false;
		}
	}

	public function stopAndSave()
	{
		if(!$this -> current_key)
			return;
		
		$content = ob_get_flush(); //Getting cache data
		ob_clean();
						
		$arguments = func_get_args();
		$models = (isset($arguments[0]) && is_array($arguments[0])) ? $arguments[0] : array();
		
		if($content) //Save data if it's not empty
			$this -> save($this -> current_key, $content, $models);
		
		$this -> current_key = null;
	}	
}
?>