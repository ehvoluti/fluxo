<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class RelatorioColuna extends Cadastro{
	function __construct($codrelatorio = NULL, $coluna = NULL){
		parent::__construct();
		$this->table = "relatoriocoluna";
		$this->primarykey = array("codrelatorio", "coluna");
		$this->setcodrelatorio($codrelatorio);
		$this->setcoluna($coluna);
		if(!is_null($this->getcodrelatorio()) && !is_null($this->getcoluna())){
			$this->searchbyobject();
		}
	}

	function getcodrelatorio(){
		return $this->fields["codrelatorio"];
	}

	function getcoluna(){
		return $this->fields["coluna"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getalinhamento(){
		return $this->fields["alinhamento"];
	}

	function getlargura(){
		return $this->fields["largura"];
	}

	function gettotalizar(){
		return $this->fields["totalizar"];
	}

	function getvisivel(){
		return $this->fields["visivel"];
	}
	
	function getordem(){
		return $this->fields["ordem"];
	}
	
	function getquebra(){
		return $this->fields["quebra"];
	}

	function setcodrelatorio($value){
		$this->fields["codrelatorio"] = value_numeric($value);
	}

	function setcoluna($value){
		$this->fields["coluna"] = value_string($value,40);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value,40);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setalinhamento($value){
		$this->fields["alinhamento"] = value_string($value,1);
	}

	function setlargura($value){
		$this->fields["largura"] = value_numeric($value);
	}

	function settotalizar($value){
		$this->fields["totalizar"] = value_string($value,1);
	}

	function setvisivel($value){
		$this->fields["visivel"] = value_string($value,1);
	}

	function setordem($value){
		$this->fields["ordem"] = value_numeric($value);
	}
	
	function setquebra($value){
		$this->fields["quebra"] = value_numeric($value);
	}
}
?>