<!DOCTYPE html>
<html>
<head>
<title><? echo $mv -> seo -> title; ?></title>
<meta name="description" content="<? echo $mv -> seo -> description; ?>" />
<meta name="keywords" content="<? echo $mv -> seo -> keywords; ?>" />
<script type="text/javascript"> var rootPath = "<? echo $mv -> root_path; ?>"; </script>
<style type="text/css">
	*{font: 14px/17px "Arial"; color: #333;}
	body{padding: 0; margin: 0;}
	h1{font-size: 27px; color: #555; margin: 0 0 20px 0;}
	h2{font-size: 20px; padding-top: 20px; margin: 0 0 10px 0;}
	h3{font-size: 18px; margin: 0 0 12px 0;}
	a{color: #1a6ab0;}
	p{margin: 0 0 10px 0; max-width: 650px;}
	p img{margin-bottom: 5px;}
	li{margin-bottom: 7px;}
	ul{margin: 0 0 15px 0; padding: 5px 0 0 15px;}
	ul.menu{background: #222; padding: 10px 0; float: left; margin: 0 0 25px 0; width: 100%;}
	ul.menu li{list-style: none; float: left; margin: 0 10px;}
	ul.menu li:first-child{margin-left: 30px;}
	ul.menu li a{text-decoration: none; color: #fff;}
	ul.menu li.active a{color: #6db9d7;}
	div.content{clear: both; margin: 0 0 0 30px;}
	
	form{padding: 10px 0 0 0;}
	form table td{padding: 0 0 10px 0;}
	form table td.field-name{width: 150px; padding-right: 12px;}
	form td.field-input input, form td.field-input textarea{width: 280px; padding: 5px 10px;}
	form td.field-input input[type="checkbox"]{width: auto;}
	form input[type="submit"]{float: right;}
	form td.field-input select{width: 302px; padding: 5px 10px;}
	div.form-errors, div.form-success{margin: 15px 0 15px 0; padding: 5px 10px; width: 450px;}
	div.form-errors p, div.form-success p{margin: 5px 0; padding-left: 5px;}
	div.form-errors{background: #fbd689;}
	div.form-success{background: #deee85;}
</style>
<? echo $mv -> seo -> displayMetaData("head"); ?>
</head>
<body>
<ul class="menu">
	<? echo $mv -> pages -> displayMenu(-1); ?>
	<li><a href="<? echo $mv -> registry -> getSetting("AdminPanelPath"); ?>" target="_blank">Административная панель</a></li>
</ul>