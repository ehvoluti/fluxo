<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Comissao extends Cadastro{
	function __construct($codcomissao = NULL){
		parent::__construct();
		$this->table = "comissao";
		$this->primarykey = "codcomissao";
		$this->setcodcomissao($codcomissao);
		if($this->getcodcomissao() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcomissao(){
		return $this->fields["codcomissao"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodlanctopai(){
		return $this->fields["codlanctopai"];
	}

	function getcodlanctofilho(){
		return $this->fields["codlanctofilho"];
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getidcupom(){
		return $this->fields["idcupom"];
	}

	function getdatavenda($format = FALSE){
		return ($format ? convert_date($this->fields["datavenda"],"Y-m-d","d/m/Y") : $this->fields["datavenda"]);
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getcomissao($format = FALSE){
		return ($format ? number_format($this->fields["comissao"],2,",","") : $this->fields["comissao"]);
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcodrepresentante(){
		return $this->fields["codrepresentante"];
	}

	function setcodcomissao($value){
		$this->fields["codcomissao"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodlanctopai($value){
		$this->fields["codlanctopai"] = value_numeric($value);
	}

	function setcodlanctofilho($value){
		$this->fields["codlanctofilho"] = value_numeric($value);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setidcupom($value){
		$this->fields["idcupom"] = value_numeric($value);
	}

	function setdatavenda($value){
		$this->fields["datavenda"] = value_date($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setcomissao($value){
		$this->fields["comissao"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setcodrepresentante($value){
		$this->fields["codrepresentante"] = value_numeric($value);
	}
}
?>