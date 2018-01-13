<?
/**
 * Parent for Record class, contains base methods for child class.  
 */
class Content
{
	//Id of record in db
	protected $id;
	
	//Array of cells values of db record
	protected $content = array();
	
	//Values of enum type fields
	protected $enum_values = array();
	
	//Object of model (kind of a parent of current record)
	protected $model;		
	
	public function __construct()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && is_array($arguments[0]))
			$this -> passContent($arguments[0]);
	}
	
	public function passContent($content)
	{
		if(is_array($content))
		{
			$this -> content = $content;
			
			if(isset($content['id']) && $content['id'])
				$this -> id = intval($content['id']);
		}
			
		return $this;
	}
	
	public function getId()
	{
		return $this -> id;
	}
	
	public function getValue($key)
	{
		if(isset($this -> content[$key]))
			return $this -> content[$key];
	}
	
	public function setValue($key, $value)
	{
		if(isset($this -> content[$key]))
			$this -> content[$key] = $value;
			
		return $this;
	}
	
	public function defineUrl($first_part)
	{
		$registry = Registry :: instance();
		$url = $registry -> getSetting("MainPath").$first_part."/";
		
		$arguments = func_get_args();
		
		$url_field = isset($arguments[1], $this -> content[$arguments[1]]) ? $arguments[1] : false;
		
		if($url_field && $this -> content[$url_field])
			return $url.$this -> content[$url_field]."/";
		else
			return $url.$this -> id."/";
	}
	
	public function extractImages($field)
	{
		if(!isset($this -> content[$field]) || !$this -> content[$field])
			return array();
			
		$arguments = func_get_args();
		$argument = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
			
		return $this -> model -> extractImages($this -> content[$field], $argument);	
	}
	
	public function getFirstImage($field)
	{
		return isset($this -> content[$field]) ? $this -> model -> getFirstImage($this -> content[$field]) : "";
	}
	
	public function combineImages($field, $images)
	{
		$result_images = array();
		
		if(count($images))
			if(isset($images[0]))
				$result_images = $images;
			else
				foreach($images as $image => $comment)
					$result_images[] = $comment ? $image."(*".preg_replace("/(\r)?\n/", "", $comment)."*)" : $image;
		
		if(isset($this -> content[$field]))
			$this -> content[$field] = implode("-*//*-", $result_images);
		
		return $this;
	}
	
	public function displayImage($field)
	{
		$arguments = func_get_args();
		$registry = Registry :: instance();
		
		$alt = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : "";
		$no_image_text = (isset($arguments[2]) && $arguments[2]) ? $arguments[2] : false;
		
		if(isset($this -> content[$field]) && is_file(Service :: addFileRoot($this -> content[$field])))
			return "<img src=\"".$registry -> getSetting("MainPath").$this -> content[$field]."\" alt=\"".$alt."\" />\n";
		else if($no_image_text)
			return $no_image_text;
	}
	
	public function resizeImage($field, $width, $height)
	{
		if(!isset($this -> content[$field]) || !$this -> content[$field])
			return;
			
		$arguments = func_get_args();
		$alt_text = (isset($arguments[3]) && $arguments[3]) ? $arguments[3] : "";
		$no_image_text = (isset($arguments[4]) && $arguments[4]) ? $arguments[4] : "";
		
		return $this -> model -> resizeImage($this -> content[$field], $width, $height, $alt_text, $no_image_text);
	}
	
	public function cropImage($field, $width, $height)
	{
		if(!isset($this -> content[$field]) || !$this -> content[$field])
			return;
			
		$arguments = func_get_args();
		$alt_text = (isset($arguments[3]) && $arguments[3]) ? $arguments[3] : "";
		$no_image_text = (isset($arguments[4]) && $arguments[4]) ? $arguments[4] : "";
		
		return $this -> model -> cropImage($this -> content[$field], $width, $height, $alt_text, $no_image_text);
	}	
	
	public function displayFileLink($field)
	{
		$arguments = func_get_args();
		$registry = Registry :: instance();
		
		$link_text = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
		$no_file_text = (isset($arguments[2]) && $arguments[2]) ? $arguments[2] : false;
		
		if(isset($this -> content[$field]) && is_file(Service :: addFileRoot($this -> content[$field])))
		{
			$link_text = $link_text ? $link_text : basename($this -> content[$field]);
			return "<a target=\"_blank\" href=\"".$registry -> getSetting("MainPath").$this -> content[$field]."\">".$link_text."</a>\n";
		}
		else if($no_file_text)
			return $no_file_text;
	}
	
	public function wrapInParagraphs($field)
	{
		if(isset($this -> content[$field]) && trim($this -> content[$field]))
			return "<p>".str_replace("<br />", "</p>\n<p>", nl2br($this -> content[$field]))."</p>\n";
	}	
}
?>