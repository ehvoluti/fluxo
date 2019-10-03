<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Nutricional extends Cadastro{
	function __construct($codnutricional = NULL){
		parent::__construct();
		$this->table = "nutricional";
		$this->primarykey = array("codnutricional");
		$this->setcodnutricional($codnutricional);
		if(!is_null($this->getcodnutricional())){
			$this->searchbyobject();
		}
	}

	function getcodnutricional(){
		return $this->fields["codnutricional"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getqtdeporcao($format = FALSE){
		return ($format ? number_format($this->fields["qtdeporcao"],4,",","") : $this->fields["qtdeporcao"]);
	}

	function getdescricaoporcao(){
		return $this->fields["descricaoporcao"];
	}

	function getqtdecal($format = FALSE){
		return ($format ? number_format($this->fields["qtdecal"],4,",","") : $this->fields["qtdecal"]);
	}

	function getperccal($format = FALSE){
		return ($format ? number_format($this->fields["perccal"],2,",","") : $this->fields["perccal"]);
	}

	function getqtdecarbo($format = FALSE){
		return ($format ? number_format($this->fields["qtdecarbo"],4,",","") : $this->fields["qtdecarbo"]);
	}

	function getperccarbo($format = FALSE){
		return ($format ? number_format($this->fields["perccarbo"],2,",","") : $this->fields["perccarbo"]);
	}

	function getqtdeprot($format = FALSE){
		return ($format ? number_format($this->fields["qtdeprot"],4,",","") : $this->fields["qtdeprot"]);
	}

	function getpercprot($format = FALSE){
		return ($format ? number_format($this->fields["percprot"],2,",","") : $this->fields["percprot"]);
	}

	function getqtdegord($format = FALSE){
		return ($format ? number_format($this->fields["qtdegord"],4,",","") : $this->fields["qtdegord"]);
	}

	function getpercgord($format = FALSE){
		return ($format ? number_format($this->fields["percgord"],2,",","") : $this->fields["percgord"]);
	}

	function getqtdegordsat($format = FALSE){
		return ($format ? number_format($this->fields["qtdegordsat"],4,",","") : $this->fields["qtdegordsat"]);
	}

	function getpercgordsat($format = FALSE){
		return ($format ? number_format($this->fields["percgordsat"],2,",","") : $this->fields["percgordsat"]);
	}

	function getqtdecolest($format = FALSE){
		return ($format ? number_format($this->fields["qtdecolest"],4,",","") : $this->fields["qtdecolest"]);
	}

	function getperccolest($format = FALSE){
		return ($format ? number_format($this->fields["perccolest"],2,",","") : $this->fields["perccolest"]);
	}

	function getqtdefibra($format = FALSE){
		return ($format ? number_format($this->fields["qtdefibra"],4,",","") : $this->fields["qtdefibra"]);
	}

	function getpercfibra($format = FALSE){
		return ($format ? number_format($this->fields["percfibra"],2,",","") : $this->fields["percfibra"]);
	}

	function getqtdeferro($format = FALSE){
		return ($format ? number_format($this->fields["qtdeferro"],4,",","") : $this->fields["qtdeferro"]);
	}

	function getpercferro($format = FALSE){
		return ($format ? number_format($this->fields["percferro"],2,",","") : $this->fields["percferro"]);
	}

	function getqtdecalcio($format = FALSE){
		return ($format ? number_format($this->fields["qtdecalcio"],4,",","") : $this->fields["qtdecalcio"]);
	}

	function getperccalcio($format = FALSE){
		return ($format ? number_format($this->fields["perccalcio"],2,",","") : $this->fields["perccalcio"]);
	}

	function getqtdesodio($format = FALSE){
		return ($format ? number_format($this->fields["qtdesodio"],4,",","") : $this->fields["qtdesodio"]);
	}

	function getpercsodio($format = FALSE){
		return ($format ? number_format($this->fields["percsodio"],2,",","") : $this->fields["percsodio"]);
	}

	function getqtdegordtrans($format = FALSE){
		return ($format ? number_format($this->fields["qtdegordtrans"],4,",","") : $this->fields["qtdegordtrans"]);
	}

	function getpercgordtrans($format = FALSE){
		return ($format ? number_format($this->fields["percgordtrans"],2,",","") : $this->fields["percgordtrans"]);
	}

	function getm1percgordtrans(){
		return $this->fields["m1percgordtrans"];
	}

	function getm1qtdefibra(){
		return $this->fields["m1qtdefibra"];
	}

	function getm1percfibra(){
		return $this->fields["m1percfibra"];
	}

	function getm1qtdecalcio(){
		return $this->fields["m1qtdecalcio"];
	}

	function getm1qtdecarbo(){
		return $this->fields["m1qtdecarbo"];
	}

	function getm1qtdeprot(){
		return $this->fields["m1qtdeprot"];
	}

	function getunidporcao(){
		return $this->fields["unidporcao"];
	}

	function getintmedcas(){
		return $this->fields["intmedcas"];
	}

	function getdecmedcas(){
		return $this->fields["decmedcas"];
	}

	function getmedcaseira(){
		return $this->fields["medcaseira"];
	}

	function setcodnutricional($value){
		$this->fields["codnutricional"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setqtdeporcao($value){
		$this->fields["qtdeporcao"] = value_numeric($value);
	}

	function setdescricaoporcao($value){
		$this->fields["descricaoporcao"] = value_string($value,40);
	}

	function setqtdecal($value){
		$this->fields["qtdecal"] = value_numeric($value);
	}

	function setperccal($value){
		$this->fields["perccal"] = value_numeric($value);
	}

	function setqtdecarbo($value){
		$this->fields["qtdecarbo"] = value_numeric($value);
	}

	function setperccarbo($value){
		$this->fields["perccarbo"] = value_numeric($value);
	}

	function setqtdeprot($value){
		$this->fields["qtdeprot"] = value_numeric($value);
	}

	function setpercprot($value){
		$this->fields["percprot"] = value_numeric($value);
	}

	function setqtdegord($value){
		$this->fields["qtdegord"] = value_numeric($value);
	}

	function setpercgord($value){
		$this->fields["percgord"] = value_numeric($value);
	}

	function setqtdegordsat($value){
		$this->fields["qtdegordsat"] = value_numeric($value);
	}

	function setpercgordsat($value){
		$this->fields["percgordsat"] = value_numeric($value);
	}

	function setqtdecolest($value){
		$this->fields["qtdecolest"] = value_numeric($value);
	}

	function setperccolest($value){
		$this->fields["perccolest"] = value_numeric($value);
	}

	function setqtdefibra($value){
		$this->fields["qtdefibra"] = value_numeric($value);
	}

	function setpercfibra($value){
		$this->fields["percfibra"] = value_numeric($value);
	}

	function setqtdeferro($value){
		$this->fields["qtdeferro"] = value_numeric($value);
	}

	function setpercferro($value){
		$this->fields["percferro"] = value_numeric($value);
	}

	function setqtdecalcio($value){
		$this->fields["qtdecalcio"] = value_numeric($value);
	}

	function setperccalcio($value){
		$this->fields["perccalcio"] = value_numeric($value);
	}

	function setqtdesodio($value){
		$this->fields["qtdesodio"] = value_numeric($value);
	}

	function setpercsodio($value){
		$this->fields["percsodio"] = value_numeric($value);
	}

	function setqtdegordtrans($value){
		$this->fields["qtdegordtrans"] = value_numeric($value);
	}

	function setpercgordtrans($value){
		$this->fields["percgordtrans"] = value_numeric($value);
	}

	function setm1percgordtrans($value){
		$this->fields["m1percgordtrans"] = value_string($value,1);
	}

	function setm1qtdefibra($value){
		$this->fields["m1qtdefibra"] = value_string($value,1);
	}

	function setm1percfibra($value){
		$this->fields["m1percfibra"] = value_string($value,1);
	}

	function setm1qtdecalcio($value){
		$this->fields["m1qtdecalcio"] = value_string($value,1);
	}

	function setm1qtdecarbo($value){
		$this->fields["m1qtdecarbo"] = value_string($value,1);
	}

	function setm1qtdeprot($value){
		$this->fields["m1qtdeprot"] = value_string($value,1);
	}

	function setunidporcao($value){
		$this->fields["unidporcao"] = value_string($value,1);
	}

	function setintmedcas($value){
		$this->fields["intmedcas"] = value_numeric($value);
	}

	function setdecmedcas($value){
		$this->fields["decmedcas"] = value_string($value,1);
	}

	function setmedcaseira($value){
		$this->fields["medcaseira"] = value_string($value,2);
	}
}
?>