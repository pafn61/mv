<?
/**
 * Class for splitting the long lists into pages.
 * Is makes parts for SQL queries to use in LIMIT.
 */
class Pager
{
	//System settings object
   	private $registry;
		
   //Total number of elements in list (usually current table).
   	private $total;
   
   	//Limit of elements to show per one page.
   	private $limit;
   
   	//Total number of pages according to $this -> total and $this -> limit
   	private $intervals;
   
   	//First element to show from current page according to params.
   	private $start;
   
   	//Current number of page to show (usually passed from GET).
   	private $page;

   	//Params for url string
   	private $url_params;
         
   	public function __construct($total, $limit)
   	{
   	  	//Gets the limits form GET and counts the needed params.
      	$this -> registry = Registry :: instance();
      
      	$this -> total = $total;
      	$this -> limit = $limit;
      
      	$this -> page = 1; //Deafult number of page is 1
      	$this -> start = 0; //And we start form first element
      	$this -> intervals = ceil($this -> total / $this -> limit);
            
      	if(!empty($_GET) && isset($_GET['page'])) //If we have current page number in GET we take it
         	$this -> definePage(intval($_GET['page'])); //and determine the current elements to show on the page
   	}
   
   	//Accessors
   	public function getTotal() { return $this -> total; }
   	public function getIntervals() { return $this -> intervals; }
   	public function getStart() { return $this -> start; }
  	public function getLimit() { return $this -> limit; }
   	public function getPage() { return $this -> page; }   
   
   	public function definePage($page)
   	{
   	  	//Determines the limits and cases when the page number does not exists.
      	if($page <= 1 || !$page)
         	$this -> page = 1;
      	if($page > $this -> intervals)
         	$this -> page = $this -> intervals;
      	else
         	$this -> page = $page;
         
     	 $this -> start = ($this -> page - 1) * $this -> limit;
   	}   

   	public function getParamsForSQL()
   	{
	  	//String for SQL query to set limits of selected items (to use in like LIMIT 5,8)
      	if($this -> start >= 0) //To avoid error page numbers
         	return " LIMIT ".$this -> start.",".$this -> limit; 
     	else
         	return " LIMIT 0,".$this -> limit; 
   	}
   
   	public function getParamsForSelect()
   	{
   		return trim(str_replace("LIMIT", "", $this -> getParamsForSQL()));
   	}
   
	public function setUrlParams($url_params)
	{
		//Makes string of GET params
		$this -> url_params = $url_params ? "?".$url_params : "";
			
		return $this;
	}
	
	public function getUrlParams()
	{	
		if($this -> page > 1)
			return "page=".$this -> page;
	}
	
	public function addUrlParams($path)
	{
		//Adds to url string of GET params
			
		if($this -> page <= 1)
			return $path;

		$path .= (strpos($path, "?") === false) ? "?" : "&";    
		
		return $path."page=".$this -> page;;			
	}

   	public function setLimit($limit)
   	{
      	//Sets new limit and recount the params according to new value.
      	$this -> limit = intval($limit);
      	$this -> intervals = ceil($this -> total / $this -> limit);

      	if(!empty($_GET) && isset($_GET['page'])) //If we have current page number in GET we take it
         	$this -> definePage(intval($_GET['page'])); //and determine the current elements to show on the page
   	}
    
   	public function setTotal($total) 
   	{
   	  	//Sets new total number and recount the params according to new value
      	$this -> total = $total;
      	$this -> setLimit($this -> limit); //Recount the params
   	}
   
   public function addPage($get) 
   {
   		//If we split long list of elements into different pages we use this function 
	    //to add current page number into form action
    	//Or add it to button action to stay in one page of the list afrer we done with current operation
   	  
	  	if($this -> intervals > 1)
      	  	return $get ? "&page=".$this -> page : "?page=".$this -> page;
   }
   
   public function checkPrevNext($type)
   {
   	   	//Says if we don't have any pages before or after current one
      	if($type == 'next')
         	return ($this -> page + 1 <= $this -> intervals);
      	else if($type == 'prev')
         	return ($this -> page - 1 > 0);
   }
   
   public function displayPagesAdmin()
   {
   	  	//Show the pages numbers (usually below the list of elements)
      	if($this -> intervals < 2)
         	return;
      
      	$html = "<div class=\"pager\">\n<div>\n<span>".I18n :: locale('page')."</span>\n";

      	//In case if we have more than 10 pages we need to show only 10 current ones and show the link for next pages
      	if($this -> intervals > 10) 
      	{
         	$totint = ceil($this -> intervals / 10); //Total number of intervals (by 10)
         	$cint  = ceil($this -> page / 10); //Current interval (by 10)

         	if($cint == 1) //If we at the first interval
            	$int_start = ($cint - 1) * 10 + 1;
         	else
            	$int_start = ($cint - 1) * 10;
         
         	if($cint < $totint)
            	$int_end = $int_start + 10;
         	else
            	$int_end = $this -> intervals;

         	if($cint != 1 && $cint != $totint)
            	$int_end ++;
      	}
      	else //If the number of page less then 10
      	{
         	$int_start = 1;
         	$int_end = $this -> intervals;
      	}
      
      	if($this -> intervals > 10 && $cint != 1)  //To display link for very first page
      	{
         	$html .= "<a href=\"".$this -> url_params;
         	$html .= (strpos($this -> url_params, "?") !== false) ? ("&page=1\"") : ("?page=1\"");
         	$html .= " class=\"pager-first\"></a>\n";
      	}
      
      	//And now we need to add page numbers for our path
      	for($i = $int_start; $i <= $int_end; $i ++)
      	{
         	$html .= "<a href=\"".$this -> url_params;
         	$html .= (strpos($this -> url_params, "?") !== false) ? ("&page=".$i."\"") : ("?page=".$i."\"");
         
         	if($i == $this -> page) //Highlights the current page in the interval
           	 	$html .= " class=\"active\"";
         
         	if($this -> intervals > 10) //In case of overflow we use sign '<' and '>', reffering to the next 10 or less pages.
         	{
            	if($i == $int_start && $i != 1)
               		$html .=  " class=\"pager-prev\">";
            	else if($i == $int_end && ($i != $this -> intervals || $totint != $cint))
               		$html .=  " class=\"pager-next\">";
            	else
               		$html .=  ">".$i;
         	}
        	 else
            	$html .=  ">".$i;
         
         	$html .=  "</a>\n";
      	} 
      
      	if($this -> intervals > 10 && $cint != $totint) //To display link for very last page
      	{
         	$html .= "<a href=\"".$this -> url_params;
         	$html .= (strpos($this -> url_params, "?") !== false) ? ("&page=".$this -> intervals."\"") : ("?page=".$this -> intervals."\"");
         	$html .= " class=\"pager-last\"></a>\n";
      	}
      	
      	$html .=  "</div>\n</div>\n";
      
      	return $html;
   	}
   
   	public function displayPagerLimits($values)
   	{
      	//Displays select with options limits of elements per page
   	  	$html = "";
   	  
   	  	foreach($values as $value)
   	  	{
   	  	 	$html .= "<option value=\"".$value."\"";
   	  	 
   	  	 	if($value == $this -> limit)
   	  	 		$html .= " selected=\"selected\"";
   	  	 
   	  	 	$html .= ">".$value."</option>\n";
   	  	}

   	  	return $html;
   	}

   	public function addFilter($html, $filter, $param)
   	{
   	  	//Adds page number including other types of filters
   	  
      	if(!$filter) //If we have no filters at all
         	return $html;
      
      	if($param) //If its regular url
         	$html = preg_replace("/(page=[0-9]+)/", "$1".$filter, $html);
      	else //If its smart url
         	$html = preg_replace("/(\/page\/[0-9]+\/)/", "$1".$filter, $html);
      
      	return $html;
   	}
   
   	public function display($path, $smart)
   	{
      //Displays html for pages numbers (current page is in center of interval)
      
      	if($this -> intervals < 2) //If numer of pages less than 2
         	return;
      
      	$html = "";

      	$interval = array(); //Pages numbers to display
      
      	$current_left = ceil($this -> page - 1); //Number of pages from left side
      	$current_right = ceil($this -> intervals - $this -> page); //Number of pages from right side
      
      	if($current_left > 5 && $current_right > 5) //If we are at the middle
      	{
         	$i = $current_left - 4; //5 previous elemets
         	
         	while($i < $this -> page + 6 && $i < $this -> intervals)
            	$interval[] = $i ++;
      	}
      	else if($current_left > 5) //10 elements form the end
      	{
         	$i = $this -> intervals;
         	
         	while($i > $this -> intervals - 10 && $i > 0)
            	array_unshift($interval, $i --);
      	}
      	else if($current_right > 5) //10 element form beginning
      	{
         	$i = 1;
         	
         	while($i < 11 && $i <= $this -> intervals)
            	$interval[] = $i ++;
      	}
      	else
      	{
         	$i = 1;
         	
         	while($i <= $this -> intervals)
            	$interval[] = $i ++;       
      	}
      
      	$arguments = func_get_args();
      	$extra_params = (isset($arguments[2])) ? $arguments[2] : "";
      
      	if($smart) //Url part of page number to  replace it with integer value
         	$pattern = "page/number/".$extra_params."\"";
      	else
         	$pattern = (strpos($path, "?")) ? ("&page=number\"") : ("?page=number\"");
      
      	$first = false;
         
      	foreach($interval as $value) //Adds pages links one by one
      	{
         	$html .= "<a href=\"".$this -> registry -> getSetting('SitePath').$path;
         	$html .= str_replace("number", $value, $pattern); //Adds number of page
         
         	$css_class = "";
			
		 	if(!$first)
		 	{
				$css_class = "first";
				$first = true;
		 	}
			
		  	if($value == $this -> page) //Highlights the current page in the interval
				$css_class .= $css_class ? " active" : "active";

		 	if($css_class)
				$html .= " class=\"".$css_class."\"";			
			
         	$html .= ">".$value."</a>\n";
      	}
      
      	if($current_left > 5 && $this -> intervals > 10) //If we need to add the very first page
         	$html = "<a class=\"very-first\" href=\"".$this -> registry -> getSetting('SitePath').$path.str_replace("number", 1, $pattern).">...</a>\n".$html;
            
      	if($current_right > 5 && $this -> intervals > 10) //Id we add very last page
         	$html .= "<a class=\"very-last\" href=\"".$this -> registry -> getSetting('SitePath').$path.str_replace("number", $this -> intervals, $pattern).">...</a>\n";     
      
      	return $html;
   	}
   
   	public function hasPages()
   	{
       	return ($this -> intervals > 1); 
   	}
   
   	public function displayLimits($limits, $path)
   	{
   		$html = "";
   		$arguments = func_get_args();
   		$options = (isset($arguments[2]) && $arguments[2] == "options");   		
   		$path .= (strpos($path, "?") === false) ? "?" : "&";
   		
   	    foreach($limits as $limit)
   	    {
   	    	$url = $path."pager-limit=".$limit;
   	    	$html .= $options ? "<option value=\"".$url."\"" : "<a href=\"".$url."\"";
   	    	
   	    	if($this -> limit == $limit)
   	    		$html .= $options ? " selected=\"selected\"" : " class=\"active\"";
				
			$html .= ">".$limit.($options ? "</option>\n" : "</a>\n");
   	    }
   	    	
   	    return $html;
   	}
   	
   	public function displayPrevLink($caption, $path)
   	{
   		if($this -> checkPrevNext("prev") && $caption)
   		{
   			$path .= (strpos($path, "?") === false) ? "?" : "&";
   			$path .= "page=".($this -> page - 1);
   			return "<a class=\"pager-prev\" href=\"".$path."\">".$caption."</a>\n";
   		}
   	}
   	
   	public function displayNextLink($caption, $path)
   	{
   		if($this -> checkPrevNext("next") && $caption)
   		{
   			$path .= (strpos($path, "?") === false) ? "?" : "&";
   			$path .= "page=".($this -> page + 1);
   			return "<a class=\"pager-next\" href=\"".$path."\">".$caption."</a>\n";
   		}
   	}
}
?>