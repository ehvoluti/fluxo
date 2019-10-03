<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class VParceiro extends Cadastro{

	function __construct($codparceiro = NULL){
		parent::__construct();
		$this->table = "v_parceiro";
		$this->primarykey = array("tipoparceiro", "codparceiro");
		$this->setcodparceiro($codparceiro);
		if(!is_null($this->getcodparceiro())){
			$this->searchbyobject();
		}
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getfone1(){
		return $this->fields["fone1"];
	}

	function getfone2(){
		return $this->fields["fone2"];
	}

	function getfax(){
		return $this->fields["fax"];
	}

	function getcpfcnpj(){
		return $this->fields["cpfcnpj"];
	}

	function getrgie(){
		return $this->fields["rgie"];
	}

	function getcep(){
		return $this->fields["cep"];
	}

	function getendereco(){
		return $this->fields["endereco"];
	}

	function getnumero(){
		return $this->fields["numero"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function getbairro(){
		return $this->fields["bairro"];
	}

	function getcodcidade(){
		return $this->fields["codcidade"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value, 1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value, 40);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value, 40);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value, 20);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value, 20);
	}

	function setfax($value){
		$this->fields["fax"] = value_string($value, 20);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value, 20);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value, 20);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value, 9);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value, 40);
	}

	function setnumero($value){
		$this->fields["numero"] = value_string($value, 20);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value, 30);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value, 2);
	}

}