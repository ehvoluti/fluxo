<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Emitente extends Cadastro{
	function __construct($codemitente = NULL){
		parent::__construct();
		$this->table = "emitente";
		$this->primarykey = "codemitente";
		$this->newrelation("emitente","codcidade","cidade","codcidade");
		$this->newrelation("cidade","uf","estado","uf");
		$this->setcodemitente($codemitente);
		if($this->getcodemitente() != NULL){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); "; // Filtra a cidade
		return $html;
	}

	function getcodemitente(){
		return $this->fields["codemitente"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
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

	function getcontato(){
		return $this->fields["contato"];
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

	function gettppessoa(){
		return $this->fields["tppessoa"];
	}

	function getcpfcnpj(){
		return $this->fields["cpfcnpj"];
	}

	function getrgie(){
		return $this->fields["rgie"];
	}

	function getsite(){
		return $this->fields["site"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function getdtultfecto($format = FALSE){
		return ($format ? convert_date($this->fields["dtultfecto"],"Y-m-d","d/m/Y") : $this->fields["dtultfecto"]);
	}

	function gettipoemp(){
		return $this->fields["tipoemp"];
	}

	function getnumero(){
		return $this->fields["numero"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function getlocallogotipo(){
		return $this->fields["locallogotipo"];
	}

	function setcodemitente($value){
		$this->fields["codemitente"] = value_numeric($value);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value,40);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value,40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value,40);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value,9);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setcontato($value){
		$this->fields["contato"] = value_string($value,40);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value,20);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value,20);
	}

	function setfax($value){
		$this->fields["fax"] = value_string($value,20);
	}

	function settppessoa($value){
		$this->fields["tppessoa"] = value_string($value,1);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value,18);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value,20);
	}

	function setsite($value){
		$this->fields["site"] = value_string($value,60);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,80);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,200);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setdtultfecto($value){
		$this->fields["dtultfecto"] = value_date($value);
	}

	function settipoemp($value){
		$this->fields["tipoemp"] = value_string($value,1);
	}

	function setnumero($value){
		$this->fields["numero"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value,40);
	}

	function setlocallogotipo($value){
		$this->fields["locallogotipo"] = value_string($value, 100);
	}
}