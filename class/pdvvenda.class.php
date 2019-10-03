<?php

class PdvVenda{

	public $pdvitem = array();
	private $status;
	private $cupom;
	private $caixa;
	private $numeroecf;
	private $data;
	private $hora;
	private $codcliente;
	private $codfunc;
	private $cpfcnpj;
	private $codorcamento;
	private $seqecf;
	private $chavecfe;
	private $codecf;
	private $operador;
	private $referencia;
	private $numfabricacao;
	private $arquivo;
	private $codsupervisor;
	private $nomecliente;

	function __construct(){
		$this->status = "A";
		$this->codfunc = NULL;
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

	function getnumeroecf(){
		return $this->numeroecf;
	}

	function getdata(){
		return $this->data;
	}

	function gethora(){
		return $this->hora;
	}

	function getcodcliente(){
		return $this->codcliente;
	}

	function getcodfunc(){
		return (int) $this->codfunc;
	}

	function getcpfcnpj(){
		return $this->cpfcnpj;
	}

	function getcodorcamento(){
		return $this->codorcamento;
	}

	function getseqecf(){
		return $this->seqecf;
	}

	function getchavecfe(){
		return $this->chavecfe;
	}

	function getcodecf(){
		return $this->codecf;
	}

	function getoperador(){
		return $this->operador;
	}

	function getreferencia(){
		return $this->referencia;
	}

	function getnumfabricacao(){
		return $this->numfabricacao;
	}

	function  getarquivo(){
		return $this->arquivo;
	}

	function  getcodsupervisor(){
		return $this->codsupervisor;
	}

	function  getnomecliente(){
		return $this->nomecliente;
	}

	function setstatus($value){
		if(in_array($value, array("A", "C", "I"))){
			$this->status = $value;
		}
	}

	function setcupom($value){
		$this->cupom = $value;
	}

	function setcaixa($value){
		$this->caixa = value_numeric($value);
	}

	function setnumeroecf($value){
		$this->numeroecf = value_numeric($value);
	}

	function setdata($value){
		$this->data = value_date($value);
	}

	function sethora($value){
		$this->hora = value_time($value);
	}

	function setcodcliente($value){
		$value = ltrim($value, "0");
		if(strlen($value) == 0){
			$value = NULL;
		}
		$this->codcliente = value_numeric($value);
	}

	function setcodfunc($value){
		if($value == 0){
			$value = NULL;
		}
		$this->codfunc = value_numeric($value);
	}

	function setcpfcnpj($value, $validar = true){
		$value = trim(removeformat($value));
		if(strlen(ltrim($value, "0")) == 0){
			$value = NULL;
		}else{
			if(strlen($value) == 14){
				$value = substr($value, 0, 2).".".substr($value, 2, 3).".".substr($value, 5, 3)."/".substr($value, 8, 4)."-".substr($value, 12, 2);
			}else{
				$value = substr($value, 0, 3).".".substr($value, 3, 3).".".substr($value, 6, 3)."-".substr($value, 9, 2);
			}
		}

		if($validar){
			if(!(valid_cpf($value) || valid_cnpj($value))){
				$value =  "";
			}	
		}		

		$this->cpfcnpj = $value;
	}

	function setcodorcamento($value){
		$this->codorcamento = value_numeric($value);
	}

	function setseqecf($value){
		$this->seqecf = $value;
	}

	function setchavecfe($value){
		$value = trim($value);
		if(strlen($value) !== 44){
			$value = null;
		}
		$this->chavecfe = $value;
	}

	function setcodecf($value){
		$this->codecf = value_numeric($value);
	}

	function setoperador($value){
		$this->operador = value_string($value);
	}

	function setreferencia($value){
		$this->referencia = value_string($value);
	}

	function setnumfabricacao($value){
		$this->numfabricacao = value_string($value);
	}

	function setarquivo($value){
		$this->arquivo = value_string($value);
	}
	
	function setcodsupervisor($value){
		$this->codsupervisor = value_numeric($value);
	}

	function setnomecliente($value){
		$this->nomecliente = value_string($value);
	}
}
