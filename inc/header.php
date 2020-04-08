<!DOCTYPE html> 
<html>
	<head>

		<meta charset="UTF-8" />
		<meta name="author" content="Bastien DS" />
		<meta name="copyright" content="www.fliip.com" />
		<meta name="robots" content="index, follow" />
		<meta name="description" content="Online FliiP variant" />
		<meta name="keywords" content="FliiP, American Football, Yards, TouchDown" />

		<title>BDS Online FliiP Variant</title>

		<!--<link rel="Shortcut Icon" href="favicon.ico" type="image/x-icon" />
		<link rel="stylesheet" href="../css/print.css" type="text/css" media="print" />
		<link rel="stylesheet" href="../css/screen.css" type="text/css" media="screen,projection" />
		 -->
		<link rel="stylesheet/less" href="./css/cards.less" type="text/css" />
		<link rel="index" title="" href="" />

		<!--[if lt IE 9 ]>
			<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]--> 
<?php if ($_SERVER['SERVER_NAME'] != "localhost") { 
		$jquerypath="http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/";
		$lesspath="//cdnjs.cloudflare.com/ajax/libs/less.js/2.7.1/";
	} else {
		$jquerypath="./js/";
		$lesspath="./js/";
}
?>
		<script src="<?= $jquerypath ?>jquery.min.js"></script>
		<script src="<?= $lesspath ?>less.min.js"></script>

	</head>
	<body class="" lang="en-US">

		<header id="primary-header">
			LOGO - Online FLiiP Game
		</header>
		