<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GrupoFatmto extends Cadastro{
	function __construct($codgrfat = NULL){
		parent::__construct();
		$this->table = "grupofatmto";
		$this->primarykey = "codgrfat";
		$this->setcodgrfat($codgrfat);
		if($this->getcodgrfat() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodgrfat(){
		return $this->fields["codgrfat"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdiacorte($format = FALSE){
		return $this->fields["diacorte"];
	}

	function getdiavencto($format = FALSE){
		return $this->fields["diavencto"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function setcodgrfat($value){
		$this->fields["codgrfat"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setdiacorte($value){
		$this->fields["diacorte"] = value_numeric($value);
	}

	function setdiavencto($value){
		$this->fields["diavencto"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>