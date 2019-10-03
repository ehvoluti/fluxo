<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Marca extends Cadastro{

	function __construct($key = NULL){
		parent::__construct();
		$this->table = "marca";
		$this->primarykey = "codmarca";
		$this->setcodmarca($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}

	function getcodmarca(){
		return $this->fields["codmarca"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getpropria(){
		return $this->fields["propria"];
	}

	function setcodmarca($value){
		$this->fields["codmarca"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 40);
	}

	function setpropria($value){
		$this->fields["propria"] = value_check($value, array("S", "N"));
	}

}