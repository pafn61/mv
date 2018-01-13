<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex, nofollow" />
<title><? echo I18n :: locale('mv'); ?></title>
<link rel="stylesheet" type="text/css" href="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/css/style.css?v2" />
<link rel="stylesheet" type="text/css" href="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/css/ui.css" />
<link rel="stylesheet" type="text/css" href="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/css/uploadify.css" />

<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/mv.js"></script>
<script type="text/javascript">
mVobject.mainPath = "<? echo $registry -> getSetting('MainPath'); ?>";
mVobject.adminPanelPath = "<? echo $registry -> getSetting('AdminPanelPath'); ?>"; 
mVobject.urlParams = "<? if(isset($system -> model)) echo $system -> model -> getAllUrlParams(array('pager','filter','model','parent','id')); ?>";
<?
if(isset($system -> model))
   echo "mVobject.currentModel = \"".$system -> model -> getModelClass()."\";\n";

if(isset($system -> model -> sorter))
   echo "mVobject.sortField = \"".$system -> model -> sorter -> getField()."\";\n";

if(isset($system -> model))
{
	$parent = $system -> model -> findForeignParent();
	$linked_order_fields = $system -> model -> findDependedOrderFilters();	
}

if(isset($parent) && is_array($parent) && isset($system -> model -> filter))
	if(!$system -> model -> filter -> allowChangeOrderLinkedWithEnum($parent['name']))
		echo "mVobject.relatedParentFilter = \"".$parent['caption']."\";\n";

if(isset($linked_order_fields) && count($linked_order_fields))
	foreach($linked_order_fields as $name => $data)
		if(!$system -> model -> filter -> allowChangeOrderLinkedWithEnum($data[0]))
			echo "mVobject.dependedOrderFields.".$name." = \"".$data[1]."\";\n";
		
$has_applied_filters = (int) (isset($system -> model -> filter) && $system -> model -> filter -> ifAnyFilterApplied());
echo "mVobject.hasAppliedFilters = ".$has_applied_filters.";\n";      
      
if(isset($system -> model -> filter))
   if($caption = $system -> model -> filter -> ifFilteredByAllParents())
      echo "mVobject.allParentsFilter = \"".$caption."\";\n";
   else if(isset($system -> model -> pager))
      echo "mVobject.startOrder = ".($system -> model -> pager -> getStart() + 1).";\n";
	  
$region = $registry -> getSetting('Region');
?>
mVobject.dateFormat = "<? echo str_replace("yyyy", "yy", I18n :: getDateFormat()); ?>";
</script>

<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/jquery.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/jquery-ui.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/form.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/jquery.overlay.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/dialogs.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/date-time.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/jquery.autocomplete.js"></script>
<? if(isset($system -> model) && $system -> model -> findElementByProperty("type", "multi_images") && !isset($no_uploaify_script_flag)): ?>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/jquery.uploadify.js"></script>
<? endif; ?>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/utils.js?v2"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>i18n/<? echo $region; ?>/jquery.ui.datepicker-<? echo $region; ?>.js"></script>
<? 
if($region != "en")
{
	echo "<script type=\"text/javascript\" src=\"".$registry -> getSetting('AdminPanelPath');
	echo "i18n/".$region."/jquery-ui-timepicker-".$region.".js\"></script>\n";
}
?>
<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>i18n/<? echo $region; ?>/locale.js?v2"></script>

<style type="text/css">
<? 
	$browser = Debug :: browser();

	if($browser == "opera" || $browser == "ie" || $browser == "chrome" || $browser == "yandex")
		echo "#fields-list div.list, #operations-menu, div.multi-actions-menu ul{top: 30px;}\n";
	
	$multi_upload_button_path = "url('".$registry -> getSetting('AdminPanelPath')."i18n/".$region."/multi-upload.png')";
	echo "div.images-area .upload-button{background:".$multi_upload_button_path." no-repeat top center !important}\n";
?>
</style>
<?
if($skin = $system -> user -> getUserSkin())
{
   $skin = $registry -> getSetting('AdminPanelPath')."interface/skins/".$skin."/skin.css";
   echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$skin."\" id=\"skin-css\" />\n";
}
else
{
   $skins = $system -> user -> getAvailableSkins();
   echo "<script type=\"text/javascript\">$(document).ready(function() { openSkinChooseDialog([\"".implode("\",\"", $skins)."\"]); });</script>\n";
}
?>

<link rel="icon" href="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/images/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/images/favicon.ico" type="image/x-icon" />
</head>
<body>
<? include $registry -> getSetting("IncludeAdminPath")."includes/noscript.php"; ?>
<div id="container">
   <div id="header">
	      <div class="inner">
	      <a id="logo" href="<? echo $registry -> getSetting('AdminPanelPath'); ?>">
		     <img src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/images/logo.png" alt="MV logo" />
	      </a>
	      <div id="models-buttons">
	         <ul>
	            <li>
	                <span><? echo I18n :: locale("modules"); ?></span>
					<div id="models-list">
						<? echo $system -> menu -> displayModelsMenu(); ?>
					</div>            
	            </li>
	         </ul>
	      </div>
	      <div id="header-search">
				<form action="<? echo $registry -> getSetting('AdminPanelPath'); ?>controls/search.php" method="get">
	   			   <div>
                      <?
                      	  $header_search_value = "";
                      	  
                      	  if(isset($search_text) && preg_match("/\/search\.php$/", $_SERVER["SCRIPT_FILENAME"]))
                      	  	$header_search_value = $search_text;
                      ?>
				      <input class="string" type="text" name="text" placeholder="<? echo I18n :: locale('search-in-all-modules'); ?>" value="<? echo $header_search_value; ?>" />
				      <input type="submit" class="search-button" value="<? echo I18n :: locale('find'); ?>" />
				   </div>
				</form>
		    </div>      
	      <div id="user-settings">
	       <ul>
	         <li id="user-name"><span class="skin-color"><? echo $system -> user -> getField('name'); ?></span></li>
	         <li><a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>controls/user-settings.php"><? echo I18n :: locale("my-settings"); ?></a></li>
	         <?
	            $logout_link = $registry -> getSetting('AdminPanelPath')."login/?logout=".$system -> user -> getId()."&code=";    
	            $logout_link .= md5($system -> user -> getId().session_id().$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']); 
	         ?>
	         <li><a href="<? echo $registry -> getSetting('MainPath') ?>" target="_blank"><? echo I18n :: locale("to-site"); ?></a></li>
	         <li><a href="<? echo $logout_link; ?>"><? echo I18n :: locale("exit"); ?></a></li>
	       </ul>
	      </div>
      </div>
   </div>
   <? echo $system -> displayWarningMessages(); ?>