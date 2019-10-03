<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FamiliaFornec extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "familiafornec";
		$this->primarykey = "codfamfornec";
		$this->setcodfamfornec($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodfamfornec(){
		return $this->fields["codfamfornec"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodfamfornec($value){
		$this->fields["codfamfornec"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
}
?>