<?php
require_once("websac/require_file.php");
require_file("def/function.php");

final class Temporary{
	private $address;
	private $columns;
	private $name;
	private $lines;

	private $char_separator = "-";
	private $column_separator = ";";
	private $line_separator = "	";

	function __construct($name, $new = true){
		$this->name = $name;
		$this->lines = array();
		if(!$new && isset($_SESSION["TEMPORARY"][$this->name])){
			$this->load();
		}
	}

    function addcolumn($column){
        if(!is_array($this->columns)){
            $this->columns = array();
        }
        if(!in_array($column,$this->columns)){
            $this->columns[] = $column;
        }
    }

	function append(){
		$i = sizeof($this->lines);
		foreach($this->columns as $column){
			$this->lines[$i][$column] = NULL;
		}
	}

	function asarray(){
		return $this->lines;
	}

	function code($str){
		$arr = array();
		for($i = 0; $i < strlen($str); $i++){
			$arr[] = ord(substr($str,$i,1));
		}
		return implode($this->char_separator,$arr);
	}

	function decode($str){
		$arr = explode($this->char_separator,$str);
		$str = "";
		foreach($arr as $ord){
			if($ord != 0){
				$str .= chr($ord);
			}
		}
		return $str;
	}

	function getcolumns(){
		return $this->columns;
	}

	function getvalue($line,$column){
		if($line === "last"){
			$line = $this->length() - 1;
		}
		return $this->lines[$line][$column];
	}

	function length(){
		return sizeof($this->lines);
	}

	function load(){
		$this->columns = array();
		$this->lines = array();
		foreach($_SESSION["TEMPORARY"][$this->name]["COLUMN"] as $column){
			$this->columns[] = $column;
		}
		foreach($_SESSION["TEMPORARY"][$this->name] as $i => $line){
			if($i !== "COLUMN"){
				foreach($line as $column => $value){
					$this->lines[$i][$column] = $value;
				}
			}
		}
	}

    function orderby($column){
        $arr_aux = array();
        foreach($this->lines as $i => $line){
            $arr_aux[$i] = $line[$column];
        }
        asort($arr_aux);
        $arr_line = array();
        foreach($arr_aux as $i => $val){
            $arr_line[] = $this->lines[$i];
        }
        $this->lines = $arr_line;
    }

	function preformated(){
		$style = "style=\"font-family:monospace; font-size:10pt\"";
		echo "<p ".$style.">Total de linhas: ".sizeof($this->lines);
		echo "<pre ".$style.">";
		print_r($this->lines);
		echo "</pre>";
		echo "</p>";
	}

	function remove($line){
		$arr = array();
		foreach($this->lines as $i => $line_arr){
			if($i != $line){
				$arr[] = $line_arr;
			}
		}
		$this->lines = $arr;
	}

	function save(){
		$_SESSION["TEMPORARY"][$this->name] = array();
		foreach($this->columns as $column){
			$_SESSION["TEMPORARY"][$this->name]["COLUMN"][] = $column;
		}
		if(sizeof($this->lines) > 0){
			foreach($this->lines as $i => $line){
				foreach($this->columns as $column){
					$_SESSION["TEMPORARY"][$this->name][$i][$column] = $line[$column];
				}
			}
		}
	}

	function search($column,$value,$start = 0,$compare_type = FALSE){
		if(!is_array($column) && !is_array($value)){
			$column = array($column);
			$value = array($value);
		}
		$found = FALSE;
		for($i = $start; $i < $this->length(); $i++){
			for($j = 0; $j < sizeof($column); $j++){
                if($compare_type){
                    if($this->getvalue($i,$column[$j]) !== $value[$j]){
                        $found = FALSE;
                        break;
                    }
                }else{
                    if($this->getvalue($i,$column[$j]) != $value[$j]){
                        $found = FALSE;
                        break;
                    }
                }
				$found = TRUE;
			}
			if($found){
				break;
			}
		}
		return ($found ? $i : FALSE);
	}

	function setcolumns($columns){
		if(is_array($columns)){
			$this->columns = $columns;
		}else{
			$this->columns = NULL;
		}
	}

	function setvalue($line,$column,$value){
		if($line === "last"){
			$line = $this->length() - 1;
		}
		if(is_array($this->lines[$line]) && in_array($column,$this->columns)){
			$this->lines[$line][$column] = (string) $value;
		}
	}
}
