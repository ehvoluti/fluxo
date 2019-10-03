<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ClienteCompl extends Cadastro{
	function __construct($codcliente = NULL, $codcomplcadastro = NULL){
		parent::__construct();
		$this->table = "clientecompl";
		$this->primarykey = array("codcliente", "codcomplcadastro");
		$this->setcodcliente($codcliente);
		$this->setcodcomplcadastro($codcomplcadastro);
		if(!is_null($this->getcodcliente()) && !is_null($this->getcodcomplcadastro())){
			$this->searchbyobject();
		}
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getcodcomplcadastro(){
		return $this->fields["codcomplcadastro"];
	}

	function getvalor(){
		return $this->fields["valor"];
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setcodcomplcadastro($value){
		$this->fields["codcomplcadastro"] = value_numeric($value);
	}

	function setvalor($value){
		$this->fields["valor"] = value_string($value);
	}
}
?>