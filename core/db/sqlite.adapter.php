<?
class Sqlite_Db_Adapter extends Db_Adapter
{
	public function runPDO()
	{
		$file = self :: $registry -> getSetting("IncludePath")."userfiles/database/sqlite/";
		$file .= self :: $registry -> getSetting("DbFile");
		
		if(file_exists($file))
			$pdo = new PDO("sqlite:".$file);
		else 
			Debug :: displayError("SQLite database file ~/userfiles/database/sqlite/".self :: $registry -> getSetting("DbFile")." not found.");

		if(!function_exists("great_fix_of_utf8_sqlite"))
		{
			function great_fix_of_utf8_sqlite($mask, $value) //Fix utf-8 case bug when using LIKE
			{
			    $mask = str_replace(array("%", "_"), array(".*?", "."), preg_quote($mask, "/"));
			    $mask = "/^".$mask."$/ui";
			    return preg_match($mask, $value);
			}
		}		
	
		$pdo -> sqliteCreateFunction("like", "great_fix_of_utf8_sqlite", 2);
		
		if(!function_exists("my_sqlite_regexp"))
		{
			function my_sqlite_regexp($regexp, $value)
			{
    			return (int) preg_match("/".$regexp."/", $value);
			}
		}
		
		$pdo -> sqliteCreateFunction("regexp", "my_sqlite_regexp", 2);
		
		return $pdo;
	}
		
	public function unixTimeStamp($name)
	{
		if($name == 'now')
			return "strftime('%s', 'now')";
		else
			return "strftime('%s', `".$name."`)";
	}

	//Returns the array of all tables of current DB
	public function listTables()
	{
		$tables = array();		
		$result = $this -> query("SELECT name FROM sqlite_master WHERE type='table'");
			
		while($row = $this -> fetch($result, "NUM"))
			$tables[] = $row[0];

		return $tables;	
	} 
	
	//Creates the dump of one single table of DB
	public function createTableDump($table)
	{
		$separator = "\r\n";
		$dump = "";
		
		if($table == "sqlite_sequence") return "";
			
		$dump .= $this -> getCell("SELECT `sql` FROM sqlite_master 
								   WHERE type='table' AND `name`='".$table."'");
		
		$dump .= ";".$separator.$separator;
			$table = "\"".$table."\"";
		
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
