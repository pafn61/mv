<?
include_once "../../config/autoload.php";
$registry = Registry :: instance();

$frontend_file_upload = true;
include $registry -> getSetting("IncludeAdminPath")."controls/upload.php";
?>