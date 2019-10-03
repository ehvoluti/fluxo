<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LogUpdate extends Cadastro{
	function __construct($data = NULL,$hora = NULL){
		parent::__construct();
		$this->table = "logupdate";
		$this->primarykey = array("data","hora");
		$this->setdata($data);
		$this->sethora($hora);
		if($this->getdata() != NULL && $this->gethora() != NULL){
			$this->searchbyobject();
		}
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function getip(){
		return $this->fields["ip"];
	}

	function gettotalarquivo(){
		return $this->fields["totalarquivo"];
	}

	function gettotalinstrucao(){
		return $this->fields["totalinstrucao"];
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_time($value);
	}

	function setip($value){
		$this->fields["ip"] = value_string($value,30);
	}

	function settotalarquivo($value){
		$this->fields["totalarquivo"] = value_numeric($value);
	}

	function settotalinstrucao($value){
		$this->fields["totalinstrucao"] = value_numeric($value);
	}
}
?>