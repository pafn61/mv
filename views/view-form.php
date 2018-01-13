<?
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, "name");

$fields = array(array("Имя", "char", "name", array("required" => 1)),
				array("Email", "email", "email"),
				array("Тема", "enum", "theme", array("empty_value" => "Не выбрано", "required" => 1,
													"values_list" => array("business" => "Организационный вопрос",
																			"tecnical" => "Технический вопрос",
																			"commertial" => "Коммерческое предложение",
																			"other" => "Другое"))),
				array("Сообщение", "text", "message", array("required" => 1)),
				array("Согласен получать новости", "bool", "news"));

$form = new Form($fields);
$form_complete = false;

if(!empty($_POST))
{
	$form -> getDataFromPost() -> validate();
	
	if(!$form -> hasErrors())
		$form_complete = true;
}

include $mv -> views_path."main-header.php";
?>
<div class="content">
	<h1><? echo $content -> name; ?></h1>
	<?
		echo $content -> content;
		
		if($form_complete)
		{
			echo "<div class=\"form-success\"><p>Форма успешно заполнена.</p></div>\n";
			echo "<h3>Сообщение для отправки по email</h3>\n";
			echo $form -> composeMessage();
			
			echo "<h3>Поля для SQL запроса</h3>\n";
			Debug :: pre($form -> getAllValues());
		}
		else
			echo $form -> displayErrors();
			
		if(!$form_complete):
	?>
	<form action="<? echo $mv -> root_path; ?>form/" method="post">
		<table>
			<? echo $form -> display(); ?>
			<tr>
				<td colspan="2">
					<input type="submit" value="Отправить" />
				</td>
			</tr>
		</table>
	</form>
	<? endif; ?>
</div>
<?
include $mv -> views_path."main-footer.php";
?>