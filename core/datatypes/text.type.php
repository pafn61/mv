<? 
class Text_Model_Element extends Char_Model_Element
{
	protected $rich_text = false;
	
	protected $height = false;
	
	protected $show_in_admin = false;
	
	protected $auto_cleanup = true;
	
	protected $images_path = "userfiles/images/";
	
	protected $files_path = "userfiles/files/";
	
	protected $display_method = false;
	
	public function setValue($value)
	{
		$this -> value = str_replace("\t", "", $value);
		
		if($this -> rich_text)
		{
			$search = array("'");
			$replace = array("&#039;");
			$this -> value = preg_replace("/<script([^>]*)>/i", "&lt;script$1&gt;", $this -> value);
			$this -> value = str_ireplace("</script>", "&lt;/script&gt;", $this -> value);
			
			if($this -> auto_cleanup)
				$this -> value = $this -> cleanupText($this -> value);
		}
		else 
		{
			$search = array("&", "'", "<", ">", '"');
			$replace = array("&amp;", "&#039;", "&lt;", "&gt;", "&quot;");
		}
		
		$this -> value = trim(str_ireplace($search, $replace, $this -> value));
		
		return $this;
	}
		
	public function displayHtml()
	{
		$arguments = func_get_args();
		$form_frontend = (isset($arguments[0]) && $arguments[0] == "frontend");
		
		$id = "textarea_".$this -> name;
		$this -> height = str_replace("px", "", $this -> height);
		
		$height = $this -> height ? $this -> height : 100;
		$height = "style=\"height: ".$height."px\" ";
		
		$css = $this -> addHtmlParams() ? $this -> addHtmlParams() : " class=\"form-textarea\"";
		
		$html = "<textarea ".$height."id=\"".$id."\" name=\"".$this -> name."\"".$css.">";
		$html .= $this -> value."</textarea>\n".$this -> addHelpText();
		
		if($this -> rich_text)
		{
			$height = $this -> height ? $this -> height : false;
			$html .= Editor :: instance($id, $height, $this -> images_path, $this -> files_path, $form_frontend);
		}
		
		return $html;
	}
	
	private function cleanupText($text)
	{
		$tags = "(p|li|div|span|strong|em|h1|h2|h3|h4|h5|h6)";
		
		$text = preg_replace("/(\s*&nbsp;\s*){2,}/", " ", $text);
		$text = preg_replace("/\s*(&nbsp;)?\s*<\/".$tags.">/", "</$2>", $text);
		$text = preg_replace("/<".$tags.">\s*(&nbsp;)?\s*/", "<$1>", $text);

		return $text;
	}
}
?>