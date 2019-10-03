<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LogPagina extends Cadastro{
	function __construct($codlogpagina = NULL){
		parent::__construct();
		$this->table = "logpagina";
		$this->primarykey = "codlogpagina";
		$this->setcodlogpagina($codlogpagina);
		if($this->getcodlogpagina() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodlogpagina(){
		return $this->fields["codlogpagina"];
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function gettitle(){
		return $this->fields["title"];
	}

	function getidtable(){
		return $this->fields["idtable"];
	}

	function getdata(){
		return $this->fields["data"];
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function setcodlogpagina($value){
		$this->fields["codlogpagina"] = value_numeric($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function settitle($value){
		$this->fields["title"] = value_string($value,100);
	}

	function setidtable($value){
		$this->fields["idtable"] = value_string($value,30);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_time($value);
	}
}
?>