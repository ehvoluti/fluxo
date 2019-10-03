<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LimiteCredito extends Cadastro{
	function __construct($codcliente = NULL){
		parent::__construct();
		$this->newrelation("limitecredito","codcliente","cliente","codcliente");
		$this->forcerelation(TRUE);
		$this->table = "limitecredito";
		$this->primarykey = "codcliente";
		$this->setcodcliente($codcliente);
		if($this->getcodcliente() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getdtstatus(){
		return $this->fields["dtstatus"];
	}

	function getdtvalidade(){
		return $this->fields["dtvalidade"];
	}

	function getemailresp(){
		return $this->fields["emailresp"];
	}

	function getvalorlimite($format = FALSE){
		return ($format ? number_format($this->fields["valorlimite"],2,",","") : $this->fields["valorlimite"]);
	}

	function getvalorfaturado($format = FALSE){
		return ($format ? number_format($this->fields["valorfaturado"],2,",","") : $this->fields["valorfaturado"]);
	}

	function getvalorpago($format = FALSE){
		return ($format ? number_format($this->fields["valorpago"],2,",","") : $this->fields["valorpago"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setdtstatus($value){
		$this->fields["dtstatus"] = value_date($value);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function setemailresp($value){
		$this->fields["emailresp"] = value_string($value,80);
	}

	function setvalorlimite($value){
		$this->fields["valorlimite"] = value_numeric($value);
	}

	function setvalorfaturado($value){
		$this->fields["valorfaturado"] = value_numeric($value);
	}

	function setvalorpago($value){
		$this->fields["valorpago"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,500);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>