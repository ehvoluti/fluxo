<?php
class PdvRecebePdv{
	private $codestabelec;
	private $codrecebepdv;
	private $dtmovto;
	private $totalliquido;
	private $codfinaliz;

	function __construct(){

	}

	function getcodestabelec(){
		return $this->codestabelec;
	}

	function getcodrecebepdv(){
		return $this->codrecebepdv;
	}

	function getdtmovto(){
		return $this->dtmovto;
	}

	function gettotalliquido(){
		return $this->totalliquido;
	}

	function getcodfinaliz(){
		return $this->codfinaliz;
	}

	function setcodestabelec($value){
		$this->codestabelec = value_numeric($value);
	}

	function setcodrecebepdv($value){
		$this->codrecebepdv = value_numeric($value);
	}

	function setdtmovto($value){
		$this->dtmovto = value_date($value);
	}

	function settotalliquido($value){
		$this->totalliquido = value_numeric($value);
	}

	function setcodfinaliz($value){
		$this->codfinaliz = $value;
	}
}
