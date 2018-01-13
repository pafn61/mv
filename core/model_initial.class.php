<?
abstract class Model_Initial
{
	//Settings object
	public $registry;
	
	//Database manager
	public $db;

	//Current table
	protected $table;
	
	//Id of current db row (record)
	protected $id;

	public function checkRecordById($id)
	{
		return $this -> db -> getCount($this -> table, "`id`='".intval($id)."'");
	}
	
	public function findRecord($params)
	{
		if(!count($params) || !$params)
			return false;
			
		if(isset($params["table->"]) && $this -> registry -> checkModel($params["table->"]))
			$model_object = new $params["table->"]("frontend");
		else
			$model_object = clone $this;
		
		if($content = $this -> selectOne($params))
		{
			if($this -> registry -> getInitialVersion() < 1.11)
				$this -> id = $content['id'];
			
			foreach($model_object -> getElements() as $name => $object)
				if($object -> getType() == "many_to_many")
				{
					$ids = $object -> setRelatedId($content['id']) -> getSelectedValues();					
					$content[$name] = count($ids) ? implode(",", $ids) : "";
				}
		}
		
		return $content ? new Record($content, $model_object) : false;
	}
	
	public function findRecordById($id)
	{
		return $this -> findRecord(array("id" => $id));
	}
	
	public function countRecords()
	{
		$arguments = func_get_args();
		$conditions = "";
		
		if(!isset($arguments[0]) || !count($arguments[0]))
			$arguments[0] = array();
			
		$arguments[0]["count->"] = "1";
		unset($arguments[0]["limit->"]);
				
		return (int) $this -> db -> getCell($this -> composeSQLQuery($arguments[0])); 		
	}
	
	public function composeSQLQuery()
	{
		$query = "SELECT * FROM `".$this -> table."`";
		$arguments = func_get_args();
		$params = (!isset($arguments[0]) || !is_array($arguments[0])) ? array() : $arguments[0];
		$query .= $this -> processSQLConditions($params);

		if(array_key_exists("count->", $params))
			$query = str_replace("*", "COUNT(*)", $query);
		else if(array_key_exists("fields->", $params))
			$query = str_replace("*", str_replace("'", "", $params["fields->"]), $query);
			
		if(array_key_exists("table->", $params))
			$query = str_replace("FROM `".$this -> table."`", "FROM `".$params["table->"]."`", $query);
			
		return $query;	
	}
	
	static public function processSQLConditions($params)
	{
		$where = array();
		$query = $order = $limit = "";
		$registry = Registry :: instance();
		$db = Database :: instance();
		$special_keys = array("table->", "count->", "fields->", "group->by", "extra->", "order->double");
		
		foreach($params as $key => $value)
		{
			$key = str_replace("'", "", $key);
			
			 if(in_array($key, $special_keys))
				continue;			
			
			if(strpos($key, "->like") || strpos($key, "->not-like"))
			{
				$field = str_replace(array("->like", "->not-like"), "", $key);
				$condition = strpos($key, "->like") ? "LIKE" : "NOT LIKE";
				$where[] = "`".$field."` ".$condition." ".$db -> secure("%".$value."%");
			}
			else if($key == "order->asc" || $key == "order->desc")
			{
				$order = strtoupper(str_replace("order->", "", $key));
				$order = " ORDER BY `".str_replace("'", "", $value)."` ".$order;
			}
			else if($key == "order->in" && $value)
			{
				if($registry -> getSetting("DbEngine") == "sqlite")
					continue;
				
				$order = " ORDER BY FIELD(`id`, ".str_replace("'", "", $value).")";
			}			
			else if($key == "order->" && $value == "random")
			{
				if($registry -> getSetting("DbEngine") == "sqlite")
					$order = " ORDER BY RANDOM()";
				else
					$order = " ORDER BY RAND()";
			}			
			else if(strpos($key, "->in") || strpos($key, "->not-in"))
			{
				$field = str_replace(array("->in", "->not-in"), "", $key);				
				$values = explode(",", $value);
					
				foreach($values as $k => $v)
				{
					$v = trim($v);
					
					if($v != "")
						$values[$k] = "'".str_replace("'", "", $v)."'";
					else
						unset($values[$k]);
				}
					
				$value = implode(",", $values);
					
				$condition = strpos($key, "->in") ? "IN" : "NOT IN";				
				$where[] = "`".$field."` ".$condition."(".$value.")";
			}
			else if($key == "limit->")
				$limit = " LIMIT ".trim(str_replace("LIMIT", "", str_replace("'", "", $value)));
			else if(strpos($key, "!=") || strpos($key, ">") || strpos($key, "<"))
			{
				$condition = preg_replace("/.*(!=|>=?|<=?)$/", "$1", $key);
				$key = str_replace($condition, "", $key);
				$where[] = "`".$key."`".$condition.$db -> secure($value);
			}
			else if($key == "id")
				$where[] = "`id`='".intval($value)."'";			
			else
				$where[] = "`".$key."`=".$db -> secure($value);
		}
				
		if(array_key_exists("extra->", $params))
			$where[] = $params["extra->"];				
		
		$query = count($where) ? " WHERE ".implode(" AND ", $where) : "";
		
		if(array_key_exists("group->by", $params))
			$query .= " GROUP BY `".str_replace("'", "", $params["group->by"])."`";
			
		if($order && array_key_exists("order->double", $params))
		{
			$values = explode("->", $params["order->double"]);
			
			if(count($values) == 2 && ($values[1] == "asc" || $values[1] == "desc"))
				$order .= ", `".$values[0]."` ".strtoupper($values[1]);
		}
				
		return $query.$order.$limit;
	}
	
	public function select()
	{
		$params = func_get_args();		
		$params = (isset($params[0]) && count($params[0])) ? $params[0] : array();
		
		return $this -> db -> getAll($this -> composeSQLQuery($params));
	}
	
	public function selectOne()
	{
		$params = func_get_args();		
		$params = (isset($params[0]) && count($params[0])) ? $params[0] : array();
		$params["limit->"] = "1";

		return $this -> db -> getRow($this -> composeSQLQuery($params));
	}
	
	public function selectColumn()
	{
		$params = func_get_args();		
		$params = (isset($params[0]) && count($params[0])) ? $params[0] : array();
		
		return $this -> db -> getColumn($this -> composeSQLQuery($params));
	}

	public function updateManyRecords($params, $conditions)
	{
		$fields = array();
		
		foreach($params as $field => $value)
		{
			$value = htmlspecialchars(trim($value), ENT_QUOTES);
			$value = Service :: cleanHtmlSpecialChars($value);
			$fields[] = "`".$field."`=".$this -> db -> secure($value);
		}
			
		$where = $this -> processSQLConditions($conditions);
		
		if($where)
			$this -> db -> query("UPDATE `".$this -> table."` SET ".implode(",", $fields).$where);
		
		return $this;
	}
	
	public function deleteManyRecords($conditions)
	{
		$where = $this -> processSQLConditions($conditions);
		
		if($where)
		{
			$ids = $this -> db -> getColumn("SELECT `id` FROM `".$this -> table."` ".$where);

			if(count($ids))
			{
				$this -> db -> query("DELETE FROM `".$this -> table."` ".$where);
				
				if(isset($this -> elements) && count($this -> elements))
					foreach($this -> elements as $name => $object)
						if($object -> getType() == 'many_to_many')
							$this -> db -> query("DELETE FROM `".$object -> getProperty('linking_table')."`
												  WHERE `".$object -> getOppositeId()."` IN(".implode(",", $ids).")");
			}
		}
		
		return $this;		
	}
	
	public function clearTable()
	{
		$params = func_get_args();
		$table = (isset($params[0]) && $params[0]) ? $params[0] : $this -> table;

		if($this -> registry -> getSetting("DbEngine") == "sqlite")
			$this -> db -> query("DELETE FROM `".$table."`");
		else
			$this -> db -> query("TRUNCATE `".$table."`");
		
		return $this;
	}
	
	public function resizeImage($image, $width, $height)
	{		
		$arguments = func_get_args();
		$argument_3 = isset($arguments[3]) ? $arguments[3] : null;
		$argument_4 = isset($arguments[4]) ? $arguments[4] : null;
		
		return $this -> cropImage($image, $width, $height, $argument_3, $argument_4, "resize");		
	}
	
	public function cropImage($image, $width, $height)
	{
		$arguments = func_get_args();		
		$params = array();
		$params["title"] = "";
		
		if(isset($arguments[3]) && is_array($arguments[3]))
		{
			$params = $arguments[3];
			$params["alt-text"] = isset($params["alt-text"]) ? $params["alt-text"] : "";
			$params["no-image-text"] = isset($params["no-image-text"]) ? $params["no-image-text"] : "";
			$params["title"] = (isset($params["title"]) && $params["title"]) ? ' title="'.$params["title"].'"' : "";
		}
		else
		{
			$params["alt-text"] = (isset($arguments[3]) && $arguments[3]) ? $arguments[3] : "";
			$params["no-image-text"] = (isset($arguments[4]) && $arguments[4]) ? $arguments[4] : "";			
		}
		
		$image = Service :: addFileRoot($image);
		
		if(!$image || !is_file($image))
			return $params["no-image-text"];
		
		$folder = $this -> table."_".$width."x".$height;

		$imager = new Imager();
		$method = (isset($arguments[5]) && $arguments[5] == "resize") ? "compress" : "crop";
		$src = $imager -> $method($image, $folder, $width, $height);
		
		$file = Service :: removeFileRoot($this -> registry -> getSetting("DocumentRoot").$src);
		
		if(isset($params["watermark"]) && !$imager -> wasCreatedErlier())
		{
			$margin_top = isset($params["watermark-margin-top"]) ? intval($params["watermark-margin-top"]) : false;
			$margin_bottom = isset($params["watermark-margin-bottom"]) ? intval($params["watermark-margin-bottom"]) : false;
			$margin_left = isset($params["watermark-margin-left"]) ? intval($params["watermark-margin-left"]) : false;
			$margin_right = isset($params["watermark-margin-right"]) ? intval($params["watermark-margin-right"]) : false;
						
			$imager -> addWatermark($file, $params["watermark"], $margin_top, $margin_bottom, $margin_left, $margin_right);
		}
		
		if(isset($params["only-source"]) && $params["only-source"])
			return $src;
			
		return "<img src=\"".$src."\" alt=\"".$params["alt-text"]."\"".$params["title"]." />\n";		
	}
	
	static public function getFirstImage($value)
	{
		$images = explode("-*//*-", $value);
		return (isset($images[0]) && $images[0]) ? preg_replace("/\(\*[^*]*\*\)/", "", $images[0]) : false;
	}
	
	static public function extractImages($value)
	{
		$arguments = func_get_args();
		$result_images = array();
		$images = explode("-*//*-", $value);
				
		if(isset($arguments[1]) && $arguments[1] == "no-comments")
		{			
			foreach($images as $image)
				if($image)
					$result_images[] = preg_replace("/\(\*[^*]*\*\)/", "", $image);
		}
		else
			foreach($images as $image)
				if($image)
					if(strpos($image, "(*") !== false)
					{
						$data = explode("(*", $image);
						$result_images[$data[0]] = str_replace("*)", "", $data[1]);
					}
					else
						$result_images[$image] = "";
		
		return $result_images;
	}
}	