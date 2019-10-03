<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class BiConfig extends Cadastro{
	function __construct($idbi = NULL){
		parent::__construct();
		$this->table = "biconfig";
		$this->primarykey = array("idbi");
		$this->setidbi($idbi);
		if(!is_null($this->getidbi())){
			$this->searchbyobject();
		}
	}

	function getidbi(){
		return $this->fields["idbi"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcategoria(){
		return $this->fields["categoria"];
	}

	function getexibir(){
		return $this->fields["exibir"];
	}

	function getordem(){
		return $this->fields["ordem"];
	}

	function setidbi($value){
		$this->fields["idbi"] = value_string($value,40);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setcategoria($value){
		$this->fields["categoria"] = value_string($value,40);
	}

	function setexibir($value){
		$this->fields["exibir"] = value_string($value,1);
	}

	function setordem($value){
		$this->fields["ordem"] = value_numeric($value);
	}
}
?>