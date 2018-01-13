<? 
class Many_To_One_Model_Element extends Model_Element
{
	protected $related_model;
	
	protected $related_id;
	
	protected $related_field;
	
	protected $name_field = 'name';
	
	protected $display_count = true;
	
	public function setRelatedId($related_id)
	{
		$this -> related_id = intval($related_id);
		return $this;
	}
	
	public function setRelatedFeild($related_field)
	{	
		$this -> related_field = $related_field;
		return $this;
	}
	
	public function validate()
	{
		return $this;
	}
	
	public function displayHtml()
	{
		$html = "";
		
		$object = new $this -> related_model();
		
		if($this -> related_id)
			$names = $object -> db -> getColumn("SELECT `".$this -> name_field."`  
		  							   		     FROM `".$object -> getTable()."` 
									             WHERE `".$this -> related_field."`='".$this -> related_id."'");
		else
			$names = array();
		
		if(!$element = $object -> getElement($this -> name_field))
		{
			$message = "You must set correct name field in related model '".get_class($object)."' ";
			$message .= "for '".$this -> name."' field of current model. Model '".get_class($object)."' ";
			$message .= "has no field with name 'name'. Set it like 'name_field' => 'label'.";
			Debug :: displayError($message);
		}
		
		$type = $element -> getType();
		$result = array();
		
		if($type == 'enum')
		{		
			if($element -> getProperty('foreign_key') && !$element -> getProperty('values_list'))
				$enum_values = $element -> getDataOfForeignKey();
			else
				$enum_values = $element -> getProperty('values_list');
		}	
		
		foreach($names as $name)
			if($name)
				if($type == 'enum')
					$result[] = $enum_values[$name];
				else if($type == 'date' || $type == 'date_time')
					$result[] = I18n :: dateFromSQL($name);
				else
					$result[] = $name;
		
		if(count($result))
			if($this -> display_count) //If we ned to show just number of records
			{
				$href = $object -> registry -> getSetting("AdminPanelPath")."model/?model=";
				$href .= $object -> getModelClass()."&".$this -> related_field."=".$this -> related_id;
				$html .= "<a class=\"to-children\" href=\"".$href."\">".I18n :: formatIntNumber(count($result))."</a>";
			}
			else
				$html .= implode("<br />", $result);
					
		return $html ? $html.$this -> addHelpText() : "-".$this -> addHelpText();
	}
	
	public function countChildElements($self_model)
	{
		$related_object = new $this -> related_model();
		
		$html = "<a class=\"to-children\" href=\"?model=".strtolower($this -> related_model)."&";
		$html .= $this -> related_field."=".$this -> related_id."\">";
		
		$number = (int) $related_object -> db -> getCell("SELECT COUNT(*)  
		  			    			   		    	      FROM `".$related_object -> getTable()."` 
								        	    		  WHERE `".$this -> related_field."`='".$this -> related_id."'");
		
		return $number ? $html.I18n :: formatIntNumber($number)."</a>" : '-';
	}
}
?>
