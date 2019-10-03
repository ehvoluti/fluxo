<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Lajava extends Cadastro{
	function __construct($idarquivo = NULL){
		parent::__construct();
		$this->table = "lajava";
		$this->primarykey = array("idarquivo");
		$this->setidarquivo($idarquivo);
		if($this->getidarquivo() != NULL){
			$this->searchbyobject();
		}
	}

	function getidarquivo(){
		return $this->fields["nome"];
	}

	function gettexto(){
		return $this->fields["texto"];
	}

	function getlocal(){
		return $this->fields["local"];
	}

	function setidarquivo($value){
		$this->fields["idarquivo"] = value_numeric($value);
	}

	function settexto($value){
		$this->fields["texto"] = value_string($value,300);
	}

	function setlocal($value){
		$this->fields["local"] = value_string($value,100);
	}
}
?>