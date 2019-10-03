<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Parametro extends Cadastro{
	function __construct($idparam = NULL,$codparam = NULL){
		parent::__construct();
		$this->table = "parametro";
		$this->primarykey = array("idparam","codparam");
		$this->setidparam($idparam);
		$this->setcodparam($codparam);
		if($this->getidparam() != NULL && $this->getcodparam() != NULL){
			$this->searchbyobject();
		}
	}

	function getidparam(){
		return $this->fields["idparam"];
	}

	function getcodparam(){
		return $this->fields["codparam"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getvalor(){
		return $this->fields["valor"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function setidparam($value){
		$this->fields["idparam"] = value_string($value,20);
	}

	function setcodparam($value){
		$this->fields["codparam"] = value_string($value,20);
	}

	function setvalor($value){
		$this->fields["valor"] = value_string(rtrim($value));
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>