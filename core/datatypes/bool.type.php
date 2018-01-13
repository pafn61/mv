<?
class Bool_Model_Element extends Model_Element
{
	protected $on_create = false;
	
	protected $quick_change = true;
	
	public function validate()
	{
		if($this -> required && !$this -> value)
			$this -> error = $this -> chooseError("required", "{error-required-bool}");
		
		return $this;
	}
	
	public function displayHtml()
	{
		$html = "<input type=\"checkbox\" name=\"".$this -> name."\"".$this -> addHtmlParams();
		$html .= $this -> value ? " checked=\"checked\" />" : " />";
		$html .= $this -> addHelpText();
		
		return $html;
	}
	
	public function setValue($value)
	{
		$this -> value = intval((bool) $value);
		return $this;
	}
			
	public function getValue()
	{ 
		return intval($this -> value);
	}
}
?>