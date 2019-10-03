<?php
require_once("websac/require_file.php");
require_file("def/require_php.php");

$query = $_REQUEST["query"];

$con = new Connection();

if(substr(strtoupper(trim($query)),0,6) == "SELECT"){
	$res = $con->query($query);
	if($res === false){
		var_dump($con->errorInfo());
		die;
	}
	$arr = $res->fetchAll(PDO::FETCH_ASSOC);

	$grid = new Grid();
	$align = [];
	$width = [];
	foreach($arr AS $row){
		$grid->addrow($row);
	}

	foreach(array_keys($row) As $key){
		if(is_numeric($row[$key])){
			$aux_align = "right";
			$aux_width = "100px";
			if(strlen($row[$key]) < 10){
				if(strlen($key) < 10){
					$aux_width = "50px";
				}else{
					$aux_width = "100px";
				}
			}
		}elseif(strlen($row[$key]) < 10){
			$aux_align = "left";
			if(strlen($key) < 10){
				$aux_width = "50px";
			}else{
				$aux_width = "100px";
			}

		}else{
			$aux_align = "left";
			$aux_width = "200px";
		}

		if(strlen($row[$key]) == 1){
			$aux_align = "center";
		}

		$align[] = $aux_align;
		$width[] = $aux_width;
	}

	$grid->setheader(array_keys($row));
	$grid->setcolumnsalign($align);
	$grid->setcolumnswidth($width);

	echo $grid->draw();
}else{
	$con->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
	$query = str_replace(array("</div>","<br>","<div>","&nbsp;"), " ", $query);
	//$query = utf8_decode(htmlspecialchars($query));
	$query = utf8_decode($query);
	$res = $con->query($query);

	if($res === false){
		var_dump($con->errorInfo());
		die;
	}else{
		echo "Sucesso";
	}
}