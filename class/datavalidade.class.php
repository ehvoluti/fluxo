<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class DataValidade extends Cadastro{
	function __construct($data = NULL, $codestabelec = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = 'datavalidade';
		$this->primarykey = array("data", "codestabelec", "codproduto");
		$this->setdata($data);
		$this->setcodestabelec($codestabelec);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getdata()) && !is_null($this->getcodestabelec()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}
}
