<?
/**
 * Class for sorting the rows when taking elements by SQL queries.
 * Takes values from GET, checks them and passes into SQL query.
 * Usually we need to put the object inside the class where we execute the SQL query.
 */
class Sorter
{  
   //Allowed fields for sorting the same as names of DB fields
   protected $fields;
   
   //Curent field of sorting.
   protected $field;

   //Current order of sorting (SQL asc / desc)
   protected $order; 
   
   //Params to go in url
   private $url_params;

   public function __construct($fields)
   {
      $this -> source_field = 'sort-field'; //Vars of GET to take params
      $this -> source_order = 'sort-order';
      $this -> fields = is_array($fields) ? $fields : false; //Takes fields     
   }
   
   public function setParams($field, $order)
   {
         if(array_key_exists($field, $this -> fields) && ($order == 'asc' || $order == 'desc'))
         {
            $this -> field = $field;
            $this -> order = $order;
            
            return true;
         }
         
         return false;
   }

   public function getField() { return $this -> field; }
   public function getOrder() { return $this -> order; }
   
   	public function getFieldType()
   	{
   		if($this -> field)
   			return $this -> fields[$this -> field];
   	}
   
	public function setUrlParams($url_params)
	{
		if($url_params)
			$this -> url_params = "?".$url_params;
	} 
	
	public function getUrlParams()
	{		
		//Makes string of GET params
		if($this -> field && $this -> order)
        	return $this -> source_field."=".$this -> field."&".$this -> source_order."=".$this -> order;
	}
	
	public function addUrlParams($path)
	{		
		//Adds to url string of GET params
		if($this -> field && $this -> order)
		{
			$path .= (strpos($path, "?") === false) ? "?" : "&";     
        	$path .= $this -> source_field."=".$this -> field."&".$this -> source_order."=".$this -> order;
		}
		
		return $path;
	}

   	public function getParamsForSQL()
   	{
   	  if(!$this -> field || !$this -> order)
   	  {
   	  	$keys = array_keys($this -> fields);
   	  	$this -> field = $keys[0];
   	  	$this -> order = $this -> defineSortOrder($this -> field);   	  	  
   	  }
   	  
   	  $registry = Registry :: instance();
   	  $fix = ($registry -> getSetting("DbEngine") == "sqlite") ? "COLLATE NOCASE " : "";
   	  
      if($this -> field && $this -> order)
         return " ORDER BY `".$this -> field."` ".$fix.strtoupper($this -> order);
   	}
   
   	public function createAdminLink($caption, $field)
  	{
   		$params = $this -> url_params ? $this -> url_params."&" : "?";
   		$params .= $this -> source_field."=".$field."&".$this -> source_order."=";
   		
		if(array_key_exists($field, $this -> fields) && $this -> fields[$field] == 'order')
   			$params .= 'asc';
   		else
   			$params .= $this -> defineSortOrder($field);
   		
   		$css_class = "";
   		
   		if($field == $this -> field)
   			if($this -> order == "asc")
   				$css_class = " class=\"active-asc\"";
   			else 
   				$css_class = " class=\"active-desc\"";
   		
		return "<a".$css_class." href=\"".$params."\">".$caption."</a>\n";
   	}
   
   	public function defineSortOrder($field)
   	{
   	   if($this -> field && $this -> order && $this -> field == $field)
			return ($this -> order == 'desc') ? 'asc' : 'desc';
   	  
   	  $initial_orders = array(
   	  	  'id' => 'asc',
	   	  'bool' => 'desc',
	   	  'int' => 'desc',
	   	  'float' => 'desc',
	   	  'char' => 'asc',
	   	  'url' => 'asc',
	   	  'redirect' => 'asc',
	   	  'email' => 'asc',
	   	  'enum' => 'asc',
	   	  'parent' => 'desc',
	   	  'order' => 'asc',
	   	  'date' => 'desc',
	   	  'date_time' => 'desc',
	   	  'image' => 'desc',
	   	  'multi_images' => 'desc',
   	  	  'file' => 'asc',
	   	  'many_to_one' => 'desc',
	   	  'many_to_many' => 'desc'
   	  );
   	  
   	  if(array_key_exists($field, $this -> fields))
   	  	if(array_key_exists($this -> fields[$field], $initial_orders))
   	  		return $initial_orders[$this -> fields[$field]];
   	  	else
   	  		return 'asc';
   	}
   	
   	public function displayLink($field, $title)
   	{
   		$order = $this -> defineSortOrder($field);
   		$path = $css_class = "";
   		
   		$arguments = func_get_args();
   		
   		if(isset($arguments[2]) && $arguments[2])
   			$path = $arguments[2];
   			
   		$path .= (strpos($path, "?") === false) ? "?" : "&";
		
		if(!($this -> field && $this -> order && $this -> field == $field))
   			if(isset($arguments[3]) && $arguments[3] == "reverse")
   				$order = ($order == 'desc') ? 'asc' : 'desc';
   		
   		if($field == $this -> field)
   			if($this -> order == "asc")
   				$css_class = " class=\"active-asc\"";
   			else 
   				$css_class = " class=\"active-desc\"";
   		
   		$html = "<a href=\"".$path.$this -> source_field."=".$field."&";
   		$html .= $this -> source_order."=".$order."\"".$css_class.">";
   		
   		return $html.$title."</a>\n";
   	}
   	
   	public function displaySingleLink($field, $order, $title)
   	{
   		$path = $css_class = "";
   		$arguments = func_get_args();
   		
   		if(isset($arguments[3]) && $arguments[3])
   			$path = $arguments[3];
   			
   		$path .= (strpos($path, "?") === false) ? "?" : "&";
   		
   		if($field == $this -> field && $order == $this -> order)
   			$css_class = " class=\"active\"";
   		
   		$html = "<a href=\"".$path.$this -> source_field."=".$field."&";
   		$html .= $this -> source_order."=".$order."\"".$css_class.">";
   		
   		return $html.$title."</a>\n";
   	}
   	
   	public function hasParams()
   	{
   		return ($this -> field && $this -> order);
   	}
}
?>