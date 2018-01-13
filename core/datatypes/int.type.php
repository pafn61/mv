<?
 class Int_Model_Element extends Char_Model_Element
{
	protected $format = "/^-?[1-9]\d*$/";
	
	protected $zero_allowed = true;
	
	protected $positive = false;
	
	public function setValue($value)
	{
		return parent :: setValue(str_replace(" ", "", $value));
	}
	
	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		
		if(!$this -> zero_allowed && $this -> value == '0')
			$this -> error = $this -> chooseError("zero_allowed", "{error-zero-forbidden}");
		else if($this -> required && $this -> value != '0' && !$this -> value)
			$this -> error = $this -> chooseError("required", "{error-required}");	
		else if($this -> value && !preg_match($this -> format, $this -> value))
			$this -> error = $this -> chooseError("format", "{error-not-int}");
		else if($this -> positive && $this -> value < 0)
			$this -> error = $this -> chooseError("positive", "{error-not-positive}");

		return $this;
	}
} 
?>