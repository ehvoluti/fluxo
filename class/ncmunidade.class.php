<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_once("../class/temporary.class.php");

class NCMUnidade extends Cadastro{


	function __construct($codncmunidade = NULL){
		parent::__construct();
		$this->table = "ncmunidade";
		$this->primarykey = array("codncmunidade");
		$this->setcodncmunidade($codncmunidade);
		if($this->getcodncmunidade() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodncmunidade(){
		return $this->fields["codncmunidade"];
	}

	function getcodigoncm(){
		return $this->fields["codigoncm"];
	}

	function getiniciovigencia($format= FALSE){
		return ($format ? convert_date($this->fields["iniciovigencia"], "Y-m-d", "d/m/Y") : $this->fields["iniciovigencia"]);
	}

	function getfimvigencia($format= FALSE){
		return ($format ? convert_date($this->fields["fimvigencia"], "Y-m-d", "d/m/Y") : $this->fields["fimvigencia"]);
	}

	function getalteracaovigencia($format= FALSE){
		return ($format ? convert_date($this->fields["alteracaovigencia"], "Y-m-d", "d/m/Y") : $this->fields["alteracaovigencia"]);
	}

	function getunidade(){
		return $this->fields["unidade"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodncmunidade($value){
		$this->fields["codncmunidade"] = value_numeric($value);
	}

	function setcodigoncm($value){
		$this->fields["codigoncm"] = value_string($value, 10);
	}

	function setiniciovigencia($value){
		$this->fields["iniciovigencia"] = value_date($value);
	}

	function setfimvigencia($value){
		$this->fields["fimvigencia"] = value_date($value);
	}

	function setalteracaovigencia($value){
		$this->fields["alteracaovigencia"] = value_date($value);
	}

	function setunidade($value){
		$this->fields["unidade"] = value_string($value, 10);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,30);
	}
}


