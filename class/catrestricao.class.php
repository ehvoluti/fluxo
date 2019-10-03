<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class CatRestricao extends Cadastro{
	function __construct($codcatrestricao = NULL){
		parent::__construct();
		$this->table = "catrestricao";
		$this->primarykey = array("codcatrestricao");
		$this->setcodcatrestricao($codcatrestricao);
		if(!is_null($this->getcodcatrestricao())){
			$this->searchbyobject();
		}
	}

	function getcodcatrestricao(){
		return $this->fields["codcatrestricao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function setcodcatrestricao($value){
		$this->fields["codcatrestricao"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}
}
?>