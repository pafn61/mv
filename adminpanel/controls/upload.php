<?
include_once "../../config/autoload.php";
$registry = Registry :: instance();
$i18n = I18n :: instance();

if(!isset($_GET["folder"], $_GET["type"]) || !preg_match("/^userfiles\/[^\/]+.*\/$/", $_GET["folder"]) || 
	($_GET["type"] != "image" && $_GET["type"] != "file"))
	exit(json_encode(array("error" => $i18n -> locale("error-failed"))));

if(!isset($frontend_file_upload))
	$system = new System("ajax");
else
{
	$code = isset($_GET["code"]) ? $_GET["code"] : "";
	
	if(!$code || $code != md5($_GET["folder"].$registry -> getSetting("SecretCode")))
		exit(json_encode(array("error" => $i18n -> locale("error-failed"))));
}

//Defines path to save files and images from WW editor
$folder = $registry -> getSetting('IncludePath').trim($_GET["folder"]);
$type = ($_GET["type"] == "file") ? "file" : "image";
$error = false;

if(!isset($_FILES['any_file'], $_FILES['any_file']['name']) || !$_FILES['any_file']['name'])
	$error = "upload-file-error";

if(isset($_FILES['any_file']['error']) && $_FILES['any_file']['error'])
	if($_FILES['any_file']['error'] == 1)
		$error = 'too-heavy-file';

if($type == "image" && !$error) //Upload only image file here
{	
	$path = $folder.$_FILES['any_file']['name'];
	$extention = Service :: getExtension($_FILES['any_file']['name']);
	$allowed_extentions = $registry -> getSetting('AllowedImages');
	
	if(!in_array($extention, $allowed_extentions)) //Checks exttension of image file
		$error = "wrong-images-type";
	else if($_FILES['any_file']['size'] > $registry -> getSetting('MaxImageSize'))
		$error = "too-heavy-image-editor";
	else
	{
		$size = @getimagesize($_FILES['any_file']['tmp_name']);
	
		//Takes size of image and checks for too big images
		if($size[0] > $registry -> getSetting('MaxImageWidth') || $size[1] > $registry -> getSetting('MaxImageHeight'))
			$error = "too-large-image-editor";
	}
}
else if($type == "file" && !$error) //Upload any allowed file
{
	$path = $folder.$_FILES['any_file']['name'];
	$extention = Service :: getExtension($_FILES['any_file']['name']);
	$allowed_extentions = $registry -> getSetting('AllowedFiles');
	
	if(!in_array($extention, $allowed_extentions))
		$error = "wrong-filemanager-type";
	else if($_FILES['any_file']['size'] > $registry -> getSetting('MaxFileSize'))
		$error = "too-heavy-file";
}
else 
	$error = "upload-file-error";
	
if(!$path)
	echo json_encode(array("error" => $i18n -> locale("error-failed")));

//If its allowed file and all is ok
if(!$error && $type)
{
	$folder = dirname($path);
	
	if(!is_dir($folder) && preg_match("/\/userfiles\/.+/", $folder))
		mkdir($folder);

	$path = Service :: prepareFilePath($path);
	
	copy($_FILES['any_file']['tmp_name'], $path); //Copy the file
	$result = array("filelink" => Service :: removeDocumentRoot($path), "filename" => basename($path));

	echo stripslashes(json_encode($result));
	
	Editor :: createFilesJSON();
	Editor :: createImagesJSON();
}
else if($error) //If it was error we give it back
{
	$arguments = array();
	
	switch($error) //Extra translation for errors
	{
		case 'wrong-images-type': $arguments['formats'] = implode(', ', $registry -> getSetting("AllowedImages"));
			break;
		case 'too-heavy-file': $arguments['weight'] = I18n :: convertFileSize($registry -> getSetting("MaxFileSize"));
			break;
		case 'too-heavy-image-editor': $arguments['weight'] = I18n :: convertFileSize($registry -> getSetting("MaxImageSize"));
			break;
		case 'too-large-image-editor':
								$arguments['size'] = $registry -> getSetting("MaxImageWidth")." x ";
								$arguments['size'] .= $registry -> getSetting("MaxImageHeight");
			break;
	}
			
	echo json_encode(array("error" => $i18n -> locale($error, $arguments)));
}
?>