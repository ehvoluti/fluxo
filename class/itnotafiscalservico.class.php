<?php
require_file("class/cadastro.class.php");

class ItNotaFiscalServico extends Cadastro{
	function __construct($idnotafiscalservico = NULL){
		parent::__construct();
		$this->table = "itnotafiscalservico";
		$this->primarykey = array("idnotafiscalservico","codproduto");
		$this->setidnotafiscalservico($idnotafiscalservico);
		if(!is_null($this->getidnotafiscalservico())){
			$this->searchbyobject();
		}
	}

	function getidnotafiscalservico(){
		return $this->fields["idnotafiscalservico"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getseqitem(){
		return $this->fields["seqitem"];
	}

	function getvalortotal($format = FALSE){
		return ($format ? number_format($this->fields["valortotal"],2,",","") : $this->fields["valortotal"]);
	}

	function getvalordesconto($format = FALSE){
		return ($format ? number_format($this->fields["valordesconto"],2,",","") : $this->fields["valordesconto"]);
	}

	function getnatbasecalculocredito(){
		return $this->fields["natbasecalculocredito"];
	}

	function getindicadororigemcredito(){
		return $this->fields["indicadororigemcredito"];
	}

	function getcstpiscofins(){
		return $this->fields["cstpiscofins"];
	}

	function getbasecalculopis($format = FALSE){
		return ($format ? number_format($this->fields["basecalculopis"],2,",","") : $this->fields["basecalculopis"]);
	}

	function getaliquotapis($format = FALSE){
		return ($format ? number_format($this->fields["aliquotapis"],2,",","") : $this->fields["aliquotapis"]);
	}

	function getvalorpis($format = FALSE){
		return ($format ? number_format($this->fields["valorpis"],2,",","") : $this->fields["valorpis"]);
	}

	function getbasecalculocofins($format = FALSE){
		return ($format ? number_format($this->fields["basecalculocofins"],2,",","") : $this->fields["basecalculocofins"]);
	}

	function getaliquotacofins($format = FALSE){
		return ($format ? number_format($this->fields["aliquotacofins"],2,",","") : $this->fields["aliquotacofins"]);
	}

	function getvalorcofins($format = FALSE){
		return ($format ? number_format($this->fields["valorcofins"],2,",","") : $this->fields["valorcofins"]);
	}

	function setidnotafiscalservico($value){
		$this->fields["idnotafiscalservico"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_string($value, 20);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 200);
	}

	function setseqitem($value){
		$this->fields["seqitem"] = value_numeric($value);
	}

	function setvalortotal($value){
		$this->fields["valortotal"] = value_numeric($value);
	}

	function setvalordesconto($value){
		$this->fields["valordesconto"] = value_numeric($value);
	}

	function setnatbasecalculocredito($value){
		$this->fields["natbasecalculocredito"] = value_string($value,2);
	}

	function setindicadororigemcredito($value){
		$this->fields["indicadororigemcredito"] = value_string($value,1);
	}

	function setcstpiscofins($value){
		$this->fields["cstpiscofins"] = value_string($value,2);
	}

	function setbasecalculopis($value){
		$this->fields["basecalculopis"] = value_numeric($value);
	}

	function setaliquotapis($value){
		$this->fields["aliquotapis"] = value_numeric($value);
	}

	function setvalorpis($value){
		$this->fields["valorpis"] = value_numeric($value);
	}

	function setbasecalculocofins($value){
		$this->fields["basecalculocofins"] = value_numeric($value);
	}

	function setaliquotacofins($value){
		$this->fields["aliquotacofins"] = value_numeric($value);
	}

	function setvalorcofins($value){
		$this->fields["valorcofins"] = value_numeric($value);
	}
}
?>