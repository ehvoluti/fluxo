<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GrupoRestricao extends Cadastro{
	function __construct($codgrupo = NULL, $codrestricao = NULL){
		parent::__construct();
		$this->table = "gruporestricao";
		$this->primarykey = array("codgrupo", "codrestricao");
		$this->setcodgrupo($codgrupo);
		$this->setcodrestricao($codrestricao);
		if(!is_null($this->getcodgrupo()) && !is_null($this->getcodrestricao())){
			$this->searchbyobject();
		}
	}

	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getcodrestricao(){
		return $this->fields["codrestricao"];
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setcodrestricao($value){
		$this->fields["codrestricao"] = value_string($value,20);
	}
}
?>