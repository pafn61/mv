<? 
class Order_Model_Element extends Int_Model_Element
{
	protected $positive = true;
	
	protected $depend_on_enum = false;
	
	public function displayHtml()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0] && $arguments[0] != "frontend")
			$this -> value = intval($arguments[0]);
			
		return parent :: displayHtml();
	}
	
	public function displayHtmlForTable($value, $row_id)
	{		
		$html = "<div class=\"move_".$this -> name."\">\n";
		$html .= "<span title=\"".I18n :: locale('move-first')."\" class=\"top\"></span>\n";
		$html .= "<span title=\"".I18n :: locale('move-up')."\" class=\"up\"></span>\n";
		$html .= "<span id=\"row_".$row_id."\" class=\"number\">".intval($value)."</span>\n";
		$html .= "<span title=\"".I18n :: locale('move-down')."\" class=\"down\"></span>\n";
		$html .= "<span title=\"".I18n :: locale('move-last')."\" class=\"bottom\"></span>\n";

		return $html."</div>\n";
	}
	
	public function getLastNumber($model_table, $condition)
	{
		$db = Database :: instance();
		
		return (int) $db -> getCell("SELECT MAX(`".$this -> name."`) 
								     FROM `".$model_table."` 
								     WHERE `".$this -> name."`!=''".$condition);
		
	}
}
?>