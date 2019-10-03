<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Especie extends Cadastro{

	function __construct($codespecie = NULL){
		parent::__construct();
		$this->table = "especie";
		$this->primarykey = array("codespecie");
		$this->setcodespecie($codespecie);
		if(!is_null($this->getcodespecie())){
			$this->searchbyobject();
		}
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getespecie(){
		return $this->fields["especie"];
	}

	function getgerafluxo(){
		return $this->fields["gerafluxo"];
	}

	function getgeravinculado(){
		return $this->fields["geravinculado"];
	}

	function getgeraliquidado(){
		return $this->fields["geraliquidado"];
	}

	function getlimitecliente(){
		return $this->fields["limitecliente"];
	}

	function getfinancpercentual($format = FALSE){
		return ($format ? number_format($this->fields["financpercentual"], 2, ",", "") : $this->fields["financpercentual"]);
	}

	function getfinanctppessoa(){
		return $this->fields["financtppessoa"];
	}

	function getgerajuros(){
		return $this->fields["gerajuros"];
	}

	function getexibepontovenda(){
		return $this->fields["exibepontovenda"];
	}

	function getnaoprotestar(){
		return $this->fields["naoprotestar"];
	}

	function getparticipafidelizacao(){
		return $this->fields["participafidelizacao"];
	}

	function getfatorconversao($format = FALSE){
		return ($format ? number_format($this->fields["fatorconversao"], 4, ",", "") : $this->fields["fatorconversao"]);
	}

	function getgerafinanceiro(){
		return $this->fields["gerafinanceiro"];
	}
	/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
	function getpercdescpadrao($format = FALSE){
		return ($format ? number_format($this->fields["percdescpadrao"], 4, ",", "") : $this->fields["percdescpadrao"]);
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getpermalterarcondpagto(){
		return $this->fields["permalterarcondpagto"];
	}
	/*OS 5240*/
	function getexibepontooperacao(){
		return $this->fields["exibepontooperacao"];
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 30);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setespecie($value){
		$this->fields["especie"] = value_string($value, 2);
	}

	function setgerafluxo($value){
		$this->fields["gerafluxo"] = value_string($value, 1);
	}

	function setgeravinculado($value){
		$this->fields["geravinculado"] = value_string($value, 1);
	}

	function setgeraliquidado($value){
		$this->fields["geraliquidado"] = value_string($value, 1);
	}

	function setlimitecliente($value){
		$this->fields["limitecliente"] = value_numeric($value);
	}

	function setfinancpercentual($value){
		$this->fields["financpercentual"] = value_numeric($value);
	}

	function setfinanctppessoa($value){
		$this->fields["financtppessoa"] = value_string($value, 1);
	}

	function setgerajuros($value){
		$this->fields["gerajuros"] = value_string($value, 1);
	}

	function setexibepontovenda($value){
		$this->fields["exibepontovenda"] = value_string($value, 1);
	}

	function setnaoprotestar($value){
		$this->fields["naoprotestar"] = value_string($value, 1);
	}

	function setparticipafidelizacao($value){
		$this->fields["participafidelizacao"] = value_string($value, 1);
	}

	function setfatorconversao($value){
		$this->fields["fatorconversao"] = value_numeric($value);
	}

	function setgerafinanceiro($value){
		$this->fields["gerafinanceiro"] = value_string($value, 1);
	}

	function setexibepontooperacao($value){
		$this->fields["exibepontooperacao"] = value_string($value, 1);
	}

	function setpercdescpadrao($value){
		/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
		$this->fields["percdescpadrao"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setpermalterarcondpagto($value){
		$this->fields["permalterarcondpagto"] = value_string($value, 1);
	}
	/*OS 5240*/
}