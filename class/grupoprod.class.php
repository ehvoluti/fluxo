<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GrupoProd extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->newrelation("grupoprod","coddepto","departamento","coddepto");
		$this->table = "grupoprod";
		$this->primarykey = "codgrupo";
		$this->setcodgrupo($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcoddepto(){
		return $this->fields["coddepto"];
	}

	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}
	
	function getnummesesforalin(){
		return $this->fields["nummesesforalin"];
	}

	function getnummesesexc(){
		return $this->fields["nummesesexc"];
	}
	
	function setcoddepto($value){
		$this->fields["coddepto"] = value_numeric($value);
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
	
	function setnummesesforalin($value){
		$this->fields["nummesesforalin"] = value_numeric($value);
	}
	
	function setnummesesexc($value){
		$this->fields["nummesesexc"] = value_numeric($value);
	}
}
?>