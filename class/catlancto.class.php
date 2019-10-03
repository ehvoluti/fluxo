<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class CatLancto extends Cadastro{
	function __construct($codcatlancto = NULL){
		parent::__construct();
		$this->table = "catlancto";
		$this->primarykey = "codcatlancto";
		$this->setcodcatlancto($codcatlancto);
		if($this->getcodcatlancto() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdebcred(){
		return $this->fields["debcred"];
	}	

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setdebcred($value){
		$this->fields["debcred"] = value_string($value,1);
	}
}
?>