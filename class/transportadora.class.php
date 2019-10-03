<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Transportadora extends Cadastro{
	function __construct($codtransp = NULL){
		parent::__construct();
		$this->table = "transportadora";
		$this->primarykey = "codtransp";
		$this->newrelation("transportadora","codcidade","cidade","codcidade");
		$this->newrelation("cidade","uf","estado","uf");
		$this->setcodtransp($codtransp);
		if($this->getcodtransp() != NULL){
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

	function getcodtransp(){
		return $this->fields["codtransp"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
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

	function getsite(){
		return $this->fields["site"];
	}

	function getemail(){
		return $this->fields["email"];
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

	function getcodclassif(){
		return $this->fields["codclassif"];
	}

	function getcodatividade($format = FALSE){
		return  $this->fields["codatividade"];
	}

	function getcodbanco($format = FALSE){
		return ($format ? number_format($this->fields["codbanco"],2,",","") : $this->fields["codbanco"]);
	}

	function getagencia(){
		return $this->fields["agencia"];
	}

	function getcontacorrente(){
		return $this->fields["contacorrente"];
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

	function getfone(){
		return $this->fields["fone"];
	}

	function getfax(){
		return $this->fields["fax"];
	}

	function getcontato1(){
		return $this->fields["contato1"];
	}

	function getcargo1(){
		return $this->fields["cargo1"];
	}

	function getfone1(){
		return $this->fields["fone1"];
	}

	function getemail1(){
		return $this->fields["email1"];
	}

	function getcontato2(){
		return $this->fields["contato2"];
	}

	function getcargo2(){
		return $this->fields["cargo2"];
	}

	function getfone2(){
		return $this->fields["fone2"];
	}

	function getemail2(){
		return $this->fields["email2"];
	}

	function getcontato3(){
		return $this->fields["contato3"];
	}

	function getcargo3(){
		return $this->fields["cargo3"];
	}

	function getfone3(){
		return $this->fields["fone3"];
	}

	function getemail3(){
		return $this->fields["email3"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getnumero(){
		return $this->fields["numero"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function setcodtransp($value){
		$this->fields["codtransp"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value,40);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value,40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value,30);
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

	function setsite($value){
		$this->fields["site"] = value_string($value,60);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,80);
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

	function setcodclassif($value){
		$this->fields["codclassif"] = value_numeric($value);
	}

	function setcodatividade($value){
		$this->fields["codatividade"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setagencia($value){
		$this->fields["agencia"] = value_string($value,20);
	}

	function setcontacorrente($value){
		$this->fields["contacorrente"] = value_string($value,20);
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

	function setfone($value){
		$this->fields["fone"] = value_string($value,20);
	}

	function setfax($value){
		$this->fields["fax"] = value_string($value,20);
	}

	function setcontato1($value){
		$this->fields["contato1"] = value_string($value,40);
	}

	function setcargo1($value){
		$this->fields["cargo1"] = value_string($value,30);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value,20);
	}

	function setemail1($value){
		$this->fields["email1"] = value_string($value,60);
	}

	function setcontato2($value){
		$this->fields["contato2"] = value_string($value,40);
	}

	function setcargo2($value){
		$this->fields["cargo2"] = value_string($value,30);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value,20);
	}

	function setemail2($value){
		$this->fields["email2"] = value_string($value,60);
	}

	function setcontato3($value){
		$this->fields["contato3"] = value_string($value,40);
	}

	function setcargo3($value){
		$this->fields["cargo3"] = value_string($value,30);
	}

	function setfone3($value){
		$this->fields["fone3"] = value_string($value,20);
	}

	function setemail3($value){
		$this->fields["email3"] = value_string($value,60);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setnumero($value){
		$this->fields["numero"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value,40);
	}
}
?>