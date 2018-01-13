<? 
class Password_Model_Element extends Char_Model_Element
{
	protected $min_length = 6;
	
	protected $letters_required = false;
	
	protected $digits_required = false;
		
	public function validate()
	{
		$this -> unique = false;
				
		parent :: validate();
				
		if(!$this -> error && $this -> value)
			if($this -> letters_required && !preg_match("/[a-zA-Z_]+/", $this -> value))
				$this -> error = "{error-letters-required}";
			else if($this -> digits_required && !preg_match("/\d/", $this -> value))
				$this -> error = "{error-digits-required}";
		
		return $this;
	}
	
    public function displayHtml()
    {
        return str_replace("type=\"text\"", "type=\"password\"",  parent :: displayHtml());
    }
}
?>
