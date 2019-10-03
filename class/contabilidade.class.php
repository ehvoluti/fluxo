<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Contabilidade extends Cadastro{
	function __construct($codcontabilidade = NULL){
		parent::__construct();
		$this->table = "contabilidade";
		$this->primarykey = "codcontabilidade";
		$this->setcodcontabilidade($codcontabilidade);
		if($this->getcodcontabilidade() != NULL){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html .= parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); "; // Filtra a cidade
		return $html;
	}

	function getcodcontabilidade(){
		return $this->fields["codcontabilidade"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
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

	function gettelefone(){
		return $this->fields["telefone"];
	}

	function getfax(){
		return $this->fields["fax"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getcodcidade(){
		return $this->fields["codcidade"];
	}

	function getnomecontador(){
		return $this->fields["nomecontador"];
	}

	function getcpfcontador(){
		return $this->fields["cpfcontador"];
	}

	function getcrccontador(){
		return $this->fields["crccontador"];
	}

	function getapelidoarquivo(){
		return $this->fields["apelidoarquivo"];
	}

	function setcodcontabilidade($value){
		$this->fields["codcontabilidade"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,60);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value,60);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value,20);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value,20);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value,9);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value,80);
	}

	function setnumero($value){
		$this->fields["numero"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value,40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value,40);
	}

	function settelefone($value){
		$this->fields["telefone"] = value_string($value,20);
	}

	function setfax($value){
		$this->fields["fax"] = value_string($value,20);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,60);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setnomecontador($value){
		$this->fields["nomecontador"] = value_string($value,60);
	}

	function setcpfcontador($value){
		$this->fields["cpfcontador"] = value_string($value,20);
	}

	function setcrccontador($value){
		$this->fields["crccontador"] = value_string($value,20);
	}

	function setapelidoarquivo($value){
		$this->fields["apelidoarquivo"] = value_string($value,20);
	}
}