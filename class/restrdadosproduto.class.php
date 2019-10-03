<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class RestrDadosProduto extends Cadastro{
	function __construct($codgrupo = NULL){
		parent::__construct();
		$this->table = "restrdadosproduto";
		$this->primarykey = array("codgrupo");
		$this->setcodgrupo($codgrupo);
		if(!is_null($this->getcodgrupo())){
			$this->searchbyobject();
		}
	}

	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getinformacaoestoque(){
		return $this->fields["informacaoestoque"];
	}

	function getvariacaoestoque(){
		return $this->fields["variacaoestoque"];
	}

	function getvendas(){
		return $this->fields["vendas"];
	}

	function getcompras(){
		return $this->fields["compras"];
	}

	function gettransferencias(){
		return $this->fields["transferencias"];
	}

	function getmovimentacoes(){
		return $this->fields["movimentacoes"];
	}

	function gethistoricopreco(){
		return $this->fields["historicopreco"];
	}

	function getestoqueestabelecimento(){
		return $this->fields["estoqueestabelecimento"];
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setinformacaoestoque($value){
		$this->fields["informacaoestoque"] = value_string($value,1);
	}

	function setvariacaoestoque($value){
		$this->fields["variacaoestoque"] = value_string($value,1);
	}

	function setvendas($value){
		$this->fields["vendas"] = value_string($value,1);
	}

	function setcompras($value){
		$this->fields["compras"] = value_string($value,1);
	}

	function settransferencias($value){
		$this->fields["transferencias"] = value_string($value,1);
	}

	function setmovimentacoes($value){
		$this->fields["movimentacoes"] = value_string($value,1);
	}

	function sethistoricopreco($value){
		$this->fields["historicopreco"] = value_string($value,1);
	}

	function setestoqueestabelecimento($value){
		$this->fields["estoqueestabelecimento"] = value_string($value,1);
	}
}
?>