<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ClienteInteresse extends Cadastro{
	function __construct($codcliente = NULL,$codinteresse = NULL){
		parent::__construct();
		$this->table = "clienteinteresse";
		$this->primarykey = array("codcliente","codinteresse");
		$this->setcodcliente($codcliente);
		$this->setcodinteresse($codinteresse);
		if($this->getcodcliente() != NULL && $this->getcodinteresse() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getcodinteresse(){
		return $this->fields["codinteresse"];
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setcodinteresse($value){
		$this->fields["codinteresse"] = value_numeric($value);
	}
}
?>