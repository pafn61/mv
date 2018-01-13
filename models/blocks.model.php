<?
class Blocks extends Model
{
	protected $name = "Текстовые блоки";
	
	protected $model_elements = array(
		array("Активация", "bool", "active", array("on_create" => true)),
		array("Название", "char", "name", array("required" => true)),
		array("Содержание", "text", "content", array("rich_text" => true))
	);
}
?>