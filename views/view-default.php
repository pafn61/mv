<?
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
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