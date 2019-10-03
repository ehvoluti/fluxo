<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class CliCartaoProp extends Cadastro{
	function __construct($codcliente = NULL,$codcartao = NULL){
		parent::__construct();
		$this->newrelation("clicartaoprop","codcliente","cliente","codcliente");
		$this->newrelation("clicartaoprop","codcartao","cartao","codcartao");
		$this->table = "clicartaoprop";
		$this->primarykey = array("codcliente","codcartao");
		$this->setcodcliente($codcliente);
		$this->setcodcartao($codcartao);
		if($this->getcodcliente() != NULL && $this->getcodcartao() != NULL){
			$this->searchbyobject();
		}
	}

	function save(){
		$object = objectbytable("clicartaoprop",array($this->getcodcliente(),$this->getcodcartao()),$this->con);
		if(strlen($this->getdtinclusao()) == 0){
			$this->setdtinclusao(date("d/m/Y"));
		}
		if(strlen($this->getdtstatus()) == 0 || $object->getstatus() != $this->getstatus()){
			$this->setdtstatus(date("d/m/Y"));
		}
		return parent::save($object);
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getcodcartao(){
		return $this->fields["codcartao"];
	}

	function getcodgrfat(){
		return $this->fields["codgrfat"];
	}

	function getdtinclusao(){
		return $this->fields["dtinclusao"];
	}

	function getstatus($desc = FALSE){
		$value = $this->fields["status"];
		if($desc){
			switch($value){
				case "A": $value = "Ativo"; break;
				case "B": $value = "Bloqueio Automático"; break;
				case "M": $value = "Bloqueio Manual"; break;
				case "C": $value = "Cancelado"; break;
				case "I": $value = "Inativo"; break;
			}
		}
		return $value;
	}

	function getdtstatus(){
		return $this->fields["dtstatus"];
	}

	function getvalorlimite($format = FALSE){
		return ($format ? number_format($this->fields["valorlimite"],2,",","") : $this->fields["valorlimite"]);
	}

	function getvalutilizado($format = FALSE){
		return ($format ? number_format($this->fields["valutilizado"],2,",","") : $this->fields["valutilizado"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdescto($format = FALSE){
		return ($format ? number_format($this->fields["descto"],2,",","") : $this->fields["descto"]);
	}

	function getdtultfecto(){
		return $this->fields["dtultfecto"];
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setcodcartao($value){
		$this->fields["codcartao"] = value_numeric($value);
	}

	function setcodgrfat($value){
		$this->fields["codgrfat"] = value_numeric($value);
	}

	function setdtinclusao($value){
		$this->fields["dtinclusao"] = value_date($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_check($value,array("A","B","M","C","I"));
	}

	function setdtstatus($value){
		$this->fields["dtstatus"] = value_date($value);
	}

	function setvalorlimite($value){
		$this->fields["valorlimite"] = value_numeric($value);
	}

	function setvalutilizado($value){
		$this->fields["valutilizado"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,500);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdescto($value){
		$this->fields["descto"] = value_numeric($value);
	}

	function setdtultfecto($value){
		$this->fields["dtultfecto"] = value_date($value);
	}
}
?>