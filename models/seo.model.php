<?
class Seo extends Model_Simple
{
	protected $name = "SEO параметры";
   
	protected $model_elements = array(
		array("Заголовок", "char", "title", array("help_text" => "Значение заголовка (title) по умолчанию для всех страниц")),
		array("Ключевые слова", "text", "keywords", array("help_text" => "Ключевые слова (meta keywords) по умолчанию для всех страниц")),
      	array("Описание", "text", "description", array("help_text" => "Описание (meta description) по умолчанию для всех страниц")),
      	array("Robots.txt", "text", "robots", array("help_text" => "Содержимое файла robots.txt, файл создается и обновляется автоматически")),
		array("Meta данные в head", "text", "meta_head", array("height" => 250, "help_text" => "Meta тэги, счетчики, плагины")),
		array("Meta данные в footer", "text", "meta_footer", array("height" => 250, "help_text" => "Счетчики и плагины"))
	);
   
	public function update()
	{
		parent :: update();
   		
   		$file = $this -> registry -> getSetting("IncludePath")."robots.txt";
   		$text = $this -> getValue("robots"); 
		$text = preg_split("/(\\r)*(\\n)*<br(\s\/)?>(\\r)*(\\n)*/", nl2br($text));

		@unlink($file);
   		
   		if($handle = @fopen($file, "wt"))
   		{
   			@chmod($file, 0777);
   			
   			foreach($text as $string)
   			    fwrite($handle, $string."\r\n");
   			
   			fclose($handle);
   		}
   		
   		return $this;
	}
   
	public function mergeParams($content)
	{
   		if(!$this -> data_loaded)
   			$this -> getDataFromDb();
   		
   		if(!$content) return;
   		
   		$seo_fields = array("title", "keywords", "description");
   		$arguments = func_get_args();
   		$text_value = false;
   		
   		if(count($arguments) == 1 && !is_object($content))
   			$text_value = $content;
   		
   		$name_field = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
   		
		foreach($seo_fields as $field)
  			if(is_object($content) && $content -> $field)
				$this -> data[$field] = $content -> $field;
			else if($name_field || $text_value)
			{
				if(!isset($this -> data[$field]))
					$this -> data[$field] = "";
					
				if(!$text_value && $content -> $name_field)
					$text_value = $content -> $name_field;
				
				if(!$text_value)
					continue;
				
				if($field == "keywords")
				{
					if($this -> data[$field])
						$this -> data[$field] = $text_value.", ".$this -> data[$field];
					else
						$this -> data[$field] = $text_value;
				}
				else
				{
					if($this -> data[$field])
						$this -> data[$field] = $text_value." ".$this -> data[$field];
					else 
						$this -> data[$field] = $text_value;
				}
			}
		   		
		return $this;
	}
	
	public function displayMetaData($type)
	{
		if($type == "head" && $this -> getValue("meta_head"))
			return htmlspecialchars_decode($this -> getValue("meta_head"), ENT_QUOTES);
		else if($type == "footer" && $this -> getValue("meta_footer"))
			return htmlspecialchars_decode($this -> getValue("meta_footer"), ENT_QUOTES);
	}
}
?>