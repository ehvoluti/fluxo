<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GrupoOcorrencia extends Cadastro{
	function __construct($codgrupoocor = NULL){
		parent::__construct();
		$this->table = "grupoocorrencia";
		$this->primarykey = array("codgrupoocor");
		$this->setcodgrupoocor($codgrupoocor);
		if(!is_null($this->getcodgrupoocor())){
			$this->searchbyobject();
		}
	}

	function getcodgrupoocor(){
		return $this->fields["codgrupoocor"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcodsetor(){
		return $this->fields["codsetor"];
	}

	function setcodgrupoocor($value){
		$this->fields["codgrupoocor"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setcodsetor($value){
		$this->fields["codsetor"] = value_numeric($value);
	}
}
?>