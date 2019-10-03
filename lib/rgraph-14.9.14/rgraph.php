<?php

$dir = opendir("../lib/rgraph-14.9.14");
while($file = readdir($dir)){
	if(substr($file, -3, 3) === ".js"){
		echo "<script type='text/javascript' src='../rgraph/{$file}'></script>\n";
	}
}