<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Estado extends Cadastro{
	function __construct($uf = NULL){
		parent::__construct();
		$this->table = "estado";
		$this->primarykey = array("uf");
		$this->setuf($uf);
		if(!is_null($this->getuf())){
			$this->searchbyobject();
		}
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getregiao(){
		return $this->fields["regiao"];
	}

	function getcodoficial(){
		return $this->fields["codoficial"];
	}

	function getconvenioicms(){
		return $this->fields["convenioicms"];
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"],2,",","") : $this->fields["aliqicms"]);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,30);
	}

	function setregiao($value){
		$this->fields["regiao"] = value_string($value,1);
	}

	function setcodoficial($value){
		$this->fields["codoficial"] = value_numeric($value);
	}

	function setconvenioicms($value){
		$this->fields["convenioicms"] = value_string($value,1);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}
}
?>