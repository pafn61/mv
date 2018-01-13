<?
class File_Model_Element extends Char_Model_Element
{
	protected $file_name;
	
	protected $allowed_extensions = array();
	
	protected $allowed_mime_types = array();
	
	protected $files_folder = false;
	
	protected $max_size = false;
	
	public function setAllowedExtensions($values)
	{
		if(is_array($values))
			$this -> allowed_extensions = $values;
 
		return $this;
	}
	
	public function setAllowedMimeTypes($values)
	{
		if(is_array($values))
			$this -> allowed_mime_types = $values;
 
		return $this;
	}
		
	public function validate()
	{
		if(!$this -> error)
		{
			$this -> unique = false;
			parent :: validate();
		}
			
		return $this;
	}
	
	public function defineFolderEnd()
	{
		$parts = explode('_', get_class($this));
		return strtolower($parts[0]).'s';
	}

	public function displayHtml()
	{
		$html = "";
		$arguments = func_get_args();
		$form_frontend = (isset($arguments[0]) && $arguments[0] == "frontend");

		if($this -> value && file_exists($this -> value))
		{
			$size = $this -> file_name ? " (".I18n :: convertFileSize(filesize($this -> value)).")" : "";
			
			$css_class = $form_frontend ? "file-params" : "file-input";
			
			$html = "<div class=\"".$css_class."\">\n<span class=\"file\">".$this -> file_name;
			$html .= $size."</span>\n";
			$html .= "<input type=\"hidden\" value=\"".$this -> file_name."-*//*-".$this -> value."\" ";
			$html .= "name=\"value-".$this -> name."\" />\n";
			
			if($form_frontend)
				$html .= "<span class=\"delete\">".I18n :: locale('delete')."</span>\n";
			else
				$html .= "<span class=\"delete\" title=\"".I18n :: locale('delete')."\"></span>\n";
						
			if(!$form_frontend)
			{
				$html .= "<p><a target=\"_blank\" class=\"download\" href=\"";
				$html .= Service :: removeDocumentRoot($this -> value)."\">";
				$html .= I18n :: locale('view-download')."</a></p>\n";
			}
			
			$html .= "</div>\n";
		}
		
		$css_class = ($this -> value && file_exists($this -> value)) ? ($form_frontend ? " hidden" : " no-display") : "";
		
		$html .= "<div class=\"file-input".$css_class."\"".$this -> addHtmlParams().">\n";
		$html .= "<input type=\"file\" name=\"".$this -> name."\" value=\"\" />\n";
		$html .= $this -> addHelpText()."</div>\n";
		
		return $html;
	}
	
	public function setRealValue($value, $file_name)
	{
		if(is_file($value))
		{
			$this -> value = $value;
			$this -> file_name = $file_name;
		}
		else
			$this -> value = $this -> file_name = "";
	}
	
	public function setValue($file_data)
	{
		if(!isset($file_data['name']) || !$file_data['name']) //File was not uploaded
		{
			$this -> value = $this -> file_name = "";
			return $this;
		}

		$image_type = (get_class($this) == 'Image_Model_Element');		
		$registry = Registry :: instance();
		$extension = Service :: getExtension($file_data['name']);
		
		$max_image_size = $this -> max_size ? $this -> max_size : $registry -> getSetting("MaxImageSize");
		$max_file_size = $this -> max_size ? $this -> max_size : $registry -> getSetting("MaxFileSize");

		if($image_type)
		{
			$max_image_width = $this -> max_width ? $this -> max_width : $registry -> getSetting("MaxImageWidth");
			$max_image_height = $this -> max_height ? $this -> max_height : $registry -> getSetting("MaxImageHeight");
		}
		
		if(isset($file_data['error']) && $file_data['error']) //File uploading error process
		{
			if($file_data['error'] == 1) //If file is too heavy
				if($image_type)
					$this -> error = $this -> chooseError("max_size", '{too-heavy-image}');
				else
					$this -> error = $this -> chooseError("max_size", '{too-heavy-file}');
		}		
		else if(count($this -> allowed_extensions) && !in_array($extension, $this -> allowed_extensions))
			$this -> error = $this -> chooseError("allowed_extensions", "{wrong-".($image_type ? "images" : "file")."-type}");
		else if(count($this -> allowed_mime_types) && !in_array($file_data['type'], $this -> allowed_mime_types))
			$this -> error = $this -> chooseError("allowed_mime_types", "{wrong-files-type}");
		else if($image_type)
		{
			$default_images_mimes = $registry -> getSetting('DefaultImagesMimeTypes');
			
			if(!count($this -> allowed_extensions) && !in_array($extension, $registry -> getSetting('AllowedImages')))
				$this -> error = '{wrong-images-type}';					
			else if(!count($this -> allowed_mime_types) && !in_array($file_data['type'], $default_images_mimes))
				$this -> error = '{wrong-files-type}';
			else if($file_data['size'] > $max_image_size)
				$this -> error = $this -> chooseError("max_size", '{too-heavy-image}');
			else
			{
				$size = @getimagesize($file_data['tmp_name']);
				
				//Takes size of image and checks for too big images
				if($size[0] > $max_image_width)
					$this -> error = $this -> chooseError("max_width", "{too-large-image}");
				else if($size[1] > $max_image_height)
					$this -> error = $this -> chooseError("max_height", "{too-large-image}");
		  	}
		}
	  	else if(!$image_type)
	  	{
			if(!count($this -> allowed_extensions) && !in_array($extension, $registry -> getSetting('AllowedFiles')))
				$this -> error = '{wrong-files-type}';
	  		else if($file_data['size'] > $max_file_size)
				$this -> error = $this -> chooseError("max_size", '{too-heavy-file}');
	  	}		
		
		if($this -> error) //If it was any type of error we don't copy the file and go back
			return;
			
		$initial_name = Service :: translateFileName($file_data['name']);
		$tmp_name = Service :: randomString(30); //New name of file
			
		$this -> file_name = $file_data['name']; //Pass the name of file
		
		if($initial_name) //Add name of file in latin letters
			$tmp_name = $initial_name."-".$tmp_name;
			
		//Path to copy the file
		$this -> value = $registry -> getSetting('FilesPath')."tmp/".$tmp_name.".".$extension;
		
		if(is_file($file_data['tmp_name']))
        	copy($file_data['tmp_name'], $this -> value); //Copy the file into temorary folder
        	
        return $this;
	}
			
	public function copyFile($model_name)
	{
		$arguments = func_get_args();
				
		//If we don't have the image file or its the alredy uploaded image
		if(!$this -> value || !is_file($this -> value))
			return;			
		else if(!preg_match("/\/tmp\/[^\/]+$/", $this -> value))
			return $this -> value;
		
		$registry = Registry :: instance();
	
		$no_model = (isset($arguments[1]) && $arguments[1] == "no-model"); //In case of frontend form without model
		$model_name = strtolower($model_name); //Name of current model
		
		//Folder to copy file
		if($no_model)
		{
			$folder = preg_replace("/^\/?(.*)\/?$/", "$1", $this -> files_folder);
			$path = $registry -> getSetting('FilesPath').$folder."/"; //Frontend form files
		}
		else //Admin panel file uploading
			$path = $registry -> getSetting('FilesPath')."models/".$model_name."-".$this -> defineFolderEnd()."/";
		
		$counter = intval($registry -> getDatabaseSetting('files_counter')) + 1;
		
		if(strpos(basename($this -> value), "-") !== false)
		{
			$new_value = Service :: removeExtension(basename($this -> value));
			$new_value = substr($new_value, 0, -31);
			$check_new_value = $path.$new_value.".".Service :: getExtension($this -> value);
			
			if(file_exists($check_new_value))
				$new_value = $path.$new_value."-f".$counter.".".Service :: getExtension($this -> value);
			else
				$new_value = $check_new_value;
		}
		else
			$new_value = $path."f".$counter.".".Service :: getExtension($this -> value); //Simple name of file
		
		if(!file_exists($new_value)) //If this file was not copied before
		{
			if(!is_dir($path)) //Makes the target folder if needed
				@mkdir($path);
				
			if(is_file($this -> value)) //Moves the file to the target folder
				@rename($this -> value, $new_value);
		}
		
		$registry -> setDatabaseSetting('files_counter', $counter);
		$this -> value = $new_value; //Passes the new value
		
		return $new_value;
	}

	public function deleteFile($file)
	{
		if(is_file($file))
			@unlink($file);			
	}
}
?>