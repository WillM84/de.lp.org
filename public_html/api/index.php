<?php

	error_reporting(E_ALL);
	ini_set('display_errors','1');
	
	define("ROOT_PATH", $_SERVER['DOCUMENT_ROOT']."/../");
	define("CLASSPATH", ROOT_PATH."resources/");

	$autoloader = CLASSPATH."autoload.php";

	require_once $autoloader;
	
	if (!empty($_POST)) echo $_POST['query']."<br />".(($x = $connection->query($_POST['query'])) !== false?$x:"FAILED!");
	
?>
<form action="#" method="POST">
	<input name="query"/>
</form>
