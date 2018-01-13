<?
include "../../config/autoload.php";

if(isset($_POST['code']) && $_POST['code'])
	session_id($_POST['code']);

$system = new System('ajax');	
$field = isset($_POST['field']) ? trim($_POST['field']) : false;

if(!$field || !is_object($system -> user) || !$system -> user -> checkUserLoginFlashUpload())
	exit();

$_POST[$field] = "";

if(isset($_POST['model']) && $system -> registry -> checkModel($_POST['model']) && !empty($_FILES))
{
	$system -> runModel(strtolower($_POST['model']));
	$result = $system -> model -> uploadMultiImages("multiple");
	
	echo json_encode($result);
}
?>