<?php
class PdvVasilhame{
	private $codestabelec;
	private $numrecepcao;
	private $codcliente;
	private $caixa;
	private $dtvenda;
	private $dtrecepcao;
	private $codfunc;
	private $quantidade;
	private $codvasilhame;
	private $entsai;

	function __construct(){

	}

	function getcodestabelec(){
		return $this->codestabelec;
	}

	function getnumrecepcao(){
		return $this->numrecepcao;
	}

	function getcodcliente(){
		return $this->codcliente;
	}

	function getcaixa(){
		return $this->caixa;
	}

	function getdtvenda(){
		return $this->dtvenda;
	}

	function getdtrecepcao(){
		return $this->dtrecepcao;
	}

	function getcodfunc(){
		return $this->codfunc;
	}

	function getquantidade(){
		return $this->quantidade;
	}

	function getcodvasilhame(){
		return $this->codvasilhame;
	}

	function getentsai(){
		return $this->entsai;
	}

	function setcodestabelec($value){
		$this->codestabelec = $value;
	}

	function setnumrecepcao($value){
		$this->numrecepcao = $value;
	}

	function setcodcliente($value){
		$this->codcliente = $value;
	}

	function setcaixa($value){
		$this->caixa = value_numeric($value);
	}

	function setdtvenda($value){
		$this->dtvenda = value_date($value);
	}

	function setdtrecepcao($value){
		$this->dtrecepcao = value_date($value);
	}

	function setcodfunc($value){
		$this->codfunc = $value;
	}

	function setquantidade($value){
		$this->quantidade = value_numeric($value);
	}

	function setcodvasilhame($value){
		$this->codvasilhame = $value;
	}

	function setentsai($value){
		$this->entsai = $value;
	}
}
