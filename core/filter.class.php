<?
class Filter
{
	//Fields with model's data
	private $fields = array();
	
	//URL parametes for merge
	private $url_params;
	
	//Model object of current filters
	private $model;
		
	public function __construct($fields)
	{
		$arguments = func_get_args();
		$registry = Registry :: instance();
		
		if(isset($arguments[1]) && $arguments[1] == "frontend")
		{
			$this -> fields = $fields;
			
			if(isset($arguments[2]) && is_object($arguments[2]) && $registry -> checkModel($arguments[2] -> getModelClass()))
				$this -> model = $arguments[2];
				
			return;
		}
				
		foreach($fields as $name => $data)
			if($data['type'] != 'password')
			{
				$this -> fields[$name] = $data;
				
				//If filter value was passed in GET
				if(isset($_GET[$name]) || isset($_GET[$name."-from"]) || isset($_GET[$name."-to"]))
				{
					$type = $this -> fields[$name]['type'];
					$checked_value = "";
					
					if($type == 'date' || $type == 'date_time') //Date and date time fialds processing
					{
						if(isset($_GET[$name."-from"]) && $_GET[$name."-from"]) //Interval from
							$checked_value['from'] = $this -> checkFieldValue($type, $_GET[$name."-from"]);
						
						if(isset($_GET[$name."-to"]) && $_GET[$name."-to"]) //Interval to
							$checked_value['to'] = $this -> checkFieldValue($type, $_GET[$name."-to"]);
					}
					else //Normal field
						$checked_value = $this -> checkFieldValue($type, $_GET[$name], $data);
					
					if($checked_value != '') //If value was taken from GET and checked
					{
						//Numeric values must have a condition like '=','>','<'
						if(in_array($type, array('int','float','order','many_to_one')))
							if(isset($_GET[$name."-cond"])) 
							{
								if(in_array($_GET[$name."-cond"], array('eq','neq','gt','lt','gte','lte')))
									$this -> fields[$name]['condition'] = $_GET[$name."-cond"];
							}
							else //If condition was not passed we skip this field at all
								continue;
						
						$this -> fields[$name]['value'] = $checked_value; //Passes the value into filter
					}
				}
			}
	}
	
	public function addFilter($caption, $type, $name)
	{
		$types = array("bool", "char", "int", "float", "enum", "text");
		$arguments = func_get_args();
		$params = (isset($arguments[3]) && is_array($arguments[3])) ? $arguments[3] : array();
		
		if(!in_array($type, $types))
			return $this;
		
		$this -> fields[$name] = array("type" => $type, "caption" => $caption);
		
		if($type == "enum")
		{
			$this -> fields[$name]["values_list"] = array();
			
			if(isset($params["values_list"]) && is_array($params["values_list"]) && count($params["values_list"]))
				$this -> fields[$name]["values_list"] = $params["values_list"];
			else if(isset($params["foreign_key"]) && $params["foreign_key"])
			{
				$object = $this -> model -> getElement($name);
				$this -> fields[$name]["long_list"] = $object -> getProperty("long_list");
				
				if(!$this -> fields[$name]["long_list"])
				{
					$object -> defineValuesList(get_class($this -> model));
					$this -> fields[$name]["values_list"] = $object -> getProperty("values_list");
				}
			}
			
			if(isset($params["empty_value"]) && $params["empty_value"])
				$this -> fields[$name]["empty_value"] = $params["empty_value"];
			else
				$this -> fields[$name]["empty_value"] = I18n :: locale("not-defined");
		}		
		
		if(isset($_GET[$name]))
		{
			$value = self :: checkFieldValue($type, trim($_GET[$name]), $this -> fields[$name]);
		
			if($value != "")
				$this -> fields[$name]["value"] = $value;
		}
		else
			foreach($_GET as $key => $value)
				if($key == $name."-from" || $key == $name."-to")
				{
					$condition = $key == $name."-from" ? "gte" : "lte";
					$value = Filter :: checkFieldValue($type, trim($value), $this -> fields[$name]);
			
					if($value != "")
						$this -> fields[$name]["conditions"][$condition] = $value;
				}
		
		return $this;
	}
	
	public function removeFilter($field)
	{
		if(isset($this -> fields[$field]))
			unset($this -> fields[$field]);
		
		return $this;
	}
	
	public function setAllowedCountFilter($field)
	{
		if(array_key_exists($field, $this -> fields) && $this -> fields[$field]['type'] == 'many_to_one')
			 $this -> allowed_count_field = $field;
		
		return $this;
	}
	
	static public function checkFieldValue($type, $value)
	{
		$checked_value = "";
		$arguments = func_get_args(); //We need the list of possible enum values
		$db = Database :: instance();
		
		//Check of fields values of different types
		if(in_array($type, array('char','url','redirect','email','text','phone')))
		{
			$value = str_replace("+", "%2B", $value);
			$checked_value = urldecode($value);
		}
		else if($type == 'enum' || $type == 'group')
		{
			if($value == "*" || $value == "-")
				$checked_value = $value;
			else if(isset($arguments[2]['long_list'], $arguments[2]['foreign_key']) && $arguments[2]['long_list'])
			{				
				if($db -> getCount($arguments[2]['foreign_key'], "`id`='".intval($value)."'"))
					$checked_value = $value;
			}
			else if(isset($arguments[2]['table']) && $arguments[2]['table'])
			{
				if($db -> getCount($arguments[2]['table'], "`id`='".intval($value)."'"))
					$checked_value = $value;				
			}
			else if(isset($arguments[2]['values_list']) && array_key_exists($value, $arguments[2]['values_list']))
				$checked_value = htmlspecialchars(urldecode($value)); //Pass the value
		}
		else if($type == 'parent')
		{
			if($value == 'all')
				$checked_value = 'all';
			else if(isset($arguments[2]['long_list'], $arguments[2]['table']) && $arguments[2]['long_list'])
			{
				$db = Database :: instance();
				$value = intval($value);
				
				if($value == -1)
					$checked_value = -1;
				else if($db -> getCount($arguments[2]['table'], "`id`='".$value."'"))
					$checked_value = $value;
			}
			else if(isset($arguments[2]['values_list']) && array_key_exists(intval($value), $arguments[2]['values_list']))
			 	$checked_value = intval($value);
		}
		else if($type == 'many_to_many')
		{
			if($value == "*" || $value == "-")
				$checked_value = $value;
			else if(is_numeric($value))
				$checked_value = strval(intval($value));
		}
		else if(in_array($type, array('int','order','many_to_one')) && is_numeric($value))
			$checked_value = strval(intval($value));
		else if($type == 'float' && is_numeric($value))
			$checked_value = strval(floatval($value));
		else if(in_array($type, array('bool','image','file','multi_images')) && ($value == '0' || $value == '1'))
			$checked_value = $value;
		else if($type == 'date' && I18n :: checkDateFormat($value))
				$checked_value = $value;
		else if($type == 'date_time' && I18n :: checkDateFormat($value, "with-time"))
				$checked_value = $value;
				
		return $checked_value;
	}
	
	public function getParamsForSQL()
	{
		$conditions = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 
						    'lt' => '<', 'gte' => '>=', 'lte' => '<='); //To transform the condition
		$sql = array();
		$db = Database :: instance();
		$registry = Registry :: instance();
		
		 //Sql query construction		
		foreach($this -> fields as $name => $data)
		{
			if(isset($data['value']) && !is_array($data['value']))
				$data['value'] = htmlspecialchars($data['value'], ENT_QUOTES);
			
			if($data['type'] == 'many_to_one' || $data['type'] == 'many_to_many')
				continue; //This filters we apply in core class
			else if(in_array($data['type'], array('int','float','order')) && 
			   isset($data['value']) && $data['value'] != '' && isset($data['condition']) && 
			   array_key_exists($data['condition'], $conditions)) //Numeric values to sql			   
					$sql[] = "`".$name."`".$conditions[$data['condition']]."'".$data['value']."'";
			else if($data['type'] == 'date' || $data['type'] == 'date_time') //Date values
			{
				if(isset($data['value']['from']) && $data['value']['from'])
					$sql[] = "`".$name."`>='".I18n :: dateForSQL($data['value']['from'])."'";
				
				if(isset($data['value']['to']) && $data['value']['to'])
					$sql[] = "`".$name."`<='".(I18n :: dateForSQL($data['value']['to']))."'";
			}
			else if(($data['type'] == 'enum' || $data['type'] == 'bool') && 
					 isset($data['value']) && $data['value'] != '') //Enum values
			{
				if($data['value'] == "*")
					$sql[] = "`".$name."`!='' AND `".$name."`!='0' AND `".$name."` IS NOT NULL";
				else if($data['value'] == "-")
					$sql[] = "(`".$name."`='' OR `".$name."`='0' OR `".$name."` IS NULL)";
				else
					$sql[] = "`".$name."`='".$data['value']."'";
			}			
			else if(in_array($data['type'], array('image','file','multi_images')) && isset($data['value']))
				$sql[] = $data['value'] ? "`".$name."` LIKE '%.%'"  : "`".$name."`=''";
			else if($data['type'] == 'parent' && isset($data['value']) && $data['value'] != '')
			{
				if($data['value'] != 'all')
					$sql[] = "`".$name."`='".$data['value']."'";
			}
			else if($data['type'] == 'group' && isset($data['value']) && $data['value'])
			{
				if($data['value'] == "*")
					$sql[] = "`".$name."`!='' AND `".$name."` IS NOT NULL";
				else if($data['value'] == "-")
					$sql[] = "(`".$name."`='' OR `".$name."` IS NULL)";
				else
				{
					$operator = $registry -> getSetting("DbEngine");
					$operator = ($operator == "sqlite") ? "REGEXP" : "RLIKE"; 
					$value = intval($data['value']);
					
					$regexp = "`".$name."` ".$operator." '^".$value."$' OR ";
					$regexp .= "`".$name."` ".$operator." '^".$value.",' OR ";
					$regexp .= "`".$name."` ".$operator." ',".$value."$' OR ";
					$regexp .= "`".$name."` ".$operator." ',".$value.",'";					
					$sql[] = "(".$regexp.")";
				}
			}			
			else if(isset($data['value']) && $data['value'] != '') //String values
				$sql[] = "`".$name."` LIKE '%".$data['value']."%'";
		}

		return implode(" AND ", $sql); //Glues the params into one string
	}
	
	public function setUrlParams($url_params)
	{
		if($url_params)
			$this -> url_params = "?".$url_params;	
	} 
	
	public function getUrlParams()
	{
		//Makes string of GET params
		$params = array();
		
		if(!count($this -> fields)) return;
		
		$arguments = func_get_args();
		$in_admin = (isset($arguments[0]) && $arguments[0] == "admin");
		
		if($in_admin)
		{
			foreach($this -> fields as $name => $data)
				if($data['type'] == 'date' || $data['type'] == 'date_time')
				{
					if(isset($data['value']['from']) && $data['value']['from'])
						$params[$name.'-from'] = $data['value']['from'];
	
					if(isset($data['value']['to']) && $data['value']['to'])
						$params[$name.'-to'] = $data['value']['to'];
				}
				else if(in_array($data['type'], array('int','float','order','many_to_one')))
				{
					if(isset($data['condition']) && $data['condition'])
					{
						$params[$name] = $data['value'];
						$params[$name.'-cond'] = $data['condition'];
					}
				}
				else if(isset($data['value']) && $data['value'] != '')
					$params[$name] = $data['value'];
		}
		else
		{
			foreach($this -> fields as $name => $data)
				if(in_array($data['type'], array('date','date_time','int','float','order')) &&
				    (!isset($data['display_single_field']) || !$data['display_single_field']))
				{
					if(isset($data['conditions']['gte']) && $data['conditions']['gte'] != '')
						$params[$name.'-from'] = $data['conditions']['gte'];
					
					if(isset($data['conditions']['lte']) && $data['conditions']['lte'] != '')
						$params[$name.'-to'] = $data['conditions']['lte'];
				}
				else if(isset($data['value']) && $data['value'] != '')
					$params[$name] = $data['value'];
		}
		
		$result = array();
		
		foreach($params as $name => $value)
			$result[] = $name."=".($data['type'] != "enum" ? urlencode($value) : $value);
		
		return implode('&', $result);
	}

	public function addUrlParams($path)
   	{
   		if($this -> hasParams())
   		{
			$path .= (strpos($path, "?") === false) ? "?" : "&";     
       		$path .= $this -> getUrlParams();
   		}
		
		return $path;
   	}
   	
   	public function getValue($field)
   	{
   		if(isset($this -> fields[$field]))
   			if(in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')) && 
   				(!isset($this -> fields[$field]['display_single_field']) || !$this -> fields[$field]['display_single_field']))
   			{
   				$arguments = func_get_args();
   				$condition = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
   				
   				if($condition == "from")
   					$condition = "gte";
   				else if($condition == "to")
   					$condition = "lte";
   				
   				if($condition && isset($this -> fields[$field]['conditions'][$condition]))
   					return $this -> fields[$field]['conditions'][$condition];
   			}
   			else if(isset($this -> fields[$field]['value']) && $this -> fields[$field]['value'] != '')
   				return $this -> fields[$field]['value'];
   	}
   	
	public function setValue($field, $value)
   	{
   		$arguments = func_get_args();
   		$from_or_to = $condition = false;
   		
   		if(isset($arguments[2]) && ($arguments[2] == "from" || $arguments[2] == "to"))
   		{
   			$from_or_to = $arguments[2];
   			$condition = ($from_or_to == "from") ? "gte" : "lte";
   		}
   		
   		if(isset($this -> fields[$field]))
   		{
   			if(($this -> fields[$field]['type'] == "enum" || $this -> fields[$field]['type'] == "many_to_many") && 
   				isset($this -> fields[$field]["display_checkbox"]) && $this -> fields[$field]["display_checkbox"])
   			{
   				$checked_values = array();
   				 
   				foreach(explode(",", $value) as $checked)
   					if(trim($checked) && array_key_exists(trim($checked), $this -> fields[$field]["values_list"]))
   						$checked_values[] = trim($checked);
   				
   					 
				$this -> fields[$field]["value"] = count($checked_values) ? implode(",", $checked_values) : "";
				
				return $this;
   			}

  		    $value = $this -> checkFieldValue($this -> fields[$field]['type'], $value, $this -> fields[$field]);
   				   			
   		    if(in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')))
	   		{	   			
	   			if($from_or_to)
	   				$this -> fields[$field]['conditions'][$condition] = $value;
	   			else
	   				$this -> fields[$field]['value'] = $value;
	   		}
	   		else
	   			$this -> fields[$field]['value'] = $value;
   		}
   		
   		return $this;
   	}	
	
	public function displayManagerSelects($default_filters, $show_empty_default_filters)
	{
		$html = array("add" => array(), "remove" => array());
		$count = 0;
		$limit = (count($this -> fields) > 7) ? 7 : count($this -> fields);
		$any_applied = $this -> ifAnyFilterApplied();
		$default_filters = (is_array($default_filters) && count($default_filters)) ? $default_filters : false;
		$sorted_filters = array();
		
		foreach($this -> fields as $name => $data)
			$sorted_filters[$data["caption"]] = $name;
		
		foreach($sorted_filters as $caption => $name)
		{
			if(!$default_filters) //No special default filters
				$key = (($any_applied && !$this -> ifApplied($name)) || (!$any_applied && ++ $count > $limit)) ? "add" : "remove";
			else
				if(!$any_applied) //No applied filters show only default ones
					$key = in_array($name, $default_filters) ? "remove" : "add";
				else
					if($this -> ifApplied($name)) //Show applied filter
						$key = "remove";
					else //Adds default filters if needed
						$key = ($show_empty_default_filters && in_array($name, $default_filters)) ? "remove" : "add";

			$html[$key][$caption] = "<option value=\"".$name."\">".$caption."</option>\n";
		}
		
		//Ordering the list by a-z
		ksort($html["add"]);
		ksort($html["remove"]);
		
		return array("add" => implode("", $html["add"]), "remove" => implode("", $html["remove"]));
	}
	
	public function displayAdminFilters($default_filters, $show_empty_default_filters)
	{
		$html = "";
		$count = 0;
		
		$limit = (count($this -> fields) > 7) ? 7 : count($this -> fields);
		$any_applied = $this -> ifAnyFilterApplied();
		
		if(!is_array($default_filters) && array_key_exists($default_filters, $this -> fields))
			$only_filter = $default_filters;
		else 
			$only_filter = false;
			
		$default_filters = (is_array($default_filters) && count($default_filters)) ? $default_filters : false;
		
		if($only_filter) //Only one filter (adding in AJAX)
			$filters_to_show = array($only_filter);
		else //Many filters to show
		{
			$filters_to_show = array(); //Array of current filters
			
			if(!$default_filters) //If no option of default filters
			{
				foreach($this -> fields as $name => $data) //Collecting only applied or regular default filters 
					if(($any_applied && !$this -> ifApplied($name)) || (!$any_applied && ++ $count > $limit))
						continue;
					else
						$filters_to_show[] = $name;
			}
			else //We set up the needed default filters 
			{
				if(!$any_applied) //No filters applied we show only default filters
					foreach($default_filters as $name)
						if(array_key_exists($name, $this -> fields))
							$filters_to_show[] = $name;
								
				if($any_applied) //Some filters set we show them first
					foreach($this -> fields as $name => $data)
						if($this -> ifApplied($name))
							$filters_to_show[] = $name;
						
				//If we have the option to show not applied default filters
				if($any_applied && $show_empty_default_filters)
					foreach($default_filters as $name)
						if(array_key_exists($name, $this -> fields) && !in_array($name, $filters_to_show))
							$filters_to_show[] = $name; //Adds missing default filters
			}
		}

		foreach($filters_to_show as $name) //Creates html for filters fields
		{
			$data = $this -> fields[$name];
			$html .= "<div class=\"filter-name\" id=\"filter-".$name."\">".$data['caption']."</div>\n";
			$html .= "<div class=\"filter-input\">\n";
			
			if(isset($data['value']) && $data['value'] != '')
				$value = is_array($data['value']) ? $data['value'] : htmlspecialchars($data['value'], ENT_QUOTES);
			else
				$value = '';
			
			if(in_array($data['type'], array('bool','image','file','multi_images')))
			{											 
				$select_data = array(I18n :: locale('not-defined') => '', 
									 I18n :: locale('yes') => '1', 
									 I18n :: locale('no') => '0');
				
				$html .= $this -> createSelectTag($name, $select_data, $value, "backend");
			}
			else if(in_array($data['type'], array('char','url','redirect','email','text','phone')))
				$html .= "<input class=\"text\" type=\"text\" name=\"".$name."\" value=\"".$value."\" />\n";
			else if($data['type'] == 'enum' || $data['type'] == 'many_to_many' || $data['type'] == 'group')
			{
				if($data['long_list'])
				{
					$select_data = array(I18n :: locale('search-by-name') => '', 
										 I18n :: locale('has-value') => '*', 
										 I18n :: locale('has-no-value') => '-');
									 		    
				    $select_value = ($value == "*" || $value == "-") ? $value : "";
				    
				    $html .= "<div class=\"long-list-select\">";
				    $html .= $this -> createSelectTag("", $select_data, $select_value, "backend")."</div>\n";					
				    
					$value_ = $value;

					if($value_)
						if($data['type'] == 'enum')
						{
							$object = new Enum_Model_Element($data["caption"], "enum", $name, $data);
							$value_ = $object -> getValueName($value_);

							if(isset($data["show_parent"]) && $value_)
								if($name_of_parent = $object -> getNameOfParentOfForeignKey($value))
									$value_ .= " (".$name_of_parent.")";
							
							$value_ =  $value_ ? $value_ : "";
						}
						else
							$value_ = $data['element'] -> checkValue($value);
					
					$html .= "<input class=\"autocomplete-input\" type=\"text\" value=\"".$value_."\" />\n";
				    $html .= "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\" />\n";
				}
				else
				{
					$select_data = count($data['values_list']) ? array_flip($data['values_list']) : array();
					$html_ = $this -> createSelectTag($name, $select_data, $value, "backend");
					
					$selected_empty = ($value == "*") ? " selected=\"selected\"" : "";
					$selected_any = ($value == "-") ? " selected=\"selected\"" : "";
				
					$replace = "$1<option value=\"\">".I18n :: locale('not-defined')."</option>\n";
					$replace .= "<option".$selected_empty." value=\"*\">".I18n :: locale('has-value')."</option>\n";
					$replace .= "<option".$selected_any." value=\"-\">".I18n :: locale('has-no-value')."</option>\n$2";
					
					$html_ = preg_replace("/(<select[^>]+>\n)(.*)/", $replace, $html_);				
					$html .= $html_;
				}					
			}
			else if($data['type'] == 'parent')
			{
				$checked = $value ? " checked=\"checked\"" : "";
								
				$html .= "<input id=\"parent-".$name."\" type=\"checkbox\"".$checked;
				$html .= " name=\"".$name."\" value=\"all\" />\n";
				$html .= "<label for=\"parent-".$name."\">".I18n :: locale("in-all-catalogs")."</label>\n";				
			}
			else if($data['type'] == 'date' || $data['type'] == 'date_time')
			{
				$from = (isset($value['from']) && $value['from']) ? $value['from'] : "";
				$to = (isset($value['to']) && $value['to']) ? $value['to'] : "";
				$css_class = ($data['type'] == "date") ? "form-date-field" : "form-date-time-field";
				
				$html .= "<table class=\"admin-date-time-filter\"><tr><td>\n";
				$html .= I18n :: locale('date-from')."</td><td><input type=\"text\" class=\"".$css_class."\" ";
				$html .= "name=\"".$name."-from\" value=\"".$from."\" /></td></tr><tr><td>\n";
				$html .= I18n :: locale('date-to')."</td><td><input type=\"text\"class=\"".$css_class."\" ";
				$html .= "name=\"".$name."-to\" value=\"".$to."\" />\n";
				$html .= "</td></tr></table>\n";
			}
			else if(in_array($data['type'], array('int','float','order','many_to_one')))
			{
				$select_data = array(I18n :: locale('not-defined') => '', ' = ' => 'eq', ' &ne; ' => 'neq',
									 ' > ' => 'gt', ' < ' => 'lt', ' &ge; ' => 'gte', 
									 ' &le; ' => 'lte');
				
				if(isset($data['condition']) && in_array($data['condition'], $select_data))
					$selected_condition = $data['condition'];
				else
					$selected_condition = false;
				
				$html .= $this -> createSelectTag($name.'-cond', $select_data, $selected_condition);
				$html .= " <input class=\"numeric\" type=\"text\" name=\"".$name."\" value=\"".$value."\" />\n";
			}
			
			$html .= "</div>\n";
		}
		
		return $html;
	}
	
	public function createSelectTag($name, $data, $selected)
	{
		$arguments = func_get_args();
		$html = "<select".($name ? " name=\"".$name."\"" : "");
		
		if(!isset($arguments[3]) || $arguments[3] != "backend")
			$html .= " id=\"filter-".$name."\"";
		
		$html .= ">\n";

		if(isset($arguments[3]) && $arguments[3] != "backend")
			$html .= "<option value=\"\">".$arguments[3]."</option>\n";

		foreach($data as $name => $value)
		{
			$html .= "<option value=\"".$value."\"";
			
			if($selected != "" && strval($selected) == $value)
				$html .=  " selected=\"selected\"";
				
			$html .= ">".$name."</option>\n";
		}

		return $html."</select>\n";
	}
	
	public function getComplexFilters()
	{
		$filters = array();
		
		foreach($this -> fields as $name => $data)
			if($data['type'] == 'many_to_one')
			{
				if(isset($data['value'], $data['condition']) && $data['value'] != '' && $data['condition'])
					$filters[$name] = $data;
			}
			else if($data['type'] == 'many_to_many' && isset($data['value']) && $data['value'] != '')
				$filters[$name] = $data;
		
		return $filters;
	}
	
	public function ifFilteredByAllParents()
	{
		foreach($this -> fields as $name => $data)
			if($data['type'] == 'parent' && isset($data['value']) && $data['value'] == 'all')
				return $data['caption'];
		
		return false;
	}
	
	public function ifApplied($name)
	{
		return isset($this -> fields[strtolower($name)]['value']);
	}
	
	public function ifAnyFilterApplied()
	{
		$conditions = array('eq', 'neq', 'gt', 'lt', 'gte', 'lte');
		
		foreach($this -> fields as $name => $data)
			if(isset($data['value']) && $data['value'] != "")
				return true;
			else if(isset($data['conditions']) && count($data['conditions']))
				foreach($conditions as $key)
					if(isset($data['conditions'][$key]) && $data['conditions'][$key] != "")
						return true;
		
		return false;
	}
	
	public function hasParams()
	{
		return $this -> ifAnyFilterApplied();
	}

   	public function getConditions()
   	{
   		$conditions = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 
						    'lt' => '<', 'gte' => '>=', 'lte' => '<='); //To transform the condition
   		
   		$sql = array();
		$db = Database :: instance();
   		
   		foreach($this -> fields as $name => $data)
   			if(in_array($data['type'], array('int','float','order','enum','bool','date','date_time', 'parent')) && 
   			   isset($data['value']) && $data['value'] != '' && !isset($data['conditions']))
   			{
   				if($data['type'] == 'date' || $data['type'] == 'date_time')
   					$sql[$name] = I18n :: dateForSQL($data['value']); //Single field with no intervals
   				else if($data['type'] == 'enum' && isset($data["display_checkbox"]) && $data["display_checkbox"])
   					$sql[$name."->in"] = $data['value']; //Multiple choise enum filter				
   				else
					$sql[$name] = $data['value'];
   			}
   			else if(in_array($data['type'], array('image','file','multi_images')) && 
   				isset($data['value']) && $data['value'] != '')
   			{
   				if($data['value'] == 0)
   					$sql[$name] = ""; //If image was not filled
   				else
   					$sql[$name."->like"] = "."; //If we search image file
   			}
   			else if($data['type'] == "many_to_many" && isset($data['value']) && $data['value'] != '')
   			{
				$element = $this -> model -> getElement($name);
   				$found_ids = $element -> getValuesForFilter($data['value']); //Ids frim linking table
   				
   				if(!isset($sql["id->in"])) //It it's a first m2m filter
   					$sql["id->in"] = is_array($found_ids) ? implode(",", $found_ids) : 0;
   				else if($sql["id->in"] != 0) //More m2m filters if previous m2m filters returned a result
   				{
   					if(is_array($found_ids)) //Found some records, based on current m2m filter
   					{
   						$crossing_ids = array(); //Only ids which match all m2m filters conditions
   						
   						foreach(explode(",", $sql["id->in"]) as $id) //Next cycle of m2m filtering
   							if($id && in_array($id, $found_ids))
   								$crossing_ids[] = $id;
   						
						$sql["id->in"] = count($crossing_ids) ? implode(",", $crossing_ids) : 0;
   					}
   					else
   						$sql["id->in"] = 0; //Nothing is found, block all future attempts   						
   				}
   			}
			else if(in_array($data['type'], array('char','text','phone','url','redirect','email')) && 
					isset($data['value']) && $data['value'] != '')
				$sql[$name."->like"] = htmlspecialchars($data['value'], ENT_QUOTES);
			else if(isset($data['conditions']))
				foreach($data['conditions'] as $condition => $value)
				{
					if($data['type'] == 'date' || $data['type'] == 'date_time')
						$value = I18n :: dateForSQL($value);
					
					$sql[$name.$conditions[$condition]] = $value;
				}
	   		
		return $sql;
   	}
   	
   	public function display()
   	{
   		$fields = array_keys($this -> fields);
   		$single_field = $one_interval_part = false;
   		$arguments = func_get_args();
   		$html = "";
   		
   		if(isset($arguments[0]))
   			if(is_array($arguments[0]))
   				$fields = $arguments[0]; //If we pass fields names to display
   			else if(array_key_exists($arguments[0], $this -> fields)) //If only one field to display
   			{
   				$single_field = $arguments[0];
   				$fields = array($arguments[0]);
   			}
			else
   				return; //Something wrong is passed
			
		//If we display only one part of inteval filter (from or to)		
		if($single_field && isset($arguments[1]) && ($arguments[1] == "from" || $arguments[1] == "to"))
			$one_interval_part = $arguments[1];			
   			
   		foreach($fields as $name)
   			if(array_key_exists($name, $this -> fields))
	   		{
	   			$data = $this -> fields[$name];
	   			
	   			if($data['type'] == 'many_to_many' && (!isset($data['display_checkbox']) || !$data['display_checkbox']))
	   			{
	   				$value = (isset($data["value"]) && $data["value"] != "") ? $data["value"] : "";
	   				$empty_value = I18n :: locale('not-defined');
	   				
	   				if(isset($data['empty_value']) && $data['empty_value'])
	   					$empty_value = $data['empty_value'];
	   				
	   				$html .= $this -> createSelectTag($name, array_flip($data['values_list']), $value, $empty_value);
	   				continue;
	   			}
					
				//If its a bool filter in checkbox input
	   			if(in_array($data['type'], array('bool','image','file','multi_images')))
		   			if(isset($data['display_checkbox']) && $data['display_checkbox'])
		   			{
		   				$html .= "<div class=\"filter-input filter-checkbox\">\n".$this -> displayCheckbox($name);
		   				$html .= "<label for=\"filter-".$name."\">".$data['caption']."</label>\n";
		   				$html .= "</div>\n";
		   				continue;
		   			}					
	   			
	   			if(!$single_field) //Interval filed which must look like one input
	   				$html .= "<div class=\"filter-name\">".$data['caption']."</div>\n";
	   			
				if(!$single_field)
					$html .= "<div class=\"filter-input\">\n";
					
				$value = (isset($data['value']) && $data['value'] != '') ? htmlspecialchars($data['value'], ENT_QUOTES) : ''; 
				
				if(in_array($data['type'], array('bool','image','file','multi_images')))
				{
					$select_data = array(I18n :: locale('not-defined') => '', 
										 I18n :: locale('yes') => '1', 
										 I18n :: locale('no') => '0');

					$html .= $this -> createSelectTag($name, $select_data, $value);
				}
				else if(in_array($data['type'], array('char','url','redirect','email','text','phone')))
					$html .= "<input class=\"text\" id=\"filter-".$name."\" type=\"text\" name=\"".$name."\" value=\"".$value."\" />\n";
				else if($data['type'] == 'enum' || $data['type'] == 'parent')
				{
					$select_data = count($data['values_list']) ? array_flip($data['values_list']) : array();
					$empty_value = (isset($data['empty_value']) && $data['empty_value']) ? $data['empty_value'] : false;
					
					if(isset($data['long_list']) && $data['long_list'])
					{
						$value = $value_title = "";
						
						if(isset($data["value"]) && $data["value"])
						{
							$foreign_key = (isset($data["foreign_key"]) && $data["foreign_key"]) ? $data["foreign_key"] : false;
							$object_params = array("long_list" => true, "foreign_key" => $foreign_key);
							$object_params["values_list"] = $data["values_list"];
							
							if($data['type'] == 'enum')
								$object = new Enum_Model_Element($data["caption"], "enum", $name, $object_params);
							else
							{
								$object_params["model"] = $data["table"];
								$object = new Parent_Model_Element($data["caption"], "parent", $name, $object_params);
							}

							if($object -> checkValue($data["value"]))
							{
								$value = $data["value"];
								$value_title = $object -> getValueName($value);
							}
						}
			
						$html_ = "<input class=\"autocomplete-input\" type=\"text\"  value=\"".$value_title."\" />\n";
		    			$html_ .= "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\" />\n";		    
					}
					else if($data['type'] == 'enum' && isset($data['display_checkbox']) && $data['display_checkbox'])
					{
						if(isset($data['empty_value']) && $data['empty_value'] && 
						   isset($data['empty_checkbox']) && $data['empty_checkbox'])
						{
							$values = array("" => $data['empty_value']);
							
							foreach($data['values_list'] as $k => $v)
								$values[$k] = $v;								
						}
						else
							$values = $data['values_list'];
						
						$object_params = array("values_list" => $values,
											   "multiple_choice" => $data['display_checkbox']);
						
						$object = new Enum_Model_Element($data["caption"], "enum", $name, $object_params);
						
						if(isset($data["value"]) && $data["value"])
							$object -> setValue($data["value"]);
						
						$html_ = $object -> displayAsCheckboxes();
					}
					else if($data['type'] == 'enum' && isset($data['display_radio']) && $data['display_radio'])
					{						
						$checked = (isset($data["value"]) && $data["value"] != "") ? $data["value"] : "";
						$columns = intval($data['display_radio']);
						$html_ = Service :: displayOrderedFormTable($data['values_list'], $columns, $checked, $name, "radio");
					}
					else
						$html_ = $this -> createSelectTag($name, $select_data, $value, $empty_value);
					
					$html .= $html_;
				}
	   			else if(in_array($data['type'], array('date','date_time','int','float','order')))
				{
					if(isset($data['display_single_field']) && $data['display_single_field'])
						$html .= $this -> displaySingleField($name);
					else
					{						
						if($data['type'] == 'date' || $data['type'] == 'date_time')
						{
							$text_from = I18n :: locale("date-from");
							$text_to = I18n :: locale("date-to");
						}
						else
						{
							$text_from = I18n :: locale("number-from");
							$text_to = I18n :: locale("number-to");						
						}
						
						$value = (isset($data['conditions']['gte']) && $data['conditions']['gte'] != '') ? $data['conditions']['gte'] : '';
						
						if(!$one_interval_part)
							$html .= "<span id=\"filter-".$name."-title-from\" class=\"filter-interval-name\">".$text_from."</span>\n";
						
						if(!$one_interval_part || $one_interval_part == "from")
						{
							$html .= "<input class=\"text interval\" id=\"filter-".$name."-from\" type=\"text\" ";
							$html .= "name=\"".$name."-from\" value=\"".$value."\" />\n";
						}
						
						$value = (isset($data['conditions']['lte']) && $data['conditions']['lte'] != '') ? $data['conditions']['lte'] : '';
						
						if(!$one_interval_part)
							$html .= "<span id=\"filter-".$name."-title-to\" class=\"filter-interval-name\">".$text_to."</span>\n";
						
						if(!$one_interval_part || $one_interval_part == "to")
						{						
							$html .= "<input class=\"text interval\" id=\"filter-".$name."-to\" type=\"text\" ";
							$html .= "name=\"".$name."-to\" value=\"".$value."\" />\n";
						}
					}
				}
				else if($data['type'] == 'many_to_many')
				{
					$checked = (isset($data["value"]) && $data["value"]) ? explode(",", $data["value"]) :  array();
					$html .= Service :: displayOrderedFormTable($data['values_list'], $data['display_checkbox'], $checked, $name);
				}
				
				if(!$single_field)
					$html .= "</div>\n";
	   		}
	   		
	   	return $html;
   	}
	
	public function displayCheckbox($field)
   	{
   		$html = $checked = "";
   		$allowed_types = array('bool','image','file','multi_images');
   		
   		if(isset($this -> fields[$field]) && in_array($this -> fields[$field]['type'], $allowed_types))
   		{
   			if(isset($this -> fields[$field]['value']) && $this -> fields[$field]['value'] != '')
   				$checked = " checked=\"checked\"";
   			
   			$html .= "<input id=\"filter-".$field."\" type=\"checkbox\" ";
			$html .= "name=\"".$field."\" value=\"1\"".$checked." />\n";
   		}
   		
   		return $html;
   	}
   	
	public function displaySingleField($field)
   	{
   		$html = "";
   		$arguments = func_get_args();
   		$from_or_to = false;

   		if(isset($arguments[1]) && ($arguments[1] == "from" || $arguments[1] == "to"))
   			$from_or_to = $arguments[1];		
   		
   		if(isset($this -> fields[$field]) && 
   		   in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')))
   		{
   			$value = "";
   			
   			if($from_or_to)
   			{
   				$condition = ($from_or_to == "from") ? "gte" : "lte";
   				
   				if(isset($this -> fields[$field]['conditions'][$condition]) && $this -> fields[$field]['conditions'][$condition] != '')
   					$value = $this -> fields[$field]['conditions'][$condition];
   				
	   			$html .= "<input class=\"text\" id=\"filter-".$field."-".$from_or_to."\" ";
	   			$html .= "type=\"text\" name=\"".$field."-".$from_or_to."\" ";
   			}
   			else
   			{
   				if(isset($this -> fields[$field]['value']) && $this -> fields[$field]['value'] != '')
   					$value = $this -> fields[$field]['value'];
   				
   				$html = "<input class=\"text\" id=\"filter-".$field."\" type=\"text\" name=\"".$field."\" ";
   			}
			
   			$html .= "value=\"".$value."\" />\n";
   		}
   		
   		return $html;
   	}   	
   	
   	public function setDisplayCheckbox($field)
   	{
   		$allowed_types = array('bool','image','file','multi_images');
   		
   		if(isset($this -> fields[$field]) && in_array($this -> fields[$field]['type'], $allowed_types))
   			$this -> fields[$field]['display_checkbox'] = true;
   			
   		return $this;
   	}
   	
   	public function setDisplaySingleField($field)
   	{
		if(isset($this -> fields[$field]) && 
			in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')))
				$this -> fields[$field]['display_single_field'] = true;
   			
   		return $this;
   	}
   	
   	public function filterValuesList($field, $params)
   	{
   		$allowed_types = array("enum", "parent", "many_to_many");
   		
   		if(isset($this -> fields[$field]) && $this -> model && count($params) && 
   		   in_array($this -> fields[$field]['type'], $allowed_types))
   		{
   			$element = $this -> model -> getElement($field);
   			$class = $this -> model -> getModelClass();
   			$this -> fields[$field]["values_list"] = $element -> filterValuesList($params, $class) -> getValuesList();
   			
   			if(!isset($this -> fields[$field]["long_list"]) || !$this -> fields[$field]["long_list"])
   				if(isset($this -> fields[$field]["value"]) && $this -> fields[$field]["value"])
   					if(!array_key_exists($this -> fields[$field]["value"], $this -> fields[$field]["values_list"]))
   						$this -> fields[$field]["value"] = "";
   		}
		
		return $this;
   	}
	
	public function setEnumEmptyValueTitle($field, $title)
   	{
   		if(isset($this -> fields[$field]) && $this -> fields[$field]['type'] == "enum")
   			if(isset($this -> fields[$field]['empty_value']))
   				$this -> fields[$field]['empty_value'] = $title;
				
		return $this;
   	}
   	
   	public function setManyToManyEmptyValueTitle($field, $title)
   	{
   		if(isset($this -> fields[$field]) && $this -> fields[$field]['type'] == "many_to_many")
			$this -> fields[$field]['empty_value'] = $title;

		return $this;
   	}
	
	public function setDisplayEnumRadio($field, $columns)
   	{
   		$html = "";
   		
   	   	if(isset($this -> fields[$field]))
   	   		$this -> fields[$field]["display_radio"] = $columns;
   		
   		return $this;
   	}
   	
   	public function setDisplayEnumCheckboxes($field, $columns)
   	{
   		$html = "";
   		$arguments = func_get_args();
   		$empty_checkbox = (isset($arguments[2]) && $arguments[2]);
   		//TODO add option of checkboxes for parent field
		
   		if(isset($this -> fields[$field]) && 
   		   ($this -> fields[$field]["type"] == "enum" || $this -> fields[$field]["type"] == "many_to_many"))
   		{
   			$this -> fields[$field]["display_checkbox"] = intval($columns);
   			
   			if($empty_checkbox)
   				$this -> fields[$field]["empty_checkbox"] = true;
   			
			$checked_values = array();
   			
			if(isset($_GET[$field]) && $_GET[$field] != '') //If we have glued keys in GET
			{
				$checked_values = array();
				
				foreach(explode(",", $_GET[$field]) as $value)
					if($value && array_key_exists($value, $this -> fields[$field]["values_list"]))
						$checked_values[] = $value;
				
				$this -> fields[$field]["value"] = count($checked_values) ? implode(",", $checked_values) : "";
			}
			else
			{
		   		foreach($this -> fields[$field]["values_list"] as $key => $value)
					if(isset($_GET[$field."-".$key]) && $_GET[$field."-".$key] == $key)
						$checked_values[] = $key;
						
				if(count($checked_values))
					$this -> fields[$field]["value"] = implode(",", $checked_values);						
			}	
   		}
   		
   		return $this;
   	}
   	
   	public function setDisplayCheckboxTable($field, $columns)
   	{
   		return $this -> setDisplayEnumCheckboxes($field, $columns);
   	}
   	
   	public function allowChangeOrderLinkedWithEnum($field)
   	{
   		return ($this -> ifApplied($field) && $this -> getValue($field) != "*" && $this -> getValue($field) != "-");
   	}
}
?>