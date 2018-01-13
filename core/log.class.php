<?
/**
 * Logs messages class for admin panel and common system text log.
 * Log files of framework are located at the /log/ folder
 */
class Log extends Model
{
	protected $name = "{users-operations}";
	
	protected $model_elements = array(
				array("{module}", "enum", "module"),
				array("{row_id}", "int", "row_id"),
				array("{date}", "date_time", "date"),
				array("{record}", "char", "name"),
				array("{user}", "enum", "user_id", array("foreign_key" => "Users")),
				array("{operation}", "enum", "operation", array("values_list" => array(
																"create" => "{creating}",
																"update" => "{editing}",
																"delete" => "{deleting}",
																"restore" => "{restoring}"
																)))				
	);
			
	protected $model_display_params = array(		
		
		"hidden_fields" => array('row_id'),
		"create_actions" => false,
		"update_actions" => false,
		"delete_actions" => false
	);
						
	private static $log_file_max_size = 3072000;
	
	private static $max_log_files = 20;
	
	public function __construct()
	{
		$values_list = array();
		$db = Database :: instance();
		$registry = Registry :: instance();
		$values = $db -> getColumn("SELECT DISTINCT `module` FROM `log`");
		
		foreach($values as $model_class)
			if($registry -> checkModel($model_class))
				{	
					$object = new $model_class();
					$values_list[$model_class] = $object -> getName();
				}
		
		natcasesort($values_list);
		
		$this -> model_elements[0][] = array('values_list' => $values_list);
		
		parent :: __construct();
		
		$this -> elements['operation'] -> defineValuesList();
		$this -> elements['user_id'] -> defineValuesList();
	}
	
	static public function write($model, $row_id, $name, $user_id, $operation)
	{
		$db = Database :: instance();
		$db -> query("INSERT INTO `log`(`module`,`row_id`,`name`,`user_id`,`operation`,`date`) 
					  VALUES('".$model."','".$row_id."','".$name."','".$user_id."',
					  		 '".$operation."',".$db -> now('with-seconds').")");
	}
	
	static public function clean($user_id)
	{
		$db = Database :: instance();
		$db -> query("DELETE FROM `log` WHERE `user_id`='".$user_id."'");
	}
	
	public function displayLogsOnIndexPage($user_id, $limit)
	{
		$html = "";
		$models = $this -> elements['module'] -> getProperty("values_list");
		$all_models = $this -> registry -> getSetting("Models");
		$all_models = array_merge($all_models, array("users","garbage"));
		$operations = $this -> elements['operation'] -> getProperty("values_list");
		$users_names = $this -> elements['user_id'] -> getProperty("values_list");
		
		$query = "SELECT * FROM `".$this -> table."` WHERE `user_id`='".$user_id."' ORDER BY `date` DESC LIMIT 0,".$limit;
		$my_logs = $this -> db -> getAll($query);
		$other_logs = $this -> db -> getAll(str_replace("`user_id`=", "`user_id`!=", $query));
		
		$my_logs = array_values($my_logs);
		$other_logs = array_values($other_logs);
		$all_logs = array();
		$limit = count($my_logs) > count($other_logs) ? count($my_logs) : count($other_logs);
		
		if(!$limit)
			return;
		
		$html .= "<tr>\n";
		
		for($i = 0; $i < $limit; $i ++)
		{
			$all_logs[] = isset($my_logs[$i]) ? $my_logs[$i] : array();
			$all_logs[] = isset($other_logs[$i]) ? $other_logs[$i] : array();
		}
		
		$count = 0;
		
		foreach($all_logs as $row)
		{
			if($count == 1 || $count % 2 != 0)
				$html .= "<td class=\"center\"></td>\n";
			else if($count && $count % 2 == 0)
				$html .= "</tr>\n<tr>\n";
						
			if(!count($row))
			{
				$html .= "<td class=\"date empty\"></td>\n<td class=\"name empty\"></td>\n<td class=\"module empty\"></td>\n";
				$count ++;
				continue;
			}
				
			$name = $row['name'] ? $row['name'] : "-";
			$module = isset($models[$row['module']]) ? $models[$row['module']] : "-";
			$operation = (isset($operations[$row['operation']])) ? $operations[$row['operation']] : "-";
			$user = (isset($users_names[$row['user_id']])) ? $users_names[$row['user_id']]."<br /> " : "";
			
			if(!in_array($row["module"], $all_models))
				$module = "";
			else if($module != "-")
			{
				$object = new $row['module']();
				
				if(get_parent_class($object) == "Model_Simple")
					$module = I18n :: locale('simple-module');
				else
				{
					$href = $this -> registry -> getSetting("AdminPanelPath")."model/";
					$href .= "?model=".$row['module'];
						
					$module = I18n :: locale('module')." <a href=\"".$href."\">".$module."</a>\n";
				}
					
				$href = $this -> registry -> getSetting("AdminPanelPath")."model/";
					
				if(get_parent_class($object) == "Model_Simple")
				{
					$href .= "index-simple.php?model=".$row['module'];
					$name = "<a href=\"".$href."\">".$name."</a>\n";
				}
				else if($row['module'] != "garbage" && $object -> getById($row['row_id']))
				{
					$href .= "update.php?model=".$row['module']."&id=".$row['row_id'];
					$name = "<a href=\"".$href."\">".$name."</a>\n";
				}
					
				if($count == 1 || $count % 2 != 0)
				{
					$href = $this -> registry -> getSetting("AdminPanelPath")."model/?model=log&user_id=".$row['user_id'];
					$user = "<a href=\"".$href."\">".$user."</a>\n";
				}
				else
					$user = "";
				
				$module = "<span class=\"module\">\n".$module."</span>";
			}
			
			$html .= "<td class=\"date\">".I18n :: formatDate($row['date'], "no-seconds")."</td>\n";
			$html .= "<td class=\"name\">\n".$name.$module."</td>\n";
			$html .= "<td class=\"operation\">".$user."<span>".$operation."</span></td>\n";
				
			$count ++;
		}
		
		return $html."</tr>\n";
	}

	static public function add($message)
	{
		$registry = Registry :: instance();
		$i18n = I18n :: instance();		
		$message = I18n :: getCurrentDateTime()." ".$message;
		
		$folder = $registry -> getSetting("IncludePath")."log/";
		
		if(!is_dir($folder))
			return;

		$file_name = isset($_SERVER["SERVER_NAME"]) ? strtolower($_SERVER["SERVER_NAME"]) : "errors";
		$file_name = $folder.preg_replace("/^www\./", "", $file_name);
		
		if(is_file($file_name.".log") && filesize($file_name.".log") >= self :: $log_file_max_size)
			for($i = 1; $i < self :: $max_log_files; $i ++)
				if((is_file($file_name.$i.".log") && filesize($file_name.$i.".log") < self :: $log_file_max_size) ||  
					!is_file($file_name.$i.".log"))
				{
					$file_name .= $i;
					break;
				}

		if(is_file($file_name.".log") && filesize($file_name.".log") >= self :: $log_file_max_size)
			return;
		
		$file = $file_name.".log";
		
	   	if($handle = @fopen($file, "at"))
	   	{
	   		$content = file_get_contents($file);	   		
	   		
	   		if(strpos($content, $message) === false)
  				fwrite($handle, $message."\r\n");
  			
   			fclose($handle);
   		}		
	}
}
?>