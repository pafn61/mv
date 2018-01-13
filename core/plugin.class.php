<?
abstract class Plugin extends Model_Initial
{
	public function __construct()
	{
		$this -> registry = Registry :: instance(); 
		$this -> db = DataBase :: instance();
		$this -> table = strtolower(get_class($this));
	}
	
	public function getTable() { return $this -> table; }
	
	public function getId() { return $this -> id; }
	
	public function runPager($total, $limit, $current_page)
	{
		$this -> pager = new Pager($total, $limit);
		
		if($current_page)
			$this -> pager -> definePage($current_page);
	}
}
?>