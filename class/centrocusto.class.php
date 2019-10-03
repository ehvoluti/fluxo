<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class CentroCusto extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "centrocusto";
		$this->primarykey = "codccusto";
		$this->setcodccusto($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodccusto(){
		return $this->fields["codccusto"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function setcodccusto($value){
		$this->fields["codccusto"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>