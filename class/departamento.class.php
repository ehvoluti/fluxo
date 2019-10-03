<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_once("../class/grupoprod.class.php");

class Departamento extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->newrelation("departamento","codfunc","funcionario","codfunc");
		$this->table = "departamento";
		$this->primarykey = "coddepto";
		$this->setcoddepto($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcoddepto(){
		return $this->fields["coddepto"];
	}

	function getnome(){
		return $this->fields["nome"];
	}
	
	function getcodfunc(){
		return $this->fields["codfunc"];
	}
	
	function getmargemvrj($format = FALSE){
		return ($format ? number_format($this->fields["margemvrj"],2,",","") : $this->fields["margemvrj"]);
	}
	
	function setcoddepto($value){
		$this->fields["coddepto"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}
	
	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}
	
	function setmargemvrj($value){
		$this->fields["margemvrj"] = value_numeric($value);
	}
}
?>