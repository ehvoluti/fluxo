<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class PontoVenda extends Cadastro{
	function __construct($login = NULL){
		parent::__construct();
		$this->table = "pontovenda";
		$this->primarykey = array("login");
		$this->setlogin($login);
		if(!is_null($this->getlogin())){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}
    
    function getnatoperacao(){
		return $this->fields["natoperacao"];
	}
    
    function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}
    
    function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value,9);
	}
    
	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}
}
?>