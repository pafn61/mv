<?
/**
 * WW editor class, created to add editor to the textareas in admin panel.
 */
class Editor
{
   private static $instance = false;
   
   static public function instance($id, $height, $images_folder, $files_folder, $form_frontend)
   {
   	  $html = "";
   	  $registry = Registry :: instance();
   	  $max_height = $height ? $height : "false";
   	  $min_height = $height ? $height : 200;
   	  $region = $registry -> getSetting('Region');
   
   	  if(!self :: $instance)
   	  {
   	  		$path = $registry -> getSetting("AdminPanelPath")."interface/redactor/";
   	  		
        	$html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$path."redactor.css\" />\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."redactor.js\"></script>\n";        	
        	$html .= "<script type=\"text/javascript\" src=\"".$path."fullscreen.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."fontcolor.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."fontsize.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."fontfamily.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."filemanager.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."imagemanager.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."table.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."video.js\"></script>\n";
        	$html .= "<script type=\"text/javascript\" src=\"".$path."langs/".$region.".js\"></script>\n";
         
         	self :: $instance = true;   	  	
   	  }
   	  
   	  $images_params = "?folder=".$images_folder."&type=image";
   	  $files_params = "?folder=".$files_folder."&type=file";
   	     	  
   	  if($form_frontend)
   	  {
   	  	 $upload_path = $registry -> getSetting("MainPath")."extra/upload/";
   	  	 
   	  	 $images_params .= "&code=".md5($images_folder.$registry -> getSetting("SecretCode"));
   	  	 $files_params .= "&code=".md5($files_folder.$registry -> getSetting("SecretCode"));
   	  }
   	  else
   	  	 $upload_path = $registry -> getSetting("AdminPanelPath")."controls/upload.php";
   	  	 
   	  $tmp_path = $registry -> getSetting("MainPath")."userfiles/tmp/";
   	  
   	  $html .= "
	      <script type=\"text/javascript\"> 
	      	$(document).ready(function() { $(\"#".$id."\").redactor({ 
	      	               lang: '".$region."', 
	      	               convertDivs: false,
						   replaceDivs: false,
						   deniedTags: ['script'],
						   removeWithoutAttr: [],
						   toolbarFixedBox: true,
	      	               minHeight: ".$min_height.",
	      	               maxHeight: ".$max_height.",
	      	               plugins: ['fullscreen','fontcolor','fontfamily','fontsize','table','video','filemanager','imagemanager'],
	      	               dragUpload: false,
	      	               imageUpload: '".$upload_path.$images_params."',
	      	               fileUpload: '".$upload_path.$files_params."',
	      	               fileManagerJson: '".$tmp_path."files.json',
	      	               imageManagerJson: '".$tmp_path."images.json',
	      	               imageUploadParam: 'any_file',
	      	               fileUploadParam: 'any_file',
	      	               imageUploadErrorCallback: function(json)
	      	               {
	      	               		if(typeof(json.error) != 'undefined')
	      	               			if(typeof(dialogs) != 'undefined')
	      	               				dialogs.showAlertMessage(json.error);
	      	               			else
	      	               				alert(json.error);
                           },
                           fileUploadErrorCallback: function(json)
	      	               {
	      	               		if(typeof(json.error) != 'undefined')
	      	               			if(typeof(dialogs) != 'undefined')
	      	               				dialogs.showAlertMessage(json.error);
	      	               			else
	      	               				alert(json.error);
                           }
   						}); }); 
	      </script>\n";
   	  
   	  self :: createFilesJSON();
   	  self :: createImagesJSON();

      return $html;
   }
   
   static public function createFilesJSON()
   {
   	   $registry = Registry :: instance();
   	   $path = $registry -> getSetting("FilesPath")."tmp/files.json";
   	   $folder = $registry -> getSetting("FilesPath")."files/";
   	   $url = $registry -> getSetting("MainPath")."userfiles/files/";
   	   $json = array();
   	   
   		clearstatcache();
		
		$directory = @opendir($folder);
		
		if($directory)
			while(false !== ($file = readdir($directory)))
			{
				if($file == "." || $file == "..")
					continue;
				
				if(is_file($folder.$file))
				{
					$json[] = array("name" => "",
									"title" => $file,
									"link" => $url.$file, 
									"size" => I18n :: convertFileSize(filesize($folder.$file)));	
				}
			}
				
		file_put_contents($path, json_encode($json));
   }
   
   static public function createImagesJSON()
   {
   	   $registry = Registry :: instance();
   	   $path = $registry -> getSetting("FilesPath")."tmp/images.json";
   	   $folder = $registry -> getSetting("FilesPath")."images/";
   	   $url = $registry -> getSetting("MainPath")."userfiles/images/";
   	   $json = array();
   	   
   		clearstatcache();
		
		$directory = @opendir($folder);
		$imager = new Imager();
		
		if($directory)
			while(false !== ($file = readdir($directory)))
			{
				if($file == "." || $file == "..")
					continue;
				
				if(is_file($folder.$file))
				{
					$extension = Service :: getExtension($file);
					
					if(!in_array($extension, $registry -> getSetting("AllowedImages")))
						continue;
					
					$tmp_name = $registry -> getSetting("FilesPath")."tmp/".$file;
					$thumb_name = $registry -> getSetting("FilesPath")."tmp/redactor/".$file;
					
					if(!is_file($thumb_name))
					{
						$imager -> setImage($folder.$file);
						@copy($folder.$file, $tmp_name);
						$thumb_name = $imager -> compress($tmp_name, "redactor", 100, 75);
						@unlink($tmp_name);
					}
					
					$json[] = array("thumb" => Service :: removeDocumentRoot($thumb_name),
									"image" => $url.$file,
									"title" => $file);
				}
				
			}
				
		file_put_contents($path, json_encode($json));
   }
}
?>