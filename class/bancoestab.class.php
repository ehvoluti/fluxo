<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class BancoEstab extends Cadastro{
	function __construct($codbanco = NULL,$codestabelec = NULL){
		parent::__construct();
		$this->table = "bancoestab";
		$this->primarykey = array("codestabelec","codbanco");
		$this->setcodbanco($codbanco);
		$this->setcodestabelec($codestabelec);
		if($this->getcodbanco() != NULL && $this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdisponivel(){
		return $this->fields["disponivel"];
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdisponivel($value){
		$this->fields["disponivel"] = value_string($value,1);
	}
}