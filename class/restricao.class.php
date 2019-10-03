<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Restricao extends Cadastro{
	function __construct($codrestricao = NULL){
		parent::__construct();
		$this->table = "restricao";
		$this->primarykey = array("codrestricao");
		$this->setcodrestricao($codrestricao);
		if(!is_null($this->getcodrestricao())){
			$this->searchbyobject();
		}
	}

	function getcodrestricao(){
		return $this->fields["codrestricao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getcodcatrestricao(){
		return $this->fields["codcatrestricao"];
	}

	function setcodrestricao($value){
		$this->fields["codrestricao"] = value_string($value,20);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setcodcatrestricao($value){
		$this->fields["codcatrestricao"] = value_numeric($value);
	}
}
?>