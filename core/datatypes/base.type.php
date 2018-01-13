<?
abstract class Model_Element
{
	protected $caption; //Name of field which is shown in left column of form table
	protected $html_params = false; //All extra parameters of element (id, class, cols, rows)
	protected $name; //Name of element (html form tag parameter and db field name)
	protected $required = false; //If this element must be completed in form	
	protected $error = false; //Error appeared in checking process
	protected $help_text = false; //Help sign of form element to show additional information
	protected $type; //Data type of element (int, char, bool, ...)
	protected $unique = false; //If this field must have unique values in model table
	protected $value; //Value of element	
	protected $must_match = false; //If this field must have the same value as other
	protected $model; //Model which this element is related to
	protected $custom_errors = array(); //Overrided errors texts
	
	public function __construct($caption, $type, $name, $extra_params)  //Sets needed values to object properties
	{
		$this -> name = $name;
		$this -> caption = $caption;
		$this -> type = $type;
		
		$properties = get_object_vars($this); //Gets all existing vars of object

		if(count($extra_params))
			foreach($extra_params as $property => $value)
				if(array_key_exists($property, $properties)) //If this object has this property we set it
					$this -> setProperty($property, $value);
				else
				{
					$message = "Undefined extra parameter '".$property."' in element '".$name."' of model '";
					$message .= ucfirst($extra_params['model'])."'.";
					Debug :: displayError($message);
				}
	}
	
	public function addHtmlParams() //Adds parameters to the element html tag
	{ 
		return ($this -> html_params) ? " ".$this -> html_params : "";
	}
	
	public function setValue($value)
	{
		//Deletes spaces from value and prevents XSS
		$this -> value = htmlspecialchars(trim($value), ENT_QUOTES);
		return $this;
	}
	
	public function passValue($value)
	{
		$this -> value = $value;		
		return $this;
	}
	
	public function getValue() 
	{
		return $this -> value; 
	}
	
	public function cleanValue()
	{
		if($this -> value)
			$this -> value = Service :: cleanHtmlSpecialChars($this -> value);
		
		return $this;
	}
	
	public function setRequired($value) { $this -> required = $value; return $this; }
	public function setHelpText($value) { $this -> help_text = $value; return $this; }
	public function setCaption($value) { $this -> caption = $value; return $this; }
	public function setHtmlParams($value) { $this -> html_params = $value; return $this; }
	public function setError($error) { $this -> error = $error; return $this; }
	
	public function getName() { return $this -> name; }
	public function getCaption() { return $this -> caption; }
	public function getType() { return $this -> type; }
	public function getError() { return $this -> error; }
	
	public function getProperty($property)
	{
		if(isset($this -> $property))
			return $this -> $property;
	}
	
	public function hasProperty($property)
	{
		return isset($this -> $property);
	}
	
	public function setProperty($property, $value)
	{
		$properties = get_object_vars($this);
		
		$int_properties = array("max_size", "max_width", "max_height", "min_length", "max_length", "length", "height",
								"form_preview_width", "form_preview_height");
		
		if($property == "value")
			return $this -> setValue($value);
		
		if(array_key_exists($property, $properties))
		{
			if($property == "min_max_length")
			{
				if(preg_match("/^\d+,\s*\d+$/", $value))
				{
					$numbers = explode(",", $value);
					$this -> min_length = abs(intval(trim($numbers[0])));
					$this -> max_length = abs(intval(trim($numbers[1])));
				}
			}
			else if(in_array($property, $int_properties))
				$this -> $property = is_numeric($value) ? abs(intval($value)) : false;
			else
				$this -> $property = is_array($value) ? $value : (is_bool($value) ? $value : trim($value));
		}
		else
		{
			$message = "Undefined extra parameter '".$property."' in element '".$this -> name."' of model '";
			$message .= ucfirst($this -> model)."'.";
			Debug :: displayError($message);
		}
		
		return $this;
	}
	
	public function chooseError($rule, $default_text)
	{
		if(isset($this -> custom_errors[$rule]) && $this -> custom_errors[$rule])
			return $this -> custom_errors[$rule];
		else
			return $default_text;
	}
	
	public function addHelpText()
	{
		if($this -> help_text)
		{
			if(preg_match("/^\{.*\}$/", $this -> help_text))
				$this -> help_text = I18n :: locale(preg_replace("/^\{(.*)\}$/", "$1", $this -> help_text));
			
			return "<div class=\"help-text\">".$this -> help_text."</div>\n";
		}
	}
	
	abstract function validate(); //Checks the element value according to established rules
	
	abstract function displayHtml(); //Function to make html code of the element
}
?>