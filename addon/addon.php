<?php

require_once("websac/require_file.php");
require_file("class/connection.class.php");
require_file("def/function.php");

$dir = opendir("../addon");
while($file = readdir($dir)){
	if(substr($file, -3, 3) === ".js"){
		echo "<script type='text/javascript' src='../addon/{$file}'></script>\n";
	}
}