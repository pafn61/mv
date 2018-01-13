<? 
class Multi_Images_Model_Element extends Char_Model_Element
{
	protected $max_size = false;
	
	protected $max_width = false;
	
	protected $max_height = false;
	
	protected $allowed_extensions = array();
	
	protected $allowed_mime_types = array();
	
	public function getOverriddenProperty($property)
	{
		$registry = Registry :: instance();
		
		$settings = array("max_size" => "MaxImageSize", 
						  "max_width" => "MaxImageWidth", 
						  "max_height" => "MaxImageHeight", 
						  "allowed_extensions" => "AllowedImages", 
						  "allowed_mime_types" => "DefaultImagesMimeTypes");
		
		if(isset($this -> $property) && array_key_exists($property, $settings))
			if(is_array($this -> $property))
				return count($this -> $property) ? $this -> $property : $registry -> getSetting($settings[$property]);
			else
				return $this -> $property ? $this -> $property : $registry -> getSetting($settings[$property]);
	}
	
	public function validate()
	{
		$this -> unique = false;
		parent :: validate();

		$images = explode("-*//*-", $this -> value);
		$checked = array();
		
		foreach($images as $image)
		{
			if(preg_match("/\(\*.*\*\)$/", $image))
			{
				$comment = preg_replace("/.*(\(\*.*\*\))$/", "$1" , $image);
				$image = preg_replace("/\(\*.*\*\)$/", "" , $image);		
			}
			else
				$comment = "";
				
			if(is_file($image) && @getimagesize($image))
				$checked[] = $image.$comment;
		}
				
		$this -> value = implode("-*//*-", $checked);
		
		return $this; 
	}
	
	public function displayHtml()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0] == "frontend")
			return "<input type=\"file\" name=\"".$this -> name."\" id=\"multi-images-".$this -> name."\" />\n";
		
		$html = "";		
		$registry = Registry :: instance();
		$html .= "<div class=\"images-area\" id=\"area-images-".$this -> name."\">\n";
		
		if(!$this -> value)
			$html .= "<p class=\"no-images\">".I18n :: locale('no-images')."</p>\n";

		$html .= "<div class=\"uploaded-images\">\n";		
		$images = explode("-*//*-", $this -> value);
		
		if($this -> value)
			foreach($images as $image)
			{
				if(preg_match("/\(\*.*\*\)$/", $image))
				{
					$comment = preg_replace("/.*\(\*(.*)\*\)$/", "$1" , $image);
					$image = preg_replace("/\(\*.*\*\)$/", "" , $image);		
				}
				else
					$comment = "";
				
				if(is_file($image))
				{
					$imager = new Imager();
					$src = $imager -> compress($image, 'admin_multi', 120, 89);
					$html .= "<div class=\"images-wrapper\">\n";
					$html .= "<div class=\"controls\" id=\"".$image."\">\n";
					$html .= "<span class=\"first\" title=\"".I18n :: locale("move-first")."\"></span> ";
					$html .= "<span class=\"left\" title=\"".I18n :: locale("move-left")."\"></span>";
					$html .= "<span class=\"right\" title=\"".I18n :: locale("move-right")."\"></span> ";
					$html .= "<span class=\"last\" title=\"".I18n :: locale("move-last")."\"></span>";
					$html .= "<span class=\"comment\" title=\"".I18n :: locale("add-edit-comment")."\"></span>";
					$html .= "<span class=\"delete\" title=\"".I18n :: locale("delete")."\"></span>";					
					$html .= "</div>\n";
					$html .= "<a href=\"".$registry -> getSetting("MainPath");
					$html .= Service :: removeFileRoot($image)."\" target=\"_blank\">\n";
					$html .= "<img src=\"".$src."\" alt=\"".basename($image)."\"";
					$html .= $comment ? " title=\"".$comment."\"" : "";
					$html .= " /></a></div>\n";
				}
			}
		
		$html .= "</div>\n";
		
		$html .= "<div class=\"upload-buttons\">\n<div class=\"upload-one\">";
		$html .= "<p class=\"upload-text\">".I18n :: locale('upload-image')."</p>\n";
		$html .= "<input type=\"file\" id=\"multi-images-".$this -> name."\" name=\"multi-images-".$this -> name."\" />\n";
		$html .= "<div class=\"loading\"></div>\n</div>\n";
		$html .= "<div class=\"upload-many\" id=\"".$this -> name."-".session_id()."\">\n";
		$html .= "<p class=\"upload-text\">".I18n :: locale('multiple-upload')."</p>\n";
		$html .= "<input id=\"".$this -> name."-upload\" class=\"multiple-upload\" type=\"file\" multiple=\"true\" />\n";
		$html .= "<div class=\"stop-upload\"><input type=\"button\" class=\"button-dark\" ";
		$html .= "value=\"".I18n :: locale('stop-upload')."\" /></div>\n";
		$html .= "<p class=\"upload-text about-flash\">".I18n :: locale('about-flash-player')."</p>\n</div>\n";
		$html .= "<div class=\"clear\"><input type=\"hidden\" name=\"".$this -> name."\" value=\"".$this -> value."\" /></div>\n";
		$html .= "</div></div>\n".$this -> addHelpText();
		
		return $html;
	}
	
	public function uploadImage($file_data, $value)
	{
		$result = array();
		$input_value = explode("-*//*-", $value);
		$arguments = func_get_args();
		$multiple_upload = (isset($arguments[2]) && $arguments[2] == "multiple");
		
		$registry = Registry :: instance();
		$extention = Service :: getExtension($file_data['name']);
		
		if(!in_array($extention, $this -> getOverriddenProperty("allowed_extensions")) ||
		   (!$multiple_upload && !in_array($file_data['type'], $this -> getOverriddenProperty("allowed_mime_types"))))
		{
			$this -> error = "wrong-images-type";
			return;
		}
		
		if((isset($file_data['error']) && $file_data['error'] == 1) || 
		   (isset($file_data['size']) && $file_data['size'] > $this -> getOverriddenProperty("max_size")))
		{
			$this -> error = "too-heavy-image";
			return;
		}
		
		$size = @getimagesize($file_data['tmp_name']);
			
		//Takes size of image and checks for too big images
		if(!$size || $size[0] > $this -> getOverriddenProperty("max_width") || 
			$size[1] > $this -> getOverriddenProperty("max_height"))
		{
			$this -> error = "too-large-image";
			return;
		}
		else
		{
			$initial_name = Service :: translateFileName($file_data['name']);
			$tmp_name = Service :: randomString(30); //New name of file
			
			if($initial_name) //Add name of file in latin letters
				$tmp_name = $initial_name."-".$tmp_name;
			
			$tmp_name = $registry -> getSetting('FilesPath')."tmp/".$tmp_name.".".$extention;
           	copy($file_data['tmp_name'], $tmp_name); //Copy the file
           	
           	foreach($input_value as $image) //Checks if its a file with no comment
           		if(is_file(preg_replace("/\(\*.*\*\)$/", "" , $image)))
           			$result[] = $image;

           	$result[] = $tmp_name;
           	$input_value = implode("-*//*-", $result);
           	
           	return array($tmp_name, $input_value);
		}
	}

	public function copyImages($model_name)
	{
		clearstatcache();
		
		$arguments = func_get_args();
		
		if(isset($arguments[1]) && $arguments[1])
			$old_images = explode("-*//*-", $arguments[1]); //Old images when updating the record
		else
			$old_images = array();
			
		$images = explode("-*//*-", $this -> value);
		
		$registry = Registry :: instance();	
		$model_name = strtolower($model_name); //Name of current model
		
		$path = $registry -> getSetting('FilesPath')."models/".$model_name."-images/"; //Folder to copy file
		$tmp_path = $registry -> getSetting('FilesPath')."tmp/";
		$counter = intval($registry -> getDatabaseSetting('files_counter'));
		
		$moved_images = array();
		
		if(!is_dir($path)) 
			@mkdir($path);
				
		foreach($images as $image)
		{
			if(preg_match("/\(\*.*\*\)$/", $image))
			{
				$comment = preg_replace("/.*(\(\*.*\*\))$/", "$1" , $image);
				$image = preg_replace("/\(\*.*\*\)$/", "" , $image);		
			}
			else
				$comment = "";
				
			if(strpos($image, $tmp_path) !== false && is_file($image)) //If image is located in temporary folder
			{
				if(strpos(basename($image), "-") !== false)
				{
					$new_value = Service :: removeExtension(basename($image));
					$new_value = substr($new_value, 0, -31);
					$check_new_value = $path.$new_value.".".Service :: getExtension($image);
			
					if(file_exists($check_new_value))
						$moved_image = $path.$new_value."-f".(++ $counter).".".Service :: getExtension($image);
					else
						$moved_image = $check_new_value;					
				}
				else
					$moved_image = $path."f".(++ $counter).".".Service :: getExtension(basename($image));
				
				if(!is_file($moved_image)) //Moves the file into model folder
					@rename($image, $moved_image);
						
				$moved_images[] = $moved_image.$comment;
			}
			else if(strpos($image, $path) !== false && is_file($image))
				$moved_images[] = $image.$comment; //Image already uploaded
			else
			{
				$image = Service :: addFileRoot($image);
				
				if(is_file($image))
					$moved_images[] = $image.$comment;
			}
		}
		
		foreach($moved_images as $key => $image) //Cuts off the file root
			$moved_images[$key] = Service :: removeFileRoot($image);
				
		$this -> value = implode("-*//*-", $moved_images);
		$registry -> setDatabaseSetting('files_counter', $counter);
	}
	
	public function deleteImages($images)
	{
		$images = explode("-*//*-", $images);
		
		foreach($images as $image)
		{
			$image = preg_replace("/\(\*[^*]*\*\)/", "", $image);
			$image = Service :: addFileRoot($image);
			
			if(is_file($image))
				@unlink($image);
		}			
	}
	
	public function setValuesWithRoot($images)
	{
		$images = explode("-*//*-", $images);
		
		foreach($images as $key => $image) //Add current system file root for images
		{
			$file = Service :: addFileRoot($image);
			
			if(is_file(preg_replace("/\(\*.*\*\)$/", "" , $file)))
				$images[$key] = $file;
			else
				unset($images[$key]);
		}
			
		$this -> value = implode("-*//*-", $images);
	}
}
?>