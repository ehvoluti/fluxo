<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class PlanoContas extends Cadastro{
	function __construct($codconta = NULL){
		parent::__construct();
		$this->table = "planocontas";
		$this->primarykey = "codconta";
		$this->setcodconta($codconta);
		if($this->getcodconta() != NULL){
			$this->searchbyobject();
		}
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getsaldoinicial($format = FALSE){
		return ($format ? number_format($this->fields["saldoinicial"],2,",","") : $this->fields["saldoinicial"]);
	}

	function getcredito($format = FALSE){
		return ($format ? number_format($this->fields["credito"],2,",","") : $this->fields["credito"]);
	}

	function getdebito($format = FALSE){
		return ($format ? number_format($this->fields["debito"],2,",","") : $this->fields["debito"]);
	}

	function getdtultfecto($format = FALSE){
		return ($format ? convert_date($this->fields["dtultfecto"],"Y-m-d","d/m/Y") : $this->fields["dtultfecto"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function getcontacontabil(){
		return $this->fields["contacontabil"];
	}

	function getcodgrcta(){
		return $this->fields["codgrcta"];
	}

	function gettpconta(){
		return $this->fields["tpconta"];
	}

	function getnumconta(){
		return $this->fields["numconta"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getcodhistorico(){
		return $this->fields["codhistorico"];
	}

	function getcodhistoricodeb(){
		return $this->fields["codhistoricodeb"];
	}

	function getdatainclusao($format = true){
		return ($format ? convert_date($this->fields["datainclusao"],"Y-m-d","d/m/Y") : $this->fields["datainclusao"]);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setsaldoinicial($value){
		$this->fields["saldoinicial"] = value_numeric($value);
	}

	function setcredito($value){
		$this->fields["credito"] = value_numeric($value);
	}

	function setdebito($value){
		$this->fields["debito"] = value_numeric($value);
	}

	function setdtultfecto($value){
		$this->fields["dtultfecto"] = value_date($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setcontacontabil($value){
		$this->fields["contacontabil"] = value_string($value,20);
	}

	function setcodgrcta($value){
		$this->fields["codgrcta"] = value_numeric($value);
	}

	function settpconta($value){
		$this->fields["tpconta"] = value_string($value,1);
	}

	function setnumconta($value){
		$this->fields["numconta"] = value_string($value,20);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setcodhistorico($value){
		$this->fields["codhistorico"] = value_numeric($value);
	}

	function setcodhistoricodeb($value){
		$this->fields["codhistoricodeb"] = value_numeric($value);
	}

	function setdatainclusao($value){
		$this->fields["datainclusao"] = value_date($value);
	}
}