<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/email.class.php");

class OrdemServico extends Cadastro{
	function __construct($codos = NULL){
		parent::__construct();
		$this->table = "ordemservico";
		$this->primarykey = "codos";
		$this->setcodos($codos);
		if($this->getcodos() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodos(){
		return $this->fields["codos"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getprioridade(){
		return $this->fields["prioridade"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdificuldade(){
		return $this->fields["dificuldade"];
	}

	function getanalise(){
		return $this->fields["analise"];
	}

	function getsolucao(){
		return $this->fields["solucao"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function gettecnico(){
		return $this->fields["tecnico"];
	}

	function getprogramador(){
		return $this->fields["programador"];
	}

	function getdatacadastro($format = FALSE){
		return ($format ? convert_date($this->fields["datacadastro"],"Y-m-d","d/m/Y") : $this->fields["datacadastro"]);
	}

	function gethoracadastro(){
		return $this->fields["horacadastro"];
	}

	function getdataprevisao($format = FALSE){
		return ($format ? convert_date($this->fields["dataprevisao"],"Y-m-d","d/m/Y") : $this->fields["dataprevisao"]);
	}

	function getdataconclusao($format = FALSE){
		return ($format ? convert_date($this->fields["dataconclusao"],"Y-m-d","d/m/Y") : $this->fields["dataconclusao"]);
	}

	function getdataalteracao($format = FALSE){
		return ($format ? convert_date($this->fields["dataalteracao"],"Y-m-d","d/m/Y") : $this->fields["dataalteracao"]);
	}

	function getpublicar(){
		return $this->fields["publicar"];
	}

	function getenviado(){
		return $this->fields["enviado"];
	}

	function getpublicacao(){
		return $this->fields["publicacao"];
	}

	function gethoraconclusao(){
		return $this->fields["horaconclusao"];
	}

	function getauditor(){
		return $this->fields["auditor"];
	}
	
	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = TRUE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function setcodos($value){
		$this->fields["codos"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setprioridade($value){
		$this->fields["prioridade"] = value_string($value,1);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value,100);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value);
	}

	function setdificuldade($value){
		$this->fields["dificuldade"] = value_numeric($value);
	}

	function setanalise($value){
		$this->fields["analise"] = value_string($value);
	}

	function setsolucao($value){
		$this->fields["solucao"] = value_string($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function settecnico($value){
		$this->fields["tecnico"] = value_string($value,20);
	}

	function setprogramador($value){
		$this->fields["programador"] = value_string($value,20);
	}

	function setdatacadastro($value){
		$this->fields["datacadastro"] = value_date($value);
	}

	function setdataalteracao($value){
		$this->fields["dataalteracao"] = value_date($value);
	}

	function sethoracadastro($value){
		$this->fields["horacadastro"] = value_time($value);
	}

	function setdataprevisao($value){
		$this->fields["dataprevisao"] = value_date($value);
	}

	function setdataconclusao($value){
		$this->fields["dataconclusao"] = value_date($value);
	}

	function setpublicar($value){
		$this->fields["publicar"] = value_string($value,1);
	}

	function setenviado($value){
		$this->fields["enviado"] = value_string($value,1);
	}

	function setpublicacao($value){
		$this->fields["publicacao"] = value_string($value);
	}

	function sethoraconclusao($value){
		$this->fields["horaconclusao"] = value_time($value);
	}

	function setauditor($value){
		$this->fields["auditor"] = value_string($value,20);
	}
	
	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>