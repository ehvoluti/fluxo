<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TipoMensagem extends Cadastro{
	function __construct($codtipomensagem = NULL){
		parent::__construct();
		$this->table = "tipomensagem";
		$this->primarykey = array("codtipomensagem");
		$this->setcodtipomensagem($codtipomensagem);
		if(!is_null($this->getcodtipomensagem())){
			$this->searchbyobject();
		}
	}

	function getcodtipomensagem(){
		return $this->fields["codtipomensagem"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodtipomensagem($value){
		$this->fields["codtipomensagem"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
}
?>