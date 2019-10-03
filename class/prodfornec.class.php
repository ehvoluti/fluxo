<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdFornec extends Cadastro{

	function __construct($codprodfornec = NULL){
		parent::__construct();
		$this->table = "prodfornec";
		$this->primarykey = array("codprodfornec");
		$this->setcodprodfornec($codprodfornec);
		if($this->getcodprodfornec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getreffornec(){
		return $this->fields["reffornec"];
	}

	function getcodprodfornec(){
		return $this->fields["codprodfornec"];
	}

	function getprincipal(){
		return $this->fields["principal"];
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setreffornec($value){
		$this->fields["reffornec"] = value_string($value, 30);
	}

	function setcodprodfornec($value){
		$this->fields["codprodfornec"] = value_numeric($value);
	}

	function setprincipal($value){
		$this->fields["principal"] = value_string($value, 1);
	}

}