<? 
/**
 * Displays the menus of admin panel.
 * Keeps all data inside self and load landuage parts from I18n class.
 */
class Menu 
{
	private $registry;
	
	private $i18n;
	
	private $user;
	
	public function __construct()
	{
		$this -> registry = Registry :: instance();
		$this -> i18n = I18n :: instance();
	}
	
	public function setUser(User $user)
	{
		$this -> user = $user;
	}
	
	public function displayModelsMenu()
	{
		if(!count($this -> registry -> getSetting('Models')))
			return;
		
		$data = $this -> getActiveModels();
		$models = $data[0];
		
		$columns_number = count($models) < 7 ? 3 : 5;
		$rows_number = ceil(count($models) / $columns_number);
		$current_row = $current_column = 1;
		$table_data = array();
		
		foreach($models as $caption => $href)
		{
			if($current_row > $rows_number)
			{
				$current_row = 1;
				$current_column ++;
			}
				
			$table_data[$current_row][$current_column] = array($caption, $href);
			$current_row ++;
		}
		
		$html = "<table class=\"models-list-table\">\n";
		
		for($i = 1; $i <= $rows_number; $i ++)
		{
			$html .= "<tr>\n";
				
			for($j = 1; $j <= $columns_number; $j ++)
				if(isset($table_data[$i][$j]))
					$html .= "<td><a href=\"".$table_data[$i][$j][1]."\">".$table_data[$i][$j][0]."</a></td>\n";
				
			$html .= "</tr>\n";
		}
		
		return $html."</table>\n";
		
	}
	
	public function displayMultiActionMenu($model_object, User $user_object)
	{
		$model_data = $model_object -> getDataForActionsMenu();
		
		if((!count($model_data) && !$model_object -> checkDisplayParam('delete_actions')) || 
		   (!$model_object -> checkDisplayParam('update_actions') && !$model_object -> checkDisplayParam('delete_actions')) ||
		    !$model_object -> checkDisplayParam('mass_actions'))
			return "";
		
		$html = "<div class=\"multi-actions-menu\">\n";
		$html .= "<input class=\"button-list\" type=\"button\" value=\"".I18n :: locale('with-selected')."\" />\n";
		$html .= "<ul>\n";
		
		$can_update = $user_object -> checkModelRights($model_object -> getModelClass(), "update");
		$types_list = array('enum', 'parent', 'date', 'date_time', 'int', 'float');
		
		foreach($model_data as $data)
		{
			$css_class = $can_update ? "multi-".$data['name'] : "has-no-rights";
			
			if($data['type'] == 'bool')
			{
				$html .= "<li class=\"".$css_class."-1\">".$data['caption']." &laquo;".I18n :: locale('yes')."&raquo;</li>\n";
				$html .= "<li class=\"".$css_class."-0\">".$data['caption']." &laquo;".I18n :: locale('no')."&raquo;</li>\n";
			}
			else if($data['type'] == 'restore')
				$html .= "<li class=\"".$css_class."\">".$data['caption']."</li>\n";
			else if(in_array($data['type'], $types_list))
				$html .= "<li class=\"".$css_class."\">".I18n :: locale('change-param')." &laquo;".$data['caption']."&raquo;</li>\n";
			else if($data['type'] == 'many_to_many' || $data['type'] == 'group')
			{
				$html .= "<li class=\"".$css_class."-add\">".I18n :: locale('add-param')." &laquo;".$data['caption']."&raquo;</li>\n";
				$html .= "<li class=\"".$css_class."-remove\">".I18n :: locale('remove-param')." &laquo;".$data['caption']."&raquo;</li>\n";
			}
		}
		
		$css_class = $user_object -> checkModelRights($model_object -> getModelClass(), "delete") ? "multi-delete" : "has-no-rights";
		
		if($model_object -> checkDisplayParam('delete_actions'))
			$html .= "<li class=\"".$css_class."\">".I18n :: locale('delete')."</li>\n";
		
		return $html."</ul>\n</div>\n";
	}
	
	public function displayTableFields($model_object)
	{
		$html = array("selected" => "", "not-selected" => array());
		$selected_fields = $model_object -> getFieldsToDisplay();
		
		foreach($selected_fields as $name)
			if($model_object -> checkIfFieldVisible($name))
				if($name == 'id' || $object = $model_object -> getElement($name))
				{
					$caption = ($name != 'id') ? $object -> getCaption() : 'Id';
					
					if($object && $object -> getType() == 'parent')
						$caption = I18n :: locale('child-records');					
					
					$html['selected'] .= "<option value=\"".$name."\">".$caption."</option>\n";
				}
				
		foreach($model_object -> getElements() as $name => $object)
			if($model_object -> checkIfFieldVisible($name))
				if(!in_array($name, $selected_fields) && $object -> getType() != 'password')
				{ 
					if($object -> getType() == 'text' && !$object -> getProperty('show_in_admin'))
						continue;
					
					$caption = ($object -> getType() == 'parent') ? I18n :: locale('child-records') : $object -> getCaption();
					$html['not-selected'][$caption] = "<option value=\"".$name."\">".$caption."</option>\n";				
				}

		ksort($html['not-selected']);

		if(!in_array('id', $selected_fields))
			$html['not-selected'][] = "<option value=\"id\">Id</option>\n";
			
		$html['not-selected'] = count($html['not-selected']) ? implode("", array_values($html['not-selected'])) : "";
		
		return $html;
	}
	
	public function getActiveModels()
	{
		$models = array();
		$max_width = 0;
		
		foreach($this -> registry -> getSetting('Models') as $model)
		{
			$object = new $model();
						
			$href = $this -> registry -> getSetting('AdminPanelPath')."model/";

			if(get_parent_class($object) == "Model_Simple")
				$href .= "index-simple.php";
			
			$href .= "?model=".$model;
			
			$name = $object -> getName();
			$length = mb_strlen($name, "utf-8");
			
			if($length > $max_width)
				$max_width = $length;
			
			$models[$object -> getName()] = $href;
		}

		return array($models, $max_width);		
	}
}
?>