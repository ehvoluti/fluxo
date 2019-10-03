<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LogEtiqueta extends Cadastro{
	function __construct($codestabelec = NULL, $codproduto = NULL, $data = NULL, $hora = NULL){
		parent::__construct();
		$this->table = "logetiqueta";
		$this->primarykey = array("codestabelec", "codproduto", "data", "hora");
		$this->setcodestabelec($codestabelec);
		$this->setcodproduto($codproduto);
		$this->setdata($data);
		$this->sethora($hora);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getcodproduto()) && !is_null($this->getdata()) && !is_null($this->gethora())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}

	function getqtdeetiqueta(){
		return $this->fields["qtdeetiqueta"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_string($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function setqtdeetiqueta($value){
		$this->fields["qtdeetiqueta"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}
}
?>