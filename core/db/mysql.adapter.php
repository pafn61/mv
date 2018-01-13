<? 
class Mysql_Db_Adapter extends Db_Adapter
{
	public function runPDO()
	{
		$host = self :: $registry -> getSetting("DbHost");
		$user = self :: $registry -> getSetting("DbUser");
		$pass = self :: $registry -> getSetting("DbPassword");
		$name = self :: $registry -> getSetting("DbName");
		
		$pdo = new PDO("mysql:host=".$host.";dbname=".$name, $user, $pass, 
			            array(PDO :: MYSQL_ATTR_INIT_COMMAND => "SET NAMES \"UTF8\""));
								            
		$pdo -> setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);

		return $pdo;
	}
				
	public function unixTimeStamp($name)
	{
		if($name == 'now')
			return "UNIX_TIMESTAMP(NOW())";
		else
			return "UNIX_TIMESTAMP(`".$name."`)";
	}
		
	public function listTables()
	{
		//Returns the array of all tables of current DB
		$tables = array();
		
		$result = $this -> query("SHOW TABLES");

		while($row = $this -> fetch($result, "NUM"))
			$tables[] = $row[0];

		return $tables;	
	}
	
	public function createTableDump($table)
	{
		//Creates the dump of one single table of DB
		$separator = "\r\n";
		$dump = "";
		
		$result = $this -> query("SHOW CREATE TABLE `".$table."`"); //Query which creates the table
			
		while($row = $this -> fetch($result, "NUM"))
			$dump .= $row[1].";".$separator.$separator; //Saves it in dump
				
		$table = "`".$table."`";
		
		$result = $this -> query("SELECT * FROM ".$table.""); //Takes all rows of db with all fields
		
		while($row = $this -> fetch($result, "ASSOC"))
		{
		    $dump .= "INSERT INTO ".$table." VALUES ("; //Query of insert
			$values = array();
			
			foreach($row as $val)
				$values[] = $this -> secure($val); //Array of values of the row
			
			$dump .= implode(",", $values); //Glues the values into string
		    $dump .= ");".$separator;
		}
		
		return $dump.$separator;
	}
}
?>
