<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class RelatorioFiltro extends Cadastro{
	function __construct($codrelatorio = NULL, $coluna = NULL){
		parent::__construct();
		$this->table = "relatoriofiltro";
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

	function getcombobox(){
		return $this->fields["combobox"];
	}

	function getatributo(){
		return $this->fields["atributo"];
	}
	
	function getmascara(){
		return $this->fields["mascara"];
	}
	
	function getobrigatorio(){
		return $this->fields["obrigatorio"];
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

	function setcombobox($value){
		$this->fields["combobox"] = value_string($value,40);
	}

	function setatributo($value){
		$this->fields["atributo"] = value_string($value,40);
	}

	function setmascara($value){
		$this->fields["mascara"] = value_string($value,20);
	}
	
	function setobrigatorio($value){
		$this->fields["obrigatorio"] = value_string($value,1);
	}
}
?>