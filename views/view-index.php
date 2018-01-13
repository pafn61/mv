<?
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, "name");

include $mv -> views_path."main-header.php";
?>
<div class="content">
	<h1><? echo $content -> name; ?></h1>
	<p><img src="<? echo $mv -> root_path; ?>adminpanel/interface/images/logo.png" alt="" /></p>
	<p>Версия <? echo $mv -> registry -> getVersion()." ".$mv -> registry -> getSetting("DbEngine"); ?><br />
	Папка проекта <? echo $mv -> root_path; ?><br />
	Папка для медиа файлов <? echo $mv -> media_path; ?><br />
	Папка шаблонов <? echo $mv -> views_path; ?><br />
	<a href="<? echo $mv -> registry -> getSetting("AdminPanelPath"); ?>" target="_blank">Административная панель</a>,
	логин root, пароль root
	</p>
	<? echo $content -> content; ?>
</div>
<?
include $mv -> views_path."main-footer.php";
?>