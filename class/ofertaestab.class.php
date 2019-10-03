<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class OfertaEstab extends Cadastro{
	function __construct($codoferta = NULL, $codestabelec = NULL){
		parent::__construct();
		$this->table = "ofertaestab";
		$this->primarykey = array("codoferta", "codestabelec");
		$this->setcodoferta($codoferta);
		$this->setcodestabelec($codestabelec);
		if(!is_null($this->getcodoferta()) && !is_null($this->getcodestabelec())){
			$this->searchbyobject();
		}
	}

	function getcodoferta(){
		return $this->fields["codoferta"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function setcodoferta($value){
		$this->fields["codoferta"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}
}
?>