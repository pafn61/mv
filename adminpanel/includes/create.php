<?
if($system -> user -> checkModelRights($system -> model -> getModelClass(), "create"))
	$action = "location.href='".$registry -> getSetting('AdminPanelPath')."model/create.php?".$url_params."'";
else
	$action = "dialogs.showAlertMessage('{no_rights}')";
?>

<input class="button-create" type="button" onclick="<? echo $action; ?>" value="<? echo I18n :: locale('create'); ?>" />
