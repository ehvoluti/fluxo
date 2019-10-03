<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/email.class.php");

class OrdemServicoTeste extends Cadastro{
	function __construct($codos = NULL){
		parent::__construct();
		$this->table = "ordemservicoteste";
		$this->primarykey = "idteste";
		$this->setidteste($idteste);
		if($this->getcodos() != NULL){
			$this->searchbyobject();
		}
	}

	function getidteste(){
		return $this->fields["idteste"];
	}
	
	function getcodos(){
		return $this->fields["codos"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}
	
	function getdatacadastro($format = FALSE){
		return ($format ? convert_date($this->fields["datacadastro"],"Y-m-d","d/m/Y") : $this->fields["datacadastro"]);
	}
	
	function getstatus(){
		return $this->fields["status"];
	}
	
	function setidteste($value){
		$this->fields["idteste"] = value_numeric($value);
	}

	function setcodos($value){
		$this->fields["codos"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value);
	}

	function setdatacadastro($value){
		$this->fields["datacadastro"] = value_date($value);
	}
	
	function setstatus($value){
		$this->fields["status"] = value_string($value);
	}
}
?>