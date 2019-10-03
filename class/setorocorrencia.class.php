<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class SetorOcorrencia extends Cadastro{

	function __construct($codsetor = NULL){
		parent::__construct();
		$this->table = "setorocorrencia";
		$this->primarykey = array("codsetor");
		$this->newrelation("setorocorrencia","codsetor","grupoocorrencia","codgrupoocor");
		$this->setcodsetor($codsetor);
		if($this->getcodsetor() != NULL){
			$this->searchbyobject();
		}
	}

	function setcodsetor($value){
		$this->fields["codsetor"] = value_numeric($value);
	}
	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}
	function getcodsetor(){
		return $this->fields["codsetor"];
	}
	function getdescricao(){
		return $this->fields["descricao"];
	}

}


?>