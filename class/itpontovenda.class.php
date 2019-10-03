<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItPontoVenda extends Cadastro{
	function __construct($login = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "itpontovenda";
		$this->primarykey = array("login", "codproduto");
		$this->setlogin($login);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getlogin()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getunidade(){
		return $this->fields["unidade"];
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->fields["percdescto"],2,",","") : $this->fields["percdescto"]);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->fields["valdescto"],2,",","") : $this->fields["valdescto"]);
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function gettotalitem($format = FALSE){
		return ($format ? number_format($this->fields["totalitem"],2,",","") : $this->fields["totalitem"]);
	}

	function getseqitem(){
		return $this->fields["seqitem"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setunidade($value){
		$this->fields["unidade"] = value_string($value,2);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function setpercdescto($value){
		$this->fields["percdescto"] = value_numeric($value);
	}

	function setvaldescto($value){
		$this->fields["valdescto"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function settotalitem($value){
		$this->fields["totalitem"] = value_numeric($value);
	}

	function setseqitem($value){
		$this->fields["seqitem"] = value_numeric($value);
	}
}
?>