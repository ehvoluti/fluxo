<?php

class PdvItem{

	private $status;
	private $sequencial;
	private $codproduto;
	private $descricao;
	private $quantidade;
	private $preco;
	private $desconto;
	private $acrescimo;
	private $total;
	private $tptribicms;
	private $aliqicms;

	function __construct(){
		$this->status = "A";
		$this->desconto = 0;
		$this->acrescimo = 0;
	}

	function calcular_total(){
		//$this->total = ($this->preco * $this->quantidade) - $this->desconto + $this->acrescimo;
		//$this->total = round($this->total, 2);
	}

	function getstatus(){
		return $this->status;
	}

	function getsequencial(){
		return $this->sequencial;
	}

	function getcodproduto(){
		return $this->codproduto;
	}

	function getdescricao(){
		return $this->descricao;
	}

	function getquantidade(){
		return $this->quantidade;
	}

	function getpreco(){
		return $this->preco;
	}

	function getdesconto(){
		return $this->desconto;
	}

	function getacrescimo(){
		return $this->acrescimo;
	}

	function gettotal(){
		return $this->total;
	}

	function gettptribicms(){
		return $this->tptribicms;
	}

	function getaliqicms(){
		return $this->aliqicms;
	}
	
	function  getcodsupervisor(){
		return $this->codsupervisor;
	}

	function setstatus($value){
		if(in_array($value, array("A", "C", "T"))){
			$this->status = $value;
		}
	}

	function setsequencial($value){
		$this->sequencial = value_numeric($value);
	}

	function setcodproduto($value, $fixed = FALSE){
		$value = trim($value);
		if(!$fixed){
			if(strlen(ltrim($value, "0")) < 8){
				$value = str_pad(ltrim($value, "0"), 8, "0", STR_PAD_LEFT);
			}
		}
		$this->codproduto = $value;
	}

	function setdescricao($value){
		$this->descricao = value_string($value);
	}

	function setquantidade($value){
		$this->quantidade = value_numeric($value);
		$this->calcular_total();
	}

	function setpreco($value){
		$this->preco = value_numeric($value);
		$this->calcular_total();
	}

	function setdesconto($value){
		$this->desconto = value_numeric($value);
		$this->calcular_total();
	}

	function setacrescimo($value){
		$this->acrescimo = value_numeric($value);
		$this->calcular_total();
	}

	function settotal($value){
		$this->total = value_numeric($value);
	}

	function settptribicms($value){
		$this->tptribicms = value_string($value, 1);
	}

	function setaliqicms($value){
		$this->aliqicms = value_numeric($value);
	}
	
	function setcodsupervisor($value){
		$this->codsupervisor = value_numeric($value);
	}

}