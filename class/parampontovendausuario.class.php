<?php
require_file("class/cadastro.class.php");

class ParamPontoVendaUsuario extends Cadastro{
	function __construct($codestabelec = NULL, $login = NULL){
		parent::__construct();
		$this->table = "parampontovendausuario";
		$this->primarykey = array("codestabelec", "login");
		$this->setcodestabelec($codestabelec);
		$this->setlogin($login);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getlogin())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getlocalimpressora(){
		return $this->fields["localimpressora"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setlocalimpressora($value){
		$this->fields["localimpressora"] = value_string($value,200);
	}
}
?>