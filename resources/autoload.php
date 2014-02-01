<?php

	error_reporting(E_ALL);
	ini_set('display_errors','1');

	$stages = __DIR__."/stages.php";
	$UCL = __DIR__."/libs/ClassLoader/UniversalClassLoader.php";
	
	require($stages);
	require($UCL);
	
	session_start();
	
	$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
	$loader->registerNamespaces(
		array
		(
			'Slim'	=> __DIR__ . '/libs/slim/slim',
			'db'	=> __DIR__ . '/db'
		)
	);
	$loader->register();
	
	function autoload($class) 
	{
		$parts = explode('\\',$class);
		$path = "";
		
		foreach ($parts as $part)
			$path .= $part .= DIRECTORY_SEPARATOR;
		
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		
		$file = __DIR__.DIRECTORY_SEPARATOR.$path.".php";
		
		if (!file_exists($file))
		{
			throw new Exception("Unable to autoload class $class.  File $file does not exist in ".__DIR__, 1);
		}
		else
		{
			require_once $file;
		}
	}
	spl_autoload_register('autoload');



	//Global Variables
	$app = new \Slim\Slim();
	$connection = new \db\Connection($database);

?>