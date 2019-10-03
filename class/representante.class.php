<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Representante extends Cadastro{
	function __construct($codrepresentante = NULL){
		parent::__construct();
		$this->table = "representante";
		$this->primarykey = array("codrepresentante");
		$this->setcodrepresentante($codrepresentante);
		if(!is_null($this->getcodrepresentante())){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); "; // Filtra a cidade
		$html .= "settipopessoa(\"cpfcnpj\",$(\"#tppessoa\").val(),$(\"#cpfcnpj\")); "; // Aplica a mascara dependendo do tipo de pessoa
		$html .= "settipopessoa(\"rgie\",$(\"#tppessoa\").val(),$(\"#rgie\")); "; // Aplica a mascara dependendo do tipo de pessoa
		$html .= "$('#cpfcnpj').val('".$this->getcpfcnpj()."'); "; // Joga o valor do CPF/CNPJ
		$html .= "$('#rgie').val('".$this->getrgie()."'); "; // Joga o valor do RG/IE
		return $html;
	}

	function getcodrepresentante(){
		return $this->fields["codrepresentante"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
	}

	function gettppessoa(){
		return $this->fields["tppessoa"];
	}

	function getcpfcnpj(){
		return $this->fields["cpfcnpj"];
	}

	function getrgie(){
		return $this->fields["rgie"];
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

	function getcep(){
		return $this->fields["cep"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getcodcidade(){
		return $this->fields["codcidade"];
	}

	function getfone1(){
		return $this->fields["fone1"];
	}

	function getfone2(){
		return $this->fields["fone2"];
	}

	function getemail1(){
		return $this->fields["email1"];
	}

	function getemail2(){
		return $this->fields["email2"];
	}

	function getperccomissao($format = FALSE){
		return ($format ? number_format($this->fields["perccomissao"],2,",","") : $this->fields["perccomissao"]);
	}

	function getpercdesctoimposto($format = FALSE){
		return ($format ? number_format($this->fields["percdesctoimposto"],2,",","") : $this->fields["percdesctoimposto"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getsituacao(){
		return $this->fields["situacao"];
	}

	function setcodrepresentante($value){
		$this->fields["codrepresentante"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,60);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value,60);
	}

	function settppessoa($value){
		$this->fields["tppessoa"] = value_string($value,1);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value,20);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value,20);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value,60);
	}

	function setnumero($value){
		$this->fields["numero"] = value_string($value,20);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value,60);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value,60);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value,9);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value,20);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value,20);
	}

	function setemail1($value){
		$this->fields["email1"] = value_string($value,60);
	}

	function setemail2($value){
		$this->fields["email2"] = value_string($value,60);
	}

	function setperccomissao($value){
		$this->fields["perccomissao"] = value_numeric($value);
	}

	function setpercdesctoimposto($value){
		$this->fields["percdesctoimposto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setsituacao($value){
		$this->fields["situacao"] = value_string($value,1);
	}
}