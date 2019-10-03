<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItRma extends Cadastro{
	function __construct($codrma = NULL, $iditnotafiscal = NULL){
		parent::__construct();
		$this->table = "itrma";
		$this->primarykey = array("codrma", "iditnotafiscal");
		$this->setcodrma($codrma);
		$this->setiditnotafiscal($iditnotafiscal);
		if(!is_null($this->getcodrma()) && !is_null($this->getiditnotafiscal())){
			$this->searchbyobject();
		}
	}

	function getcodrma(){
		return $this->fields["codrma"];
	}

	function getiditnotafiscal(){
		return $this->fields["iditnotafiscal"];
	}

	function getquantidade(){
		return $this->fields["quantidade"];
	}

	function getnumeroserie(){
		return $this->fields["numeroserie"];
	}

	function setcodrma($value){
		$this->fields["codrma"] = value_numeric($value);
	}

	function setiditnotafiscal($value){
		$this->fields["iditnotafiscal"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setnumeroserie($value){
		$this->fields["numeroserie"] = value_string($value,50);
	}
}
?>