<? 
class Image_Model_Element extends File_Model_Element
{
	protected $max_width = false;
	
	protected $max_height = false;
	
	protected $form_preview_width = false;
	
	protected $form_preview_height = false;
	
	public function displayHtml()
	{
		$html = "";
		$arguments = func_get_args();
		$form_frontend = (isset($arguments[0]) && $arguments[0] == "frontend");
		
		$preview_width = ($form_frontend && $this -> form_preview_width) ? $this -> form_preview_width : 150;
		$preview_height = ($form_frontend && $this -> form_preview_height) ? $this -> form_preview_height : 111;
		$preview_folder = $form_frontend ? $this -> model."_".$preview_width.'x'.$preview_height : 'admin_record';
				
		if($this -> value && file_exists($this -> value))
		{
			$imager = new Imager();
			$src = $imager -> compress($this -> value, $preview_folder, $preview_width, $preview_height);
			$size = getimagesize($this -> value);
			
			$html .= "<div class=\"".($form_frontend ? "file-params" : "image-input")."\">\n";
			
			if($form_frontend)
			{
				$html .= "<img src=\"".$src."\" alt=\"".$this -> file_name."\" />\n";
				$html .= "<p><span class=\"file\">".$this -> file_name."</span>\n";
				$html .= "<span class=\"delete\">".I18n :: locale('delete')."</span></p>\n";
			}
			else
			{
				$html .= "<div class=\"picture\">\n";
				$html .= "<img src=\"".$src."\" alt=\"".$this -> file_name."\" />\n</div>\n";
				$html .= "<div class=\"params\">\n";
				$html .= "<div class=\"name\">".$this -> file_name."</div>\n";
				$html .= "<span class=\"delete\" title=\"".I18n :: locale('delete')."\"></span>\n";
				$html .= "<p>".I18n :: locale('size').": ".I18n :: convertFileSize(filesize($this -> value))."</p>\n";
				$html .= "<p>".I18n :: locale('width').": ".$size[0]."</p>\n";
				$html .= "<p>".I18n :: locale('height').": ".$size[1]."</p>\n";
				$html .= "<p><a target=\"_blank\" class=\"download\" href=\"";
				$html .= Service :: removeDocumentRoot($this -> value)."\">";
				$html .= I18n :: locale('view-download')."</a></p>\n";
			}
			
			$html .= "<input type=\"hidden\" value=\"".$this -> file_name."-*//*-".$this -> value."\" ";
			$html .= "name=\"value-".$this -> name."\" />\n</div>\n";
			$html .= $form_frontend ? "" : "</div>\n";
		}
		
		$css_class = ($this -> value && file_exists($this -> value)) ? ($form_frontend ? " hidden" : " no-display") : "";
		
		$html .= "<div class=\"image-input".$css_class."\"".$this -> addHtmlParams().">\n";
		$html .= "<input type=\"file\" name=\"".$this -> name."\" value=\"\" />\n";
		$html .= $this -> addHelpText()."</div>\n";
		
		return $html;
	}
}
?>