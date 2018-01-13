<?
$content = $mv -> pages -> findRecord(array("url" => "e404"));
$mv -> seo -> mergeParams($content, "name");

include $mv -> views_path."main-header.php";
?>
<div class="content">
	<h1><? echo $content -> name; ?></h1>
	<? echo $content -> content; ?>
</div>
<?
include $mv -> views_path."main-footer.php";
?>