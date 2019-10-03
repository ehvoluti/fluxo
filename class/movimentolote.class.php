<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class MovimentoLote extends Cadastro{
	function __construct($codlote = NULL){
		parent::__construct();
		$this->table = "movimentolote";
		$this->primarykey = array("codlote");
		$this->setcodlote($codlote);
		if(!is_null($this->getcodlote())){
			$this->searchbyobject();
		}
	}

	function getcodlote(){
		return $this->fields["codlote"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function gethrmovto(){
		return $this->fields["hrmovto"];
	}

	function getcodtpdocto(){
		return $this->fields["codtpdocto"];
	}

	function gettipoembal(){
		return $this->fields["tipoembal"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}
	
	function gettipolote(){
		return $this->fields["tipolote"];
	}

	function setcodlote($value){
		$this->fields["codlote"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function sethrmovto($value){
		$this->fields["hrmovto"] = value_string($value);
	}

	function setcodtpdocto($value){
		$this->fields["codtpdocto"] = value_numeric($value);
	}

	function settipoembal($value){
		$this->fields["tipoembal"] = value_string($value,1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
	
	function settipolote($value){
		$this->fields["tipolote"] = value_string($value,1);
	}
}
?>