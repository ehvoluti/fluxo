<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Concorrente extends Cadastro{
	function __construct($codconcorrente = NULL){
		parent::__construct();
		$this->table = "concorrente";
		$this->primarykey = array("codconcorrente");
		$this->setcodconcorrente($codconcorrente);
		if(!is_null($this->getcodconcorrente())){
			$this->searchbyobject();
		}
	}

	function getcodconcorrente(){
		return $this->fields["codconcorrente"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getfone(){
		return $this->fields["fone"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function getsite(){
		return $this->fields["site"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getjquerypreco(){
		return $this->fields["jquerypreco"];
	}
	
	function getativo(){
		return $this->fields["ativo"];
	}

	function setcodconcorrente($value){
		$this->fields["codconcorrente"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,60);
	}

	function setfone($value){
		$this->fields["fone"] = value_string($value,20);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,100);
	}

	function setsite($value){
		$this->fields["site"] = value_string($value,150);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setjquerypreco($value){
		$this->fields["jquerypreco"] = value_string($value,200);
	}
	
	function setativo($value){
		$this->fields["ativo"] = value_string($value,1);
	}
}
?>