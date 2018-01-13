<?
class Redirect_Model_Element extends Char_Model_Element
{
	protected $format = "/^https?:\/\/(www\.)?(([a-z\d-]+\.)+[a-z]{2,4}|localhost)((\/|\?).*)*$/";
	
	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		$this -> value = mb_strtolower($this -> value, "utf-8");
		
		if(!$this -> error && $this -> value)
			if(!preg_match($this -> format, $this -> value))
				$this -> error = $this -> chooseError("format", "{error-redirect-format}");
		
		return $this;
	}
}
?>