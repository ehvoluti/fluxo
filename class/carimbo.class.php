<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Carimbo extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "carimbo";
		$this->primarykey = "codcarimbo";
		$this->setcodcarimbo($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodcarimbo(){
		return $this->fields["codcarimbo"];
	}

	function getdescrreduzida(){
		return $this->fields["descrreduzida"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function setcodcarimbo($value){
		$this->fields["codcarimbo"] = value_numeric($value);
	}

	function setdescrreduzida($value){
		$this->fields["descrreduzida"] = value_string($value,40);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,40);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>