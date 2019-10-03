<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Premio extends Cadastro{
	function __construct($codpremio = NULL){
		parent::__construct();
		$this->table = "premio";
		$this->primarykey = array("codpremio");
		$this->setcodpremio($codpremio);
		if(!is_null($this->getcodpremio())){
			$this->searchbyobject();
		}
	}

	function getcodpremio(){
		return $this->fields["codpremio"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getqtdevenda(){
		return $this->fields["qtdevenda"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getativo(){
		return $this->fields["ativo"];
	}

	function getcodtpdocto(){
		return $this->fields["codtpdocto"];
	}

	function setcodpremio($value){
		$this->fields["codpremio"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,120);
	}

	function setqtdevenda($value){
		$this->fields["qtdevenda"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setativo($value){
		$this->fields["ativo"] = value_string($value,1);
	}

	function setcodtpdocto($value){
		$this->fields["codtpdocto"] = value_numeric($value);
	}
}
?>