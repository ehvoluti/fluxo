<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Receita extends Cadastro{
	function __construct($codreceita = NULL){
		parent::__construct();
		$this->table = "receita";
		$this->primarykey = array("codreceita");
		$this->setcodreceita($codreceita);
		if(!is_null($this->getcodreceita())){
			$this->searchbyobject();
		}
	}

	function getcodreceita(){
		return $this->fields["codreceita"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcomponentes(){
		return $this->fields["componentes"];
	}

	function getmodopreparo(){
		return $this->fields["modopreparo"];
	}

	function setcodreceita($value){
		$this->fields["codreceita"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setcomponentes($value){
		$this->fields["componentes"] = value_string($value);
	}

	function setmodopreparo($value){
		$this->fields["modopreparo"] = value_string($value);
	}
}
?>