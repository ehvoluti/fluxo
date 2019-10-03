<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Vasilhame extends Cadastro{
	function __construct($idvasilhame = NULL){
		parent::__construct();
		$this->table = "vasilhame";
		$this->primarykey = array("idvasilhame");
		$this->setcodestabelec($idvasilhame);
		if($this->getidvasilhame() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumrecepcao(){
		return $this->fields["numrecepcao"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getcaixa(){
		return $this->fields["caixa"];
	}

	function getdtvenda($format = FALSE){
		return ($format ? convert_date($this->fields["dtvenda"],"Y-m-d","d/m/Y") : $this->fields["dtvenda"]);
	}

	function getdtrecepcao($format = FALSE){
		return ($format ? convert_date($this->fields["dtrecepcao"],"Y-m-d","d/m/Y") : $this->fields["dtrecepcao"]);
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getcodvasilhame(){
		return $this->fields["codvasilhame"];
	}

	function getidvasilhame(){
		return $this->fields["idvasilhame"];
	}

	function getcodmovimento(){
		return $this->fields["codmovimento"];
	}

	function getentsai(){
		return $this->fields["entsai"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumrecepcao($value){
		$this->fields["numrecepcao"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setcaixa($value){
		$this->fields["caixa"] = value_numeric($value);
	}

	function setdtvenda($value){
		$this->fields["dtvenda"] = value_date($value);
	}

	function setdtrecepcao($value){
		$this->fields["dtrecepcao"] = value_date($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setcodvasilhame($value){
		$this->fields["codvasilhame"] = value_numeric($value);
	}

	function setidvasilhame($value){
		$this->fields["idvasilhame"] = value_numeric($value);
	}

	function setcodmovimento($value){
		$this->fields["codmovimento"] = value_numeric($value);
	}

	function setentsai($value){
		$this->fields["entsai"] = value_string($value, 1);
	}
}