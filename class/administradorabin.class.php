<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class AdministradoraBin extends Cadastro{
	function __construct($bin = NULL,$codadminist = NULL){
		parent::__construct();
		$this->table = "administradorabin";
		$this->primarykey = array("bin","codadminist");
		$this->setbin($bin);
		$this->setcodadminist($codadminist);
		if($this->getbin() != NULL && $this->getcodadminist() != NULL){
			$this->searchbyobject();
		}
	}

	function getbin(){
		return $this->fields["bin"];
	}

	function getcodadminist(){
		return $this->fields["codadminist"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function gethoralog(){
		return $this->fields["horalog"];
	}

	function setbin($value){
		$this->fields["bin"] = value_string($value,6);
	}

	function setcodadminist($value){
		$this->fields["codadminist"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}
}

?>