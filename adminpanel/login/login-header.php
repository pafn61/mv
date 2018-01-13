<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex, nofollow" />
<title><? echo I18n :: locale("mv"); ?></title>
<link rel="stylesheet" type="text/css" href="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/css/style-login.css" />

<link rel="icon" href="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/images/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/images/favicon.ico" type="image/x-icon" />

<?
if(preg_match("/\/loading\.php/", $_SERVER['REQUEST_URI']))
{
	$url = $registry -> getSetting("AdminPanelPath");
	
	if(isset($_GET["back-path"]) && $_GET["back-path"])
		$url .= base64_decode(trim($_GET["back-path"]));

	echo "<meta http-equiv=\"refresh\" content=\"1; URL=".$url."\" />\n";
}
	
if(stripos($_SERVER['REQUEST_URI'], "/login/error.php") === false)
	include $registry -> getSetting("IncludeAdminPath")."includes/noscript.php";	
?>

<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/jquery.js"></script>
<script type="text/javascript">
$(document).ready(function()
{
   $("#select-login-region").change(function() 
   { 
      location.href = "<? echo $registry -> getSetting('AdminPanelPath'); ?>login/?region=" + $(this).val(); 
   });
});   
</script>
</head>
<body>