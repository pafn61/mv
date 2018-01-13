<?
/**
 * Main class for recods management (similar to Active Record).
 * Record does not extend the methods of model, only own methods.
 * This class is used mostly in frontend of project.
 * Contains CRUD methods and base methods for passing the content for records.
 */
class Record extends Content
{	
	public function __construct($data, $model)
	{
		$this -> model = $model;
			
		parent :: __construct($data);
	}
	
	public function __get($key)
	{
		if(isset($this -> content[$key]))
			return $this -> content[$key];
	}
	
	public function __set($key, $value)
	{
		if($key != "id" && $object = $this -> model -> getElement($key))		
			$this -> content[$key] = trim($value);
	}
	
	public function getValues()
	{
		return $this -> content;
	}
			
	public function setValues($values)
	{
		foreach($values as $field => $value)			
			if($this -> model -> getElement($field))
				$this -> content[$field] = trim($value);
				
		return $this;
	}
	
	public function prepareContentValues()
	{
		//Gets values of fields ready for creat/update action with record
		$prepared_values = array();
		
		foreach($this -> content as $field => $value)
		{
			if($field == "id") //Id is not processed
			{
				$prepared_values["id"] = intval($value);
				continue;
			}	
			
			$object = $this -> model -> getElement($field); //Object of field
			
			if(!$object)
			{
				unset($this -> content[$field]);
		 		continue;
			}
			
			$type = $object -> getType();
			$value = trim($value);
			
			if($type == "bool")
				$prepared_values[$field] = $value ? 1 : 0;
			else if($type == "date" || $type == "date_time")
			{
				if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}(:\d{2})?)?$/", $value))
					$prepared_values[$field] = I18n :: dateForSQL($value);
				else
					$prepared_values[$field] = $value;
			}
			else if($type == "order")
			{
				if($value)
				{
					$prepared_values[$field] = intval($value);
					continue;
				}

				$object = $this -> model -> getElement($field);
				
				if($parent_field = $this -> model -> getParentField())
					if(isset($this -> content[$parent_field]) && $this -> content[$parent_field])
						$this -> model -> setParentId($this -> content[$parent_field]);
							
				$prepared_values[$field] = $this -> content[$field] = $this -> model -> setLastOrder($object);
			}
			else if($type == "int" || $type == "parent")
				$prepared_values[$field] = intval($value);
			else if($type == "float")
				$prepared_values[$field] = floatval($value);
			else if($type == "text")
			{
				if($object -> getProperty("display_method"))
					continue;

				$search = array("'", "\t");
				$replace = array("&#039;", "");		
				$prepared_values[$field] = str_replace($search, $replace, $value);
						
				if(!$object -> getProperty("rich_text"))
				{
					$search = array("&", "<", ">", '"');
					$replace = array("&amp;", "&lt;", "&gt;", "&quot;");		
					
					$prepared_values[$field] = str_replace($search, $replace, $prepared_values[$field]);
				}
			}
			else if($type == 'image' || $type == 'file')
			{
				if(preg_match("/^userfiles\/models\//", $value))
					$value = Service :: addFileRoot($value);
				
				$object -> setRealValue($value, basename($value));
				
				if($new_path = $object -> copyFile(get_class($this -> model)))				
					$prepared_values[$field] = Service :: removeFileRoot($new_path);
				else
					$prepared_values[$field] = "";
			}
			else if($type == 'multi_images')
			{
				$object -> setValue($this -> content[$field]);
				$object -> copyImages($this -> model -> getModelClass());
				$prepared_values[$field] = $object -> getValue();
			}
			else //TODO check unique values?
				$prepared_values[$field] = htmlspecialchars($value, ENT_QUOTES);
		}
		
		return $prepared_values;
	}
	
	public function getEnumTitle($field)
	{
		$object = $this -> model -> getElement($field);
		
		if($object && isset($this -> content[$field])) //If this field exists
			if(!isset($this -> enum_values[$field][$this -> content[$field]]))
			{
				if($object -> getProperty("long_list")) //If long list we get only one cell
					//Some inside cache to keep the extracted values
					$this -> enum_values[$field][$this -> content[$field]] = $object -> getValueName($this -> content[$field]);
				else 
				{
					$object -> defineValuesList(); //We take full values list
					
					//Some inside cache to keep the extracted values
					$this -> enum_values[$field] = $object -> getValuesList();									
				}
				
				if(isset($this -> enum_values[$field][$this -> content[$field]]))
					return $this -> enum_values[$field][$this -> content[$field]];
			}
			else
				return $this -> enum_values[$field][$this -> content[$field]];
	}	
	
	public function create()
	{
		if(get_parent_class($this -> model) == "Model_Simple" || $this -> id)
			return;
			
		$params = array();
		$prepared_values = $this -> prepareContentValues();
						
		foreach($prepared_values as $field => $value)
			if($this -> model -> getElement($field))
				$params[$field] = $value;
				
		if(count($params))
		{
			$this -> id = $this -> content['id'] = $this -> model -> createRecord($params);
			return $this -> id;
		}
	}
	
	public function update()
	{
		if($this -> id && get_parent_class($this -> model) != "Model_Simple")
		{	
			$params = array();
			$prepared_values = $this -> prepareContentValues();
						
			foreach($prepared_values as $field => $value)
				if($this -> model -> getElement($field))
					$params[$field] = $value;
					
			if(count($params))
				$this -> model -> updateRecord($this -> id, $params);
		}
		
		return $this;
	}
	
	public function delete()
	{
		if($this -> id && get_parent_class($this -> model) != "Model_Simple")
		{
			$this -> model -> deleteRecord($this -> id) -> drop();
			$this -> id = null;
			$this -> content = array();
		}
			
		return $this;
	}
	
	public function __call($method, $arguments)
	{
		if($method == "getContent")
			return $this -> getValues();
		else if($method == "getEnumValue")
			return $this -> getEnumTitle($arguments[0]);
		else
			Debug :: displayError("Call to undefiend method '".$method."' of Record object '".get_class($this -> model)."'.");
	}
	
	public function getModelName()
	{
		return $this -> model -> getModelClass();
	}
}
?>