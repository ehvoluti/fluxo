<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ConsVendaDia extends Cadastro{
	function __construct($codestabelec = NULL,$dtmovto = NULL,$codproduto = NULL){
		parent::__construct();
		$this->table = "consvendadia";
		$this->primarykey = array("codestabelec","dtmovto","codproduto");
		$this->setcodestabelec($codestabelec);
		$this->setdtmovto($dtmovto);
		$this->setcodproduto($codproduto);
		if($this->getcodestabelec() != NULL && $this->getdtmovto() != NULL && $this->getcodproduto() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
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

	function gettipovenda(){
		return $this->fields["tipovenda"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
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

	function settipovenda($value){
		$this->fields["tipovenda"] = value_string($value,1);
	}
}
?>