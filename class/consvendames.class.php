<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ConsVendaMes extends Cadastro{
	function __construct($codestabelec = NULL,$ano = NULL,$mes = NULL,$codproduto = NULL){
		parent::__construct();
		$this->table = "consvendames";
		$this->primarykey = array("codestabelec","ano","mes","codproduto");
		$this->setcodestabelec($codestabelec);
		$this->setano($ano);
		$this->setmes($mes);
		$this->setcodproduto($codproduto);
		if($this->getcodestabelec() != NULL && $this->getano() != NULL && $this->getmes() != NULL && $this->getcodproduto() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getano(){
		return $this->fields["ano"];
	}

	function getmes(){
		return $this->fields["mes"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],2,",","") : $this->fields["quantidade"]);
	}

	function getvenda($format = FALSE){
		return ($format ? number_format($this->fields["venda"],2,",","") : $this->fields["venda"]);
	}

	function getcusto($format = FALSE){
		return ($format ? number_format($this->fields["custo"],2,",","") : $this->fields["custo"]);
	}

	function getdesconto($format = FALSE){
		return ($format ? number_format($this->fields["desconto"],2,",","") : $this->fields["desconto"]);
	}

	function getacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["acrescimo"],2,",","") : $this->fields["acrescimo"]);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setano($value){
		$this->fields["ano"] = value_numeric($value);
	}

	function setmes($value){
		$this->fields["mes"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setvenda($value){
		$this->fields["venda"] = value_numeric($value);
	}

	function setcusto($value){
		$this->fields["custo"] = value_numeric($value);
	}

	function setdesconto($value){
		$this->fields["desconto"] = value_numeric($value);
	}

	function setacrescimo($value){
		$this->fields["acrescimo"] = value_numeric($value);
	}
}
?>