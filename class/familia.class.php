<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Familia extends Cadastro{
	function __construct($codfamilia = NULL){
		parent::__construct();
		$this->table = "familia";
		$this->primarykey = array("codfamilia");
		$this->setcodfamilia($codfamilia);
		if(!is_null($this->getcodfamilia())){
			$this->searchbyobject();
		}
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getponto($format = FALSE){
		return ($format ? number_format($this->fields["ponto"],2,",","") : $this->fields["ponto"]);
	}

	function getcodfamilia(){
		return $this->fields["codfamilia"];
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setponto($value){
		$this->fields["ponto"] = value_numeric($value);
	}

	function setcodfamilia($value){
		$this->fields["codfamilia"] = value_numeric($value);
	}
}
?>