<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Funcionario extends Cadastro{

	function __construct($codfunc = NULL){
		parent::__construct();
		$this->table = "funcionario";
		$this->primarykey = "codfunc";
		$this->setcodfunc($codfunc);
		if($this->getcodfunc() != NULL){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); "; // Filtra a cidade
		$html .= "settppessoamasks(); "; // Aplica a mascara dependendo do tipo de pessoa
		$html .= "$('#cpfcnpj').val('".$this->getcpfcnpj()."'); "; // Joga o valor do CPF/CNPJ
		$html .= "$('#rgie').val('".$this->getrgie()."'); "; // Joga o valor do RG/IE
		return $html;
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getendereco(){
		return $this->fields["endereco"];
	}

	function getbairro(){
		return $this->fields["bairro"];
	}

	function getcep(){
		return $this->fields["cep"];
	}

	function getcodcidade(){
		return $this->fields["codcidade"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getfone1(){
		return $this->fields["fone1"];
	}

	function getfone2(){
		return $this->fields["fone2"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function getcontato(){
		return $this->fields["contato"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
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

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getcomissao($format = FALSE){
		return ($format ? number_format($this->fields["comissao"], 2, ",", "") : $this->fields["comissao"]);
	}

	function getcoddepto(){
		return $this->fields["coddepto"];
	}

	function getsituacao(){
		return $this->fields["situacao"];
	}

	function getlimite($format = FALSE){
		return ($format ? number_format($this->fields["limite"], 2, ",", "") : $this->fields["limite"]);
	}

	function getnummatricula(){
		return $this->fields["nummatricula"];
	}

	function getcodfuncresp(){
		return $this->fields["codfuncresp"];
	}

	function getlimiteadicional($format = FALSE){
		return ($format ? number_format($this->fields["limiteadicional"], 2, ",", "") : $this->fields["limiteadicional"]);
	}

	function getnumero(){
		return $this->fields["numero"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function getcodclassif(){
		return $this->fields["codclassif"];
	}

	function getperm_venda(){
		return $this->fields["perm_venda"];
	}

	function getperm_compra(){
		return $this->fields["perm_compra"];
	}

	function getperm_ocorrencia(){
		return $this->fields["perm_ocorrencia"];
	}

	function getperm_outros(){
		return $this->fields["perm_outros"];
	}

	function getcodmodeloemailorcamento(){
		return $this->fields["codmodeloemailorcamento"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodigooperador(){
		return $this->fields["codigooperador"];
	}

	function getmodeloorcamento(){
		return $this->fields["modeloorcamento"];
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value, 40);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value, 40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value, 30);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value, 9);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value, 2);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value, 20);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value, 20);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value, 80);
	}

	function setcontato($value){
		$this->fields["contato"] = value_string($value, 40);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 200);
	}

	function settppessoa($value){
		$this->fields["tppessoa"] = value_string($value, 1);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value, 20);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value, 20);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setcomissao($value){
		$this->fields["comissao"] = value_numeric($value);
	}

	function setcoddepto($value){
		$this->fields["coddepto"] = value_numeric($value);
	}

	function setsituacao($value){
		$this->fields["situacao"] = value_string($value, 1);
	}

	function setlimite($value){
		$this->fields["limite"] = value_numeric($value);
	}

	function setnummatricula($value){
		$this->fields["nummatricula"] = value_string($value, 20);
	}

	function setcodfuncresp($value){
		$this->fields["codfuncresp"] = value_numeric($value);
	}

	function setlimiteadicional($value){
		$this->fields["limiteadicional"] = value_numeric($value);
	}

	function setnumero($value){
		$this->fields["numero"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 40);
	}

	function setcodclassif($value){
		$this->fields["codclassif"] = value_numeric($value);
	}

	function setperm_venda($value){
		$this->fields["perm_venda"] = value_string($value, 1);
	}

	function setperm_compra($value){
		$this->fields["perm_compra"] = value_string($value, 1);
	}

	function setperm_ocorrencia($value){
		$this->fields["perm_ocorrencia"] = value_string($value, 1);
	}

	function setperm_outros($value){
		$this->fields["perm_outros"] = value_string($value, 1);
	}

	function setcodmodeloemailorcamento($value){
		$this->fields["codmodeloemailorcamento"] = value_numeric($value);
	}

	function setmodeloorcamento($value){
		$this->fields["modeloorcamento"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodigooperador($value){
		$this->fields["codigooperador"] = value_string($value, 5);
	}
}