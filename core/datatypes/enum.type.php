<?
class Enum_Model_Element extends Model_Element
{
	protected $foreign_key = false;
	
	protected $is_parent = false;
	
	protected $values_list = array();
	
	protected $empty_value = true;
	
	protected $name_field = "name";
	
	protected $name_field_extra = false;
	
	protected $display_radio = false;
	
	protected $long_list = false;
	
	protected $show_parent = false;
	
	protected $multiple_choice = false;
	
	protected $order_asc = false;
	
	protected $order_desc = false;
	
	public function getValuesList()
	{
		return $this -> values_list;
	}
	
	public function setLongList($value)
	{
		$this -> long_list = $value;
		return $this;
	}
	
	public function setDisplayRadio($display_radio) 
	{ 
		$this -> display_radio = $display_radio; 
		return $this; 
	}
	
	public function validate()
	{
		if($this -> required && !$this -> value)
			$this -> error = $this -> chooseError("required", "{error-required-enum}");
		else if($this -> value) 
			if(!$this -> multiple_choice)
			{
				if(!$this -> checkValue($this -> value))
					$this -> error = "{error-undefined-value}";
			}
			else
				foreach(explode(",", $this -> value) as $key)
					if(!array_key_exists($key, $this -> values_list))
						$this -> error = "{error-undefined-value}";
		
		return $this;
	}

	public function filterValuesList($params)
	{
		if(!is_array($params) || !count($params))
			return $this;
			
		if(!count($this -> values_list))
			$this -> defineValuesList();
		
		if($this -> foreign_key && !$this -> is_parent)
		{
			$object = new $this -> foreign_key();
			
			$this -> values_list = array();
			
			$query = "SELECT `id`,`".$this -> name_field."` 
					  FROM `".$object -> getTable()."`".Model :: processSQLConditions($params);
			
			$filtered_data = $object -> db -> getAll($query);
			
			foreach($filtered_data as $data)
				$this -> values_list[$data['id']] = $data[$this -> name_field];
		}
			
		return $this;
	}
	
	public function displayHtml()
	{
		if($this -> display_radio)
			return $this -> displayAsRadio();
		
		$options_html = "";
		$data_for_options = $this -> values_list;
		$arguments = func_get_args();
		$form_frontend = (isset($arguments[0]) && $arguments[0] == "frontend");
		
		if($this -> long_list)
		{
			$value = $this -> getValueName($this -> value);
			
			if($this -> is_parent && $this -> show_parent)
				if($parent_name = $this -> getNameOfParentOfForeignKey($this -> value))
					$value .= " (".$parent_name.")";
			
			$html = "<input class=\"autocomplete-input\" type=\"text\" ";
		    $html .= $this -> addHtmlParams()." value=\"".$value."\" />\n";
		    $html .= "<input type=\"hidden\" name=\"".$this -> name."\" value=\"".$this -> value."\" />\n";	

			return $html.$this -> addHelpText();
		}
		
		if($this -> empty_value)
		{
			$empty_text = is_bool($this -> empty_value) ? I18n :: locale("not-defined") : $this -> empty_value;
			$options_html .= "<option value=\"\">".$empty_text."</option>\n";
		}
		else if(!$form_frontend)
			$options_html = "<option value=\"\">".I18n :: locale("select-value")."</option>\n";
			
		if(is_array($data_for_options))
			foreach($data_for_options as $id => $name)
			{
				$selected = ($id == $this -> value) ? " selected=\"selected\"" : "";
				$options_html .= "<option value=\"".$id."\"".$selected.">".$name."</option>\n";
			}

		$css = $this -> addHtmlParams() ? $this -> addHtmlParams() : " class=\"form-single-select\"";
		$html = "<select".$css." name=\"".$this -> name."\">\n".$options_html."</select>\n";
		
		return $html.$this -> addHelpText();
	}
	
	public function displayAsRadio()
	{
		$checked = $this -> value ? $this -> value : "";		
		$html = Service :: displayOrderedFormTable($this -> values_list, $this -> display_radio, $checked, $this -> name, "radio");
		
		return $html.$this -> addHelpText();
	}
	
	public function displayAsCheckboxes()
	{
		$checked = $this -> value ? explode(",", $this -> value) : array();
		$html = Service :: displayOrderedFormTable($this -> values_list, $this -> multiple_choice, $checked, $this -> name);
		
		return $html.$this -> addHelpText();
	}
	
	public function setValuesFromCheckboxes()
	{
		$this -> value = array();
		
		foreach($this -> values_list as $key => $value)
			if(isset($_POST[$this -> name."-".$key]) && $_POST[$this -> name."-".$key] == $key)
				$this -> value[] = $key;
		
		$this -> value = count($this -> value) ?  implode(",", $this -> value) : "";
	}
	
	public function createNameFromNameFields($row, $object)
	{
		$type = ($this -> name_field == "id") ? "id" : $object -> getElement($this -> name_field) -> getType();		
		$name_fields = array($this -> name_field => $type);
		
		if($this -> name_field_extra && is_object($object -> getElement($this -> name_field_extra)))
		{
			$type = ($this -> name_field_extra == "id") ? "id" : $object -> getElement($this -> name_field_extra) -> getType();
			$name_fields[$this -> name_field_extra] = $type;
		}
		
		$name = array();
			
		foreach($name_fields as $field_name => $field_type)
		{
			if($field_type == "enum")
				$name[] = $object -> getElement($field_name) -> getValueName($row[$field_name]);
			else if($field_type == 'date' || $field_type == 'date_time')
				$name[] = I18n :: dateFromSQL($row[$field_name]);
			else if($field_type == 'text')
				$name[] = Service :: cutText(strip_tags($row[$field_name]), 30, " ...");
			else
				$name[] = $row[$field_name];
		}
			
		return implode(" ", $name);
	}
	
	public function getDataOfForeignKey()
	{
		$object = new $this -> foreign_key();
		$foreign_name_field = $object -> getElement($this -> name_field);
		
		if(!is_object($foreign_name_field) && $this -> name_field != "id")
		{
			$message = "You must specify correct name field from model '".$this -> foreign_key;
			$message .= "' for model element with name '".$this -> name."'.";
			
			Debug :: displayError($message);
		}
			
		$type = $this -> name_field == "id" ? "id" : $foreign_name_field -> getType();
		$condition = $order = "";
		
		if($this -> order_asc && $object -> getElement($this -> order_asc))
			$order = " ORDER BY `".$this -> order_asc."` ASC";
		else if($this -> order_desc && $object -> getElement($this -> order_desc))
			$order = " ORDER BY `".$this -> order_desc."` DESC";
		
		if($this -> is_parent) //If this is parent model for other model
		{
			//Leaves only allowed parents which have no children in current model
			$parent_field = $object -> getParentField();
			$condition = "WHERE `id` NOT IN(SELECT DISTINCT `".$parent_field."` FROM `".$object -> getTable()."`) ";
		}
			
		$query = "SELECT `id`,`".$this -> name_field."`".
				 ($this -> name_field_extra ? ",`".$this -> name_field_extra."`" : "").
				 "FROM `".$object -> getTable()."` ".$condition.$order;
				  
		if(!$order)
			$query .= "ORDER BY `".$this -> name_field."` ASC";
				
		$result = $object -> db -> query($query);
		$ids = $names = array();
		
		while($row = $object -> db -> fetch($result, "ASSOC"))
		{
			$ids[] = $row['id'];
			$names[] = $this -> createNameFromNameFields($row, $object);
		}
		
		if(!count($ids))
			return array();
		
		if($this -> is_parent && $this -> show_parent) //If we need to show the parent name of parent of record
		{
			$foreign_key_field = $object -> findElementByProperty('parent_for', $this -> model);
			$parent_field_name = $foreign_key_field -> getName();
			$foreign_key_name_field = $object -> tryToFindNameField();
			
			$parents_ids = $object -> db -> getAll("SELECT `id`,`".$parent_field_name."` 
													FROM `".$object -> getTable()."` 
												    WHERE `id` IN(".implode(",", $ids).")");

			$parents_ids_unique = array();
			
			foreach($parents_ids as $id => $data) //Ids of parents in foreign key model
				if($data[$parent_field_name] && !in_array($data[$parent_field_name], $parents_ids_unique) && $data[$parent_field_name] != -1)
					$parents_ids_unique[] = $data[$parent_field_name];

			if(count($parents_ids_unique)) //Takes names of parents
				$parents_names = $object -> db -> getAll("SELECT `id`,`".$foreign_key_name_field."` 
														  FROM `".$object -> getTable()."` 
				  										  WHERE `id` IN(".implode(",", $parents_ids_unique).")");

			foreach($names as $key => $name)
				if($parents_ids[$ids[$key]][$parent_field_name] == -1)
					$names[$key] .= " (".I18n :: locale('root-catalog').")";
				else if(isset($parents_names[$parents_ids[$ids[$key]][$parent_field_name]][$foreign_key_name_field]))
					$names[$key] .= " (".$parents_names[$parents_ids[$ids[$key]][$parent_field_name]][$foreign_key_name_field].")";
		}
			
		$options = array_combine($ids, $names);
		
		return $options;
	}
		
	public function defineValuesList()
	{
		if($this -> foreign_key && !count($this -> values_list))
		{
			$this -> values_list = $this -> getDataOfForeignKey();
			return $this;
		}
		
		if(!count($this -> values_list))
			return $this;	
		
		$transformed = array();
		
		foreach($this -> values_list as $key => $val)
		{
			$key_ = $key;
			$val_ = $val;
			
			if(strpos($key, "{") !== false || strpos($val, "{") !== false)
			{
				if(preg_match("/^\{.*\}$/", $key))
					$key_ = I18n :: locale(preg_replace("/^\{(.*)\}$/", "$1", $key));
	
				if(preg_match("/^\{.*\}$/", $val))
					$val_ = I18n :: locale(preg_replace("/^\{(.*)\}$/", "$1", $val));
			}
			
			$transformed[$key_] = $val_;
		}
		
		$this -> values_list = $transformed;
		
		return $this;
	}
	
	public function checkValue($value)
	{
		if(!$value)
			return (bool) $this -> empty_value;
		else if($this -> foreign_key && !is_numeric($value))
			return false;
		
		if($this -> long_list && $this -> foreign_key) //Long list option process
		{			
			$value = intval($value);
			$object = new $this -> foreign_key();
			
			//Checks if record really exists
			$check_value = (bool) $object -> db -> getCount($object -> getTable(), "`id`='".$value."'");
			
			if(!$this -> is_parent || !$check_value)
				return $check_value; //If is not linked tree or record not exusts
			
			//We take parent field of foreign key model and check if our record has child recorda in parent model 
			$foreign_key_field = $object -> findElementByProperty('parent_for', $this -> model);
			$check_value = $object -> db -> getCount($object -> getTable(), "`".$foreign_key_field -> getName()."`='".$value."'");
			
			return $check_value ? false : true; //If record has children we can not use it
		}
		else //If no long list we load full list op allowed options
			if(!count($this -> values_list) && $this -> foreign_key)
				$this -> values_list = $this -> getDataOfForeignKey();
		
		return (array_key_exists($value, $this -> values_list));
	}
	
	public function getDataForMultiAction()
	{
		$options_xml = "<value id=\"\">".I18n :: locale("select-value")."</value>\n";
		
		if($this -> foreign_key && !$this -> values_list)
			$data_for_options = $this -> getDataOfForeignKey();
		else
			$data_for_options = $this -> values_list;
			
		if(!$this -> required && $this -> empty_value)
		{
			$empty_text = is_bool($this -> empty_value) ? I18n :: locale("not-defined") : $this -> empty_value;
			$options_xml .= "<value id=\"0\">".$empty_text."</value>\n";
		}
		
		if(is_array($data_for_options))
			foreach($data_for_options as $id => $name)
				$options_xml .= "<value id=\"".$id."\">".$name."</value>\n";
		
		return $options_xml;
	}
	
	public function getDataForAutocomplete($request, $db)
	{
		$result_rows = array();
		$request_like = str_replace("%", "[%]", $request);
		$request_like = $db -> secure("%".$request_like."%");
		
		if($this -> foreign_key && !$this -> is_parent) //If its regular enum field with foreign key
		{
			$object = new $this -> foreign_key();
			$foreign_name_field = $object -> getElement($this -> name_field);
			$foreign_name_field_extra = $object -> getElement($this -> name_field_extra);
			
			if($foreign_name_field -> getType() == "enum" || (is_object($foreign_name_field_extra) && 
			   $foreign_name_field_extra -> getType() == "enum")) //If we search in enum fields
			{
				$request_re = Service :: prepareRegularExpression($request);
				
				foreach($this -> defineValuesList() -> values_list as $key => $value)
					if(preg_match("/".$request_re."/ui", $value))
						$result_rows[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
			}
			else //Regular search in text fields
			{
				$query = "SELECT `id`,`".$this -> name_field."` 
						  FROM `".$object -> getTable()."`";
				
				//If we have complex name field with other field of model
				$query = $this -> processNameFields($query);
				
				$query .= " WHERE `".$this -> name_field."` LIKE ".$request_like; //Search condition
				
				if($this -> name_field_extra) //Extra condition if complex name field
					$query .= " OR `".$this -> name_field_extra."` LIKE ".$request_like;
				
				$query .= " ORDER BY `".$this -> name_field."` ASC LIMIT 10";
				
				$rows = $db -> getAll($query);
				
				foreach($rows as $row) //Collects suggestions
					$result_rows[$row['id']] = htmlspecialchars_decode($row[$this -> name_field], ENT_QUOTES);
			}
		}
		else //If its just values list
		{
			$this -> defineValuesList();
			$request_re = Service :: prepareRegularExpression($request);
			
			if(count($this -> values_list) <= 10) //If short list we give it all back
				$result_rows = $this -> values_list;
			else
				foreach($this -> values_list as $key => $value) //String search in list
					if(preg_match("/".$request_re."/ui", $value))
						$result_rows[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
		}
		
		return array('query' => $request,
					 'suggestions' => array_values($result_rows),
					 'data' => array_keys($result_rows));
	}
	
	public function getValueName($key)
	{
		//If we need to get the enum value by key at the frontend
		if($this -> foreign_key)
		{
			$object = new $this -> foreign_key();

			$row = $object -> db -> getRow("SELECT `".$this -> name_field."`".
					  					   ($this -> name_field_extra ? ",`".$this -> name_field_extra."`" : "")."
										   FROM `".$object -> getTable()."` 
										   WHERE `id`='".$key."'");

			return $this -> createNameFromNameFields($row, $object);
		}
		else
			if(isset($this -> values_list[$key]))
				return $this -> values_list[$key];
	}
	
	public function getNameOfParentOfForeignKey($id)
	{	
		$object = new $this -> foreign_key();		
		$foreign_key_object = $object -> findElementByProperty('parent_for', $this -> model);
		$parent_field_name = $foreign_key_object -> getName();
		$foreign_key_name_field = $object -> tryToFindNameField();
		
		$parent_id = $object -> db -> getCell("SELECT `".$parent_field_name."` 
											   FROM `".$object -> getTable()."`
											   WHERE `id`='".$id."'");
		
		if($parent_id == -1)
			return I18n :: locale('root-catalog');
		else if($parent_id)
			return $object -> db -> getCell("SELECT `".$foreign_key_name_field."`
											 FROM `".$object -> getTable()."`
											 WHERE `id`='".$parent_id."'");
	}
	
	public function setEmptyValueTitle($title)
	{
		$this -> empty_value = trim($title);
	}
	
	public function processNameFields($query)
	{
		if($this -> name_field_extra)
		{
			$registry = Registry :: instance();
			
			if($registry -> getSetting("DbEngine") == "sqlite")
				$replace = " (`".$this -> name_field."` || ' ' || `".$this -> name_field_extra."`) AS `".$this -> name_field."`";
			else
				$replace = "CONCAT_WS(' ', `".$this -> name_field."`, `".$this -> name_field_extra."`) AS `".$this -> name_field."`";
			
			$query = str_replace("`".$this -> name_field."`", $replace, $query);
		}						  
		
		return $query;
	}
	
	public function getKeyUsingName($name)
	{
		if(!$name) return;

		if(!$this -> foreign_key)
		{
			if(in_array($name, $this -> values_list))
				return array_search($name, $this -> values_list);
		}
		else
		{
			$db = Database :: instance();
	
			return $db -> getCell("SELECT `id`
					  			   FROM `".strtolower($this -> foreign_key)."` 
					  			   WHERE `".$this -> name_field."`=".$db -> secure($name));
		}
	}
}
?>