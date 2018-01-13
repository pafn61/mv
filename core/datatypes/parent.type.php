<? 
class Parent_Model_Element extends Enum_Model_Element
{
	protected $self_id;
	
	protected $self_model;
	
	protected $parent_for;
	
	protected $child_records = array();
	
	protected $name_field = "name";
	
	protected $max_depth;
	
	protected $show_parent = false;
	
	public function getChildRecords()
	{
		return $this -> child_records;
	}
	
	public function setSelfModel($model_name)
	{
		$this -> self_model = $model_name;
		
		return $this;
	}
		
	public function setSelfId($id)
	{
		$this -> self_id = $id;
		
		return $this;
	}
	
	public function getAvailbleParents($table)
	{
		$registry = Registry :: instance();
		$db = DataBase :: instance();
		$arguments = func_get_args();
		$for_frontend_filter = false;
		$order_by = "`".$this -> name_field."` ASC";
		
		if(isset($arguments[1]) && $arguments[1])
		{
			$for_frontend_filter = true; //Inintial parents for Filter, we dont use max depth
			
			if($arguments[1] != "for-frontend-filter")
				$order_by = $arguments[1]; //Ordering by filterValuesList() of Filter
		}
				
		$where = "";
		
		if($this -> self_id) //If we have direct child rows in db
		{
			$this -> defineChildElements($table, $this -> self_id);
			
			$denied_ids = $this -> child_records; //Ids which we can't use as parents for current element
			$denied_ids[] = $this -> self_id;
			
			$where = " WHERE `id` NOT IN(".implode(',', $denied_ids).")";
		}
				
		if($this -> parent_for && !$for_frontend_filter) //If this is parent model for other model
		{
			$object = new $this -> parent_for(); //Object of other model
			$parent_field = $object -> getNameOfForeignKeyField($this -> self_model); //Searches the field name

			$forbidden_ids = $db -> getColumn("SELECT DISTINCT `".$parent_field."`  
											   FROM `".$object -> getTable()."`  
							 				   WHERE `".$parent_field."`!='-1' 
							 				   AND `".$parent_field."`!=''");

			if(count($forbidden_ids))
			{
				foreach($forbidden_ids as $key => $value)
					$forbidden_ids[$key] = intval($value);
				
				$where .= $where ? " AND " : " WHERE ";
				$where .= "`id` NOT IN(".implode(',', $forbidden_ids).")";
			}
		}
		
		$query = "SELECT `id`,`".$this -> name_field."` 
				  FROM `".$table."`".$where." 
				  ORDER BY ".$order_by;
		
		$result = $db -> query($query);
		
		$ids = $names = array(); //To collect the values
		
		while($row = $db -> fetch($result, "ASSOC"))
		{
			$ids[] = $row['id'];
			$names[] = $row[$this -> name_field];
		}
		
		//Adds root directory
		array_unshift($names, I18n :: locale('root-catalog'));
		array_unshift($ids, '-1');
		
		$this -> values_list = array_combine($ids, $names);
		
		if($this -> max_depth && !$for_frontend_filter) //To show only elements with allowed depth in the tree
		{
			$start_parents = $allowed_ids = array(-1); //Root catalog
			$max_depth = intval($this -> max_depth);
			
			if($max_depth -- > 1)
				do
				{
					$query = "SELECT `id` FROM `".$table."` WHERE `".$this -> name."` 
							  IN(".implode(',', $start_parents).")";
					
					$start_parents = $db -> getColumn($query);
				
					if(is_array($start_parents)) //Collects allowed parents ids
						$allowed_ids = array_merge($allowed_ids, $start_parents);
				}
				while(-- $max_depth && is_array($start_parents) && count($start_parents));
			
			foreach($this -> values_list as $id => $name)
				if(!in_array($id, $allowed_ids))
					unset($this -> values_list[$id]); //Deletes forbidden ids
		}
		
		if($this -> show_parent) //Adds parent name of parent
		{
			$ids = $parents = $parents_ids = array();
			
			foreach($this -> values_list as $id => $name)
				if($id != -1 && !in_array($id, $ids))
					$ids[] = $id;
			
			if(count($ids))
				$parents = $db -> getAll("SELECT `id`,`".$this -> name."` 
										  FROM `".$table."` 
										  WHERE `id` IN(".implode(",", $ids).")");
			
			foreach($parents as $id => $data)
				if($data[$this -> name] && $data[$this -> name] != -1 && !in_array($data[$this -> name], $parents_ids))
					$parents_ids[] = $data[$this -> name];
			
			if(count($parents_ids))
				$parents_names = $db -> getAll("SELECT `id`,`".$this -> name_field."` 
											    FROM `".$table."` 
				  								WHERE `id` IN(".implode(",", $parents_ids).")");
			
			foreach($this -> values_list as $id => $name)
				if($id == -1)
					continue;
				else if($parents[$id][$this -> name] == -1)
					$this -> values_list[$id] .= " (".I18n :: locale('root-catalog').")";
				else if(isset($parents_names[$parents[$id][$this -> name]][$this -> name_field]))
					$this -> values_list[$id] .= " (".$parents_names[$parents[$id][$this -> name]][$this -> name_field].")";
		}
		
		return $this;
	}
	
	public function checkAllowedDepth($start_id)
	{
		$arguments = func_get_args();
		
		//Check for child element from different model
		if($this -> parent_for && isset($arguments[1]) && class_exists($arguments[1]))
		{
			$related_object = new $this -> parent_for(); //Other model object
			
			//Needed parent field
			$related_object_element = $related_object -> findElementByProperty('foreign_key', $arguments[1]);
			
			if(!$related_object_element -> getProperty('is_parent')) //One more check that its the needed element
				return;
							
			//Sql to check child elements
			$condition = "`".$related_object_element -> getName()."`='".$start_id."'";
			$related_children = (bool) $related_object -> db -> getCount($related_object -> getTable(), $condition);
			
			if($this -> max_depth && !$related_children)
				return count($this -> displayPath($start_id)) < $this -> max_depth;
			else
				return !$related_children;
		}
		
		if(!$this -> max_depth) //If max deth is not defined we can go any deeper
			return true;
		
		return count($this -> displayPath($start_id)) < $this -> max_depth; //Counts parents and compares with max depth
	}
	
	public function displayPath($start_id)
	{
		$registry = Registry :: instance();
		$db = DataBase :: instance();
		$table = strtolower($this -> self_model);
		
		$names = $ids = array();
		
		do
		{
			$row = $db -> getRow("SELECT `id`,`".$this -> name_field."`,`".$this -> name."` 
								  FROM `".$table."` 
								  WHERE `id`='".$start_id."'");
			
			if(!$row) break;
			
			array_unshift($ids, $row['id']);
			array_unshift($names, $row[$this -> name_field]);
			 
			$start_id = $row[$this -> name];
		}
		while($row[$this -> name] != -1);
		
		if(count($names) && count($ids))
			return array_combine($ids, $names);
	}
	
	public function countChildElements($model_name, $field, $id)
	{
		$object = new $model_name();
		
		//Checks the child elements in self model table
		$number = $object -> db -> getCount($object -> getTable(), "`".$field."`='".$id."'");
		
		if($number) //If founded direct children in current model
			return $number." (".$object -> getName().")";
		
		//And we need to check if this model is parent for other model
		//So we should find the needed types of fields in current model and related model if it exists	
		
		$element = $object -> getElement($field); //Object of parent type
		$related_model = $element -> getProperty('parent_for'); //Name of releted model 
		
		if($related_model)
		{
			$related_object = new $related_model(); //Object of related model

			//Model element of parent type which shows parent (value from first model)
			$related_object_element = $related_object -> findElementByProperty('foreign_key', $model_name);
			
			if(!$related_object_element -> getProperty('is_parent')) //One more check that it's the needed element
				return;
			
			$field = $related_object_element -> getName(); //Name of other parent field
			$number = $object -> db -> getCount($related_object -> getTable(), "`".$field."`='".$id."'");
			
			if($number) //If we found children of our parent we show the number of them and link to this model with filter
			{
				$html = "<a class=\"to-children\" href=\"?model=".strtolower($related_model)."&".$related_object_element -> getName();
				$html .= "=".$id."\">".$number." (".$related_object -> getName().")</a>";
				 
				return $html;
			}
		}
	}
	
	public function defineChildElements($table, $id)
	{
		$registry = Registry :: instance();
		$db = DataBase :: instance();
		
		$ids = $db -> getColumn("SELECT `id` FROM `".$table."` WHERE `".$this -> name."`='".$id."'");
		
		foreach($ids as $id)
			if($id && !in_array($id, $this -> child_records)) //Adds new child record
			{
				$this -> child_records[] = $id;
				$this -> defineChildElements($table, $id); //Goes deeper in tree
			}
			
		return $this -> child_records;
	}
	
	public function getNameOfParent($id)
	{
		return isset($this -> values_list[$id]) ? $this -> values_list[$id] : '-';
	}
	
	public function checkValue($value)
	{
		$table = strtolower($this -> model);
		
		if(!is_numeric($value))
			return false;
		else if($value == -1)
			return true;
		else if(!$value)
			return (bool) $this -> empty_value;
		
		if($this -> long_list)
		{		
			$value = intval($value);	
			$db = Database :: instance();
			return (bool) $db -> getCount($table, "`id`='".$value."'");
		}
		else
			if(!count($this -> values_list))
				$this -> getAvailbleParents($table);			
		
		return (array_key_exists($value, $this -> values_list));		
	}
	
	public function getValueName($key)
	{
		$table = strtolower($this -> model);
		$key = intval($key);
		
		if($key == -1)
			return I18n :: locale('root-catalog');
		else if($this -> long_list)
		{
			$db = Database :: instance();

			$query = "SELECT `".$this -> name_field."` 
					  FROM `".$table."` 
					  WHERE `id`='".$key."'";

			$query = $this -> processNameFields($query);

			return $db -> getCell($query);
		}
		else
			if(isset($this -> values_list[$key]))
				return $this -> values_list[$key];
	}
		
	public function getParentsForFilter($table)
	{
		$db = DataBase :: instance();
		
		//Takes all rows qhich have any child rows
		$result = $db -> query("SELECT `id`,`".$this -> name_field."` 
								FROM `".$table."` WHERE `id` IN(
								SELECT DISTINCT `".$this -> name."`
								FROM `".$table."`)");
		
		$ids = $names = array(); //To collect the values for select tag
		
		while($row = $db -> fetch($result, "ASSOC"))
		{
			$ids[] = $row['id'];
			$names[] = $row[$this -> name_field];
		}
		
		//Adds root directory
		array_unshift($names, I18n :: locale('not-defined'), I18n :: locale('all-catalogs'), I18n :: locale('root-catalog'));
		array_unshift($ids, '', 'all', '-1');
				
		return array_combine($ids, $names);
	}
	
	public function getParentsForOneRecord($table, $id)
	{
		$db = DataBase :: instance();
		$parents = array();
		
		do
		{
			$row = $db -> getRow("SELECT `id`, `".$this -> name."` FROM `".$table."` WHERE `id`='".$id."'");
			
			if(!$row) break;
			
			$parents[] = $row[$this -> name];
			$id = $row[$this -> name];
		}
		while($row[$this -> name] != -1);
		
		return $parents;
	}

	public function getDataForAutocomplete($request, $db)
	{
		$request_re = Service :: prepareRegularExpression($request);
		$result_rows = array();
		$count = 0;
		
		foreach($this -> values_list as $key => $value)
			if(preg_match("/".$request_re."/ui", $value))
				$result_rows[$key] = htmlspecialchars_decode($value, ENT_QUOTES);

		return array('query' => $request,  
					 'suggestions' => array_values($result_rows),
					 'data' => array_keys($result_rows));
	}
	
	public function filterValuesList($params)
	{
		$table = func_get_arg(1);
		$order_by = false;
		
		if(array_key_exists("order->asc", $params) || array_key_exists("order->desc", $params))
		{
			if(array_key_exists("order->asc", $params))
				$order_by = "`".$params["order->asc"]."` ASC";			
			else
				$order_by = "`".$params["order->desc"]."` DESC";
				
			unset($params["order->asc"], $params["order->desc"]);
		}
		
		$keeps_max_depth = $this -> max_depth;
		$this -> max_depth = null;
		$this -> getAvailbleParents($table, $order_by);
		$this -> max_depth = $keeps_max_depth;
		
		if(!is_array($params) || !count($params) || !count($this -> values_list))
			return $this;
		
		$db = Database :: instance();
		
		$found_ids = $db -> getColumn("SELECT `id` FROM `".$table."`".Model :: processSQLConditions($params));
		
		foreach($this -> values_list as $id => $name)
			if(!in_array($id, $found_ids))
				unset($this -> values_list[$id]);
		
		return $this;
	}
	
	public function getKeyUsingName($name)
	{
		$db = Database :: instance();
		
		if($name == I18n :: locale('root-catalog'))
			return -1;
		else if($name)
			return $db -> getCell("SELECT `id`
					  			   FROM `".strtolower($this -> model)."` 
					  			   WHERE `".$this -> name_field."`=".$db -> secure($name));
	}
}
?>