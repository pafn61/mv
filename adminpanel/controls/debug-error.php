<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex,nofollow" />
<title>MV framework</title>
<style type="text/css">
body{margin:0; border-top:10px solid #333; font-family:Arial, Helvetica, sans-serif;}
#debug-area{background:#eee; padding:10px 10px; border-top:2px solid #ff4343; border-bottom:2px solid #ff4343}
#debug-area p{margin:5px 0}
#debug-area h2{margin:0; padding:5px 0; background:none; color:#777; text-align:left;}
#debug-area h4{margin:0; padding:5px 0; background:none; color:#333; text-align:left; font-weight:bold;}
</style>
</head>
<body>

<div id="debug-area">
    <h2>Internal Script Error</h2>
	<p>MV framework <small>version <? echo $registry -> getVersion(); ?></small></p>
	<h4>Error description: <? echo $error; ?></h4>
	<? 
		
		$backtrace = debug_backtrace();
		unset($backtrace[0], $backtrace[1]);
		
		foreach($backtrace as $key => $data)
			unset($backtrace[$key]['object'], $backtrace[$key]['type']);
		
		echo "<pre>";
		print_r($backtrace);
		echo "</pre>";
	?>
</div>
</body>
</html>