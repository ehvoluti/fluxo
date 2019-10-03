<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Finalizadora extends Cadastro{
	function __construct($codestabelec = NULL, $codfinaliz = NULL){
		parent::__construct();
		$this->table = "finalizadora";
		$this->primarykey = array("codestabelec","codfinaliz");
		$this->newrelation("finalizadora","codestabelec","estabelecimento","codestabelec");
		$this->setcodestabelec($codestabelec);
		$this->setcodfinaliz($codfinaliz);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getcodfinaliz())){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('codcatlancto','".$this->getcodsubcatlancto()."'); ";
		return $html;
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodfinaliz(){
		return $this->fields["codfinaliz"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getgerafinanceiro(){
		return $this->fields["gerafinanceiro"];
	}

	function gettipogeracao(){
		return $this->fields["tipogeracao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function getcodccusto(){
		return $this->fields["codccusto"];
	}

	function getcodmoeda(){
		return $this->fields["codmoeda"];
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getgeratesouraria(){
		return $this->fields["geratesouraria"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodfinaliz($value){
		$this->fields["codfinaliz"] = value_string($value,5);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value,1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setgerafinanceiro($value){
		$this->fields["gerafinanceiro"] = value_string($value,1);
	}

	function settipogeracao($value){
		$this->fields["tipogeracao"] = value_string($value,1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setcodccusto($value){
		$this->fields["codccusto"] = value_numeric($value);
	}

	function setcodmoeda($value){
		$this->fields["codmoeda"] = value_numeric($value);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setgeratesouraria($value){
		$this->fields["geratesouraria"] = value_string($value,1);
	}
}
?>