<?
/**
 * MV - content management framework for developing internet sites and applications.
 * Released under the terms of BSD License.
 * http://mv-framework.ru
 */

//MV auto loader
require_once "setup.php";
require_once "settings.php";
require_once "models.php";
require_once "plugins.php";

//Path to include files
if(isset($mvSetupSettings["UseServerDocumentRootForIncludes"]) && $mvSetupSettings["UseServerDocumentRootForIncludes"])
	$mvSetupSettings["DocumentRoot"] = preg_replace("/\/$/", "", $_SERVER["DOCUMENT_ROOT"]);
else
{
	$mvSetupSettings["DocumentRoot"] = str_replace("\\", "/", dirname(__FILE__));
	$mvSetupSettings["DocumentRoot"] = str_replace($mvSetupSettings["MainPath"]."config", "", $mvSetupSettings["DocumentRoot"]);
}

$mvSetupSettings["IncludePath"] = $mvSetupSettings["DocumentRoot"].$mvSetupSettings["MainPath"];
$mvSetupSettings["FilesPath"] = $mvSetupSettings["IncludePath"].$mvSetupSettings["FilesPath"]."/";

//Path to include files in admin panel, must start and end with "/"
$mvSetupSettings["IncludeAdminPath"] = $mvSetupSettings["IncludePath"].$mvSetupSettings["AdminFolder"]."/"; 

//Admin panel location path form root folder must start and end with "/"
$mvSetupSettings["AdminPanelPath"] = $mvSetupSettings["MainPath"].$mvSetupSettings["AdminFolder"]."/";

$mvSetupSettings["Models"] = $mvActiveModels;
$mvSetupSettings["Plugins"] = $mvActivePlugins;

//Class auto loader
function autoloadMV($class_name)
{
    global $mvSetupSettings;
	
	$class_name = strtolower($class_name);
	
	if(strpos($class_name, "swift_") === 0)
		return;
	
	if(strpos($class_name, "model_element") !== false)
	{
		$class_name = str_replace("_model_element", "", $class_name);
		require_once $mvSetupSettings["IncludePath"]."core/datatypes/".$class_name.".type.php";
	}
	else if(in_array($class_name, $mvSetupSettings["Models"]))
		require_once $mvSetupSettings["IncludePath"]."models/".$class_name.".model.php";
	else if(in_array($class_name, $mvSetupSettings["Plugins"]))
		require_once $mvSetupSettings["IncludePath"]."plugins/".$class_name.".plugin.php";		
	else
		require_once $mvSetupSettings["IncludePath"]."core/".$class_name.".class.php";
}

spl_autoload_register("autoloadMV");

//Loads all settings into Registry to get them in any place
$registry = Registry :: instance();
$registry -> loadSettings($mvSetupSettings);
$registry -> loadSettings($mvMainSettings);

$i18n = I18n :: instance();
$i18n -> setRegion($mvSetupSettings["Region"]);

function errorHandlerMV($type, $message, $file, $line)
{
	Log :: add($message." in line ".$line." of file ".$file);
}
	
function fatalErrorHandlerMV()
{
	$registry = Registry :: instance();

	if(!$registry -> getSetting("ErrorAlreadyLogged"))
		if($error = error_get_last())
			Log :: add("Error! ".$error["message"]." in line ".$error["line"]." of file ".$error["file"]);
}

if($mvSetupSettings["Mode"] == "development")
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
else
{
  	error_reporting(0);
  	ini_set('display_errors', 0);

  	register_shutdown_function("fatalErrorHandlerMV");
	set_error_handler("errorHandlerMV");  	
}

if(isset($mvSetupSettings["HttpOnlyCookie"]) && $mvSetupSettings["HttpOnlyCookie"])
	ini_set('session.cookie_httponly', 1);

session_set_cookie_params(0, $mvSetupSettings["MainPath"]);

ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('magic_quotes_gpc', 0);
ini_set('magic_quotes_sybase', 0);
ini_set('magic_quotes_runtime', 0);
ini_set("register_long_arrays", 0);

//Important initial includes
require_once $mvSetupSettings["IncludePath"]."core/datatypes/base.type.php";
require_once $mvSetupSettings["IncludePath"]."core/log.class.php";
?>