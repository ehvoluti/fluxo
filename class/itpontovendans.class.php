<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItPontoVendaNs extends Cadastro{
	function __construct($login = NULL, $codproduto = NULL, $numeroserie = NULL){
		parent::__construct();
		$this->table = "itpontovendans";
		$this->primarykey = array("login", "codproduto", "numeroserie");
		$this->setlogin($login);
		$this->setcodproduto($codproduto);
		$this->setnumeroserie($numeroserie);
		if(!is_null($this->getlogin()) && !is_null($this->getcodproduto()) && !is_null($this->getnumeroserie())){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getnumeroserie(){
		return $this->fields["numeroserie"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setnumeroserie($value){
		$this->fields["numeroserie"] = value_string($value,50);
	}
}
?>