<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Embalagem extends Cadastro{
	function __construct($codembal = NULL){
		parent::__construct();
		$this->table = "embalagem";
		$this->primarykey = array("codembal");
		$this->newrelation("embalagem","codunidade","unidade","codunidade");
		$this->setcodembal($codembal);
		if(!is_null($this->getcodembal())){
			$this->searchbyobject();
		}
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getcodembal(){
		return $this->fields["codembal"];
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setcodembal($value){
		$this->fields["codembal"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}
}
?>