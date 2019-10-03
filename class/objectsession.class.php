<?php

require_once("websac/require_file.php");
require_file("def/function.php");

final class ObjectSession{

	private $con;
	private $table;
	private $id;
	private $arr_object;

	function __construct($con, $table, $id){
		$this->con = $con;
		$this->table = $table;
		$this->id = $id;

		require_table_class($table);

		$this->clear();

		if(isset($_SESSION["OBJECT"][$this->id])){
			$this->load();
		}
	}

	function addobject($arr_object){
		if(!is_array($arr_object)){
			$arr_object = array($arr_object);
		}
		foreach($arr_object as $object){
			$a = $object->gettablename();
			$b = $this->table;
			if(is_object($object) && $object->gettablename() == $this->table){
				$object->setconnection($this->con);
				$this->arr_object[] = $object;
			}
		}
	}

	function clear(){
		$this->arr_object = array();
	}

	function getobject($i = null){
		if(is_null($i)){
			return $this->arr_object;
		}else{
			return $this->arr_object[$i];
		}
	}

	function length(){
		return count($this->arr_object);
	}

	function load(){
		$this->arr_object = unserialize($_SESSION["OBJECT"][$this->id]);
		foreach($this->arr_object as $object){
			$object->setconnection($this->con);
		}
	}

	function removeobject($i){
		unset($this->arr_object[$i]);
	}

	function save(){
		// Remove a conexao com o banco de dados dos objetos
		foreach($this->arr_object as $i => $object){
			$this->arr_object[$i]->setconnection(null);
		}

		// Transforma os objetos em conteudo de texto
		$serialized = serialize($this->arr_object);

		// Grava os objetos na sessao
		$_SESSION["OBJECT"][$this->id] = $serialized;

		// Adiciona a conexao com o banco de dados de volta nos objetos
		foreach($this->arr_object as $i => $object){
			$this->arr_object[$i]->setconnection($this->con);
		}

		return true;
	}

	function search($column, $value, $start = 0){
		if(!is_array($column) && !is_array($value)){
			$column = array($column);
			$value = array($value);
		}
		$found = FALSE;
		$c = 0;
		foreach($this->arr_object as $i => $object){
			if($c >= $start){
				for($j = 0; $j < sizeof($column); $j++){
					if(call_user_func(array($object, "get".$column[$j])) != $value[$j]){
						$found = FALSE;
						break;
					}
					$found = TRUE;
				}
				if($found){
					break;
				}
			}
			$c++;
		}
		return ($found ? $i : FALSE);
	}

	function setobject($i, $object){
		if(is_object($object)){
			$this->arr_object[$i] = $object;
		}
	}

}