<? 
class Url_Model_Element extends Char_Model_Element
{
	protected $format = "/^[a-z\d-]+$/";
	
	protected $translit_from = false;

	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		$this -> value = mb_strtolower($this -> value, "utf-8");
		
		if(!$this -> error && $this -> value != "")
			if(preg_match("/^[^a-z]+$/", $this -> value) || !preg_match($this -> format, $this -> value))
				$this -> error = $this -> chooseError("format", "{error-url-format}");
		
		return $this;
	}
	
	public function displayHtml()
	{
		$html = parent :: displayHtml();
		
		if($this -> translit_from)
			$html =	str_replace(" />", " rel=\"translit-from-".$this -> translit_from."\" /><span class=\"translit\"></span>", $html);
			
		return $html;
	}
}
?>
