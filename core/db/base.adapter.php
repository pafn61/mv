<? 
abstract class Db_Adapter
{
	protected static $registry;
	
	public function __construct()
	{
		self :: $registry = Registry :: instance();
	}
	
	public function now()
	{
		return "'".date("Y-m-d H:i:s")."'";
	}
	
	public function timestamp()
	{
		return time();
	}
}
?>
