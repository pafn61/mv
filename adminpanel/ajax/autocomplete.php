<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

if(isset($_POST['action'], $_POST['string']) && $_POST['action'] == "translit")
{
	echo I18n :: translitUrl(trim($_POST['string']));
	exit();
}
		
if(isset($_GET['model'], $_GET['field'], $_GET['query']) && $system -> registry -> checkModel($_GET['model']))
{
	$system -> runModel($_GET['model']);
	$request = htmlspecialchars(trim($_GET['query']), ENT_QUOTES);
	$object = $system -> model -> getElement($_GET['field']);
	
	if($object)
	{
		if($object -> getType() == "parent")
		{			
			$object -> setSelfModel(get_class($system -> model));
			
			if(isset($_GET['id']))
			{				
				$object -> setSelfId(intval($_GET['id'])) -> getAvailbleParents($system -> model -> getTable());				
				$result = $object -> getDataForAutocomplete($request, $system -> db);
			}
			else if(isset($_GET['ids']) && $_GET['ids'])
			{
				$ids = explode(",", $_GET['ids']);
				$object -> getAvailbleParents($system -> model -> getTable());
				$result = $system -> model -> getParentsForMultiAutocomplete($request, $ids);
			}
			else
			{
				$object -> getAvailbleParents($system -> model -> getTable());
				$result = $object -> getDataForAutocomplete($request, $system -> db);
			}
		}
		else
			$result = $object -> getDataForAutocomplete($request, $system -> db);
			
		if(isset($result["query"]))
			$result["query"] = htmlspecialchars_decode($result["query"], ENT_QUOTES);
			
		echo json_encode($result);
	}
}
?>