<?
session_start();

$_SESSION["login"]["captcha"] = "";
$image = imagecreate(120, 30);   
imagecolorallocate($image, 255, 255, 255);

$symbols_color = imagecolorallocate($image, 17, 17, 17);
$font = dirname(__FILE__)."/font.ttf";     

for($i = 0; $i < 7; $i++) 
{
    $number = mt_rand(0, 9);
    $_SESSION["login"]["captcha"] .= $number;
    $angle = mt_rand(-25, 25);
    imagettftext($image, 19, $angle, 8 + 15 * $i, 23, $symbols_color, $font, $number);
}

$_SESSION["login"]["captcha"] = md5($_SESSION["login"]["captcha"]);

header("Expires: Wed, 1 Jan 1997 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: image/png");
	
imagepng($image);
imagedestroy($image);
?>