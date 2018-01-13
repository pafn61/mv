<? 
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();
	
if(isset($_POST['model']) && $system -> registry -> checkModel($_POST['model']) && !empty($_FILES))
{
	$system -> runModel(strtolower($_POST['model']));
	$data_for_json = $system -> model -> uploadMultiImages(false);
}
else
	$data_for_json = array("error" => I18n :: locale('upload-file-error'));
	
echo json_encode($data_for_json);
?>