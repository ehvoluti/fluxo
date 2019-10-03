<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Cest extends Cadastro{
	function __construct($idcest = NULL){
		parent::__construct();
		$this->table = "cest";
		$this->primarykey = array("idcest");
		$this->setidcest($idcest);
		if(!is_null($this->getidcest())){
			$this->searchbyobject();
		}
	}

	function getidcest(){
		return $this->fields["idcest"];
	}

	function getcest(){
		return $this->fields["cest"];
	}

	function getncm(){
		return $this->fields["ncm"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setidcest($value){
		$this->fields["idcest"] = value_numeric($value);
	}

	function setcest($value){
		$this->fields["cest"] = value_string($value,10);
	}

	function setncm($value){
		$this->fields["ncm"] = value_string($value,100);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value);
	}
}
?>