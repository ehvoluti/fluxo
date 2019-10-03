<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FuncMeta extends Cadastro{
	function __construct($codfunc = NULL, $mes = NULL, $ano = NULL){
		parent::__construct();
		$this->table = "funcmeta";
		$this->primarykey = array("codfunc", "mes", "ano");
		$this->setcodfunc($codfunc);
		$this->setmes($mes);
		$this->setano($ano);
		if(!is_null($this->getcodfunc()) && !is_null($this->getano())){
			$this->searchbyobject();
		}
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getmes(){
		return $this->fields["mes"];
	}

	function getano(){
		return $this->fields["ano"];
	}

	function getvalormetafunc(){
		return $this->fields["valormetafunc"];
	}

	function getvalormetageral(){
		return $this->fields["valormetageral"];
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setmes($value){
		$this->fields["mes"] = value_numeric($value);
	}

	function setano($value){
		$this->fields["ano"] = value_numeric($value);
	}

	function setvalormetafunc($value){
		$this->fields["valormetafunc"] = value_numeric($value);
	}

	function setvalormetageral($value){
		$this->fields["valormetageral"] = value_numeric($value);
	}
}
?>