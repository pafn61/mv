<?
/**
 * Class contains all methos related to database.
 * Uses PDO driver to deal with databases.
 * Works on singleton pattern.
 */
class Database
{
	private static $instance;
	
	public static $pdo;
	
	private static $registry;
	
	private static $adapter;
	
	public static $total;
	
	public static $engine;
	
	private function __construct() {}
	
	public function __call($method, $params)
	{
		$param = isset($params[0]) ? $params[0] : null;
		
		if(method_exists(self :: $adapter, $method))
			return self :: $adapter -> $method($param);
		else
			Debug :: displayError("You must define method '".$method."' in ".get_class(self :: $adapter)." class.");
	}
	
	static public function instance()
	{
		//Establish the connection with DB form config
		if(!isset(self :: $instance))
		{
			self :: $instance = new self();			
			self :: $registry = Registry :: instance();
			
			$db_engine = self :: $registry -> getSetting("DbEngine");
			
			if(!in_array($db_engine, PDO :: getAvailableDrivers()))
			{
				$message = "The PDO driver for database '".$db_engine."' is not available. ";
				$message .= "Please install the needed PDO driver or check the 'DbEngine' config setting.";
				Debug :: displayError($message);
			}
			 
			self :: $engine = $db_engine;
			$base_db_adapter = self :: $registry -> getSetting("IncludePath")."core/db/base.adapter.php";
			$db_adapter_file = self :: $registry -> getSetting("IncludePath")."core/db/".$db_engine.".adapter.php";
			
			if(file_exists($db_adapter_file))
			{
				require_once $base_db_adapter;
				require_once $db_adapter_file;
				
				$db_adapter_class = ucfirst($db_engine."_Db_Adapter");
				self :: $adapter = new $db_adapter_class();
			}
			else
				Debug :: displayError("Database adapter class needed ~/core/db/".$db_engine.".adapter.php"); 
			
			try
			{
				self :: $pdo = self :: $adapter -> runPDO();
			}
			catch(PDOException $error)
			{
				if(self :: $registry -> getSetting("Mode") == "development")
				{
					if(!headers_sent())
						Debug :: displayError($error -> getMessage());
					else
						Debug :: pre($error);
				}
				else
				{
					self :: $registry -> setSetting("ErrorAlreadyLogged", true);
					Log :: add($error -> getMessage()." \r\n".$error -> getTraceAsString());
				}
				
				exit();
			}
						            
			self :: $pdo -> setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
			
			if(self :: $engine == "mysql")
				self :: $pdo -> query("SET `sql_mode`='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
			
			//Sets local time zone if defined
			if(self :: $engine == "mysql" && $time_zone = self :: $registry -> getSetting('TimeZone'))
			{
				date_default_timezone_set($time_zone);
				self :: $pdo -> query("SET `time_zone`='".date('P')."'");
			}
		}
			
		return self :: $instance;
	}
	
	static public function close()
	{
		self :: $pdo = self :: $engine = self :: $instance = self :: $total = self :: $adapter = self :: $registry = null;
	}
	
	static public function secure($value)
	{
		if(self :: $registry -> getSetting("DbEngine") == "sqlite")
			$value = preg_replace("/\n|\t|\r/", "", $value); //To make LIKE operartor work normaly
		
		return self :: $pdo -> quote($value);
	}
	
	public function beginTransaction()
	{
		self :: $pdo -> beginTransaction();
	}
	
	public function commitTransaction()
	{
		self :: $pdo -> commit();
	}

	public function query($query)
	{
		//Executes one  SQL query and puts it into the total for counting later
		self :: $total .= "-*//*-".$query;
		
		try
		{
			$result = self :: $pdo -> query($query);
			
			if($result)
				return $result;
		}
		catch(PDOException $error)
		{
			if(self :: $registry -> getSetting("Mode") == "development")
			{
				if(!headers_sent())
					Debug :: displayError($error -> getMessage());
				else
					Debug :: pre($error);
			}
			else
			{
				self :: $registry -> setSetting("ErrorAlreadyLogged", true);
				$trace = $error -> getTrace();
				Log :: add($error -> getMessage()."\r\n".$trace[0]["args"][0]." \r\n".$error -> getTraceAsString());
			}
			
			exit();		
		}
	}
	
	static public function fetch($result)
	{
		//Adopts result of SQL query to get the data from it.
		$arguments = func_get_args();
		
		if(isset($arguments[1]) && $arguments[1] == "NUM")
			return $result -> fetch(PDO :: FETCH_NUM);
		else
			return $result -> fetch(PDO :: FETCH_ASSOC);
	}
	
	static public function lastId()
	{
		//Gets the Id in table created form last SQL insert query operation
		return self :: $pdo -> lastInsertId();
	}
	
	public function getCell($query)
	{
		//Gets one cell from table row
		return $this -> query($query) -> fetchColumn();
	}
	
	public function getRow($query)
	{
		//Gets one row from table
		return $this -> query($query) -> fetch(PDO :: FETCH_ASSOC);
	}
	
	public function getColumn($query)
	{
		//Gets one column from table
		$result = $this -> query($query);
		$mass = array();
		
		while($row = $this -> fetch($result, "NUM"))
			$mass[] = $row[0];

		return $mass;
	}

	public function getAll($query)
	{
		//Get one or more rows from table and put them in array with ids as keys
		$mass = array();
		$result = $this -> query($query);

		while($row = $this -> fetch($result, "ASSOC"))
			if(isset($row['id']))
				$mass[$row['id']] = $row;
			else
				$mass[] = $row;

		return $mass;
	}
	
	public function getCount($table)
	{
		//Count the number of rows in SQL query result with parameters but not query as parameter
		$query = "SELECT COUNT(*) FROM `".$table."`";
		
		$params = func_get_args();

		if(isset($params[1]) && $params[1]) //If we have the params we add them to query
			$query .= " WHERE ".$params[1];
			
		$result = $this -> getCell($query);
		
		return $result ? $result : 0;
	}

	public function unixTimeStampDiff($from, $to)
	{
		return "(".$this -> unixTimeStamp($from)." - ".$this -> unixTimeStamp($to).")";
	}
}
?>