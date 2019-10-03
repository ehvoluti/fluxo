<?php
class PdvFinalizador{
	private $status;
	private $cupom;
	private $caixa;
	private $data;
	private $hora;
	private $codfinaliz;
	private $valortotal;
	private $codcliente;
	private $cpfcliente;
	private $datavencto;
	private $codbanco;
	private $numagenciacheq;
	private $numcheque;
	private $contacheque;
	private $codfunc;
	private $bin;
	private $tipotransacao;
	private $recebepdv = false;

	function __construct(){
		$this->status = "A";
	}

	function getstatus(){
		return $this->status;
	}

	function getcupom(){
		return $this->cupom;
	}

	function getcaixa(){
		return $this->caixa;
	}

	function getdata(){
		return $this->data;
	}

	function gethora(){
		return $this->hora;
	}

	function getcodfinaliz(){
		return $this->codfinaliz;
	}

	function getvalortotal(){
		return $this->valortotal;
	}

	function getcodcliente(){
		return $this->codcliente;
	}

	function getcpfcliente(){
		return $this->cpfcliente;
	}

	function getdatavencto(){
		return $this->datavencto;
	}

	function getcodbanco(){
		return $this->codbanco;
	}

	function getnumagenciacheq(){
		return $this->numagenciacheq;
	}

	function getnumcheque(){
		return $this->numcheque;
	}

	function getcodfunc(){
		return $this->codfunc;
	}

	function getbin(){
		return $this->bin;
	}

	function gettipotransacao(){
		return $this->tipotransacao;
	}

	function getcontacheque(){
		return $this->contacheque;
	}

	function getrecebepdv(){
		return $this->recebepdv;
	}

	function setstatus($value){
		if(in_array($value,array("A","C"))){
			$this->status = $value;
		}
	}

	function setcupom($value){
		$this->cupom = $value;
	}

	function setcaixa($value){
		$this->caixa = value_numeric($value);
	}

	function setdata($value){
		$this->data = value_date($value);
	}

	function sethora($value){
		$this->hora = value_time($value);
	}

	function setcodfinaliz($value){
		$this->codfinaliz = $value;
	}

	function setvalortotal($value){
		$this->valortotal = value_numeric($value);
	}

	function setcodcliente($value){
		$value = ltrim($value,"0");
		if($value == 0){
			$value = NULL;
		}
		if(strlen($value) > 8){
			$this->setcpfcliente($value);
		}else{
			$this->codcliente = value_numeric($value);
		}
	}

	function setcpfcliente($value){
		$value = trim(removeformat($value));
		if(strlen(ltrim($value,"0")) == 0){
			$value = NULL;
		}else{
			if(strlen($value) == 14){
				$value = substr($value,0,2).".".substr($value,2,3).".".substr($value,5,3)."/".substr($value,8,4)."-".substr($value,12,2);
			}else{
				$value = substr($value,0,3).".".substr($value,3,3).".".substr($value,6,3)."-".substr($value,9,2);
			}
		}

		if(!(valid_cpf($value) || valid_cnpj($value))){
			$value =  "";
		}

		$this->cpfcliente = $value;
	}

	function setdatavencto($value){
		$this->datavencto = value_date($value);
	}

	function setcodbanco($value){
		$this->codbanco = value_string($value,10);
	}

	function setnumagenciacheq($value){
		$this->numagenciacheq = value_string($value,10);
	}

	function setnumcheque($value){
		$this->numcheque = value_string(trim($value),20);
	}

	function setcodfunc($value){
		$this->codfunc = value_numeric($value);
	}

	function setbin($value){
		$this->bin = value_numeric($value);
	}

	function settipotransacao($value){
		$this->tipotransacao = value_string($value,1);
	}

	function setcontacheque($value){
		$this->contacheque = value_string($value,20);
	}

	function setrecebepdv($value){
		$this->recebepdv = $value;
	}
}
