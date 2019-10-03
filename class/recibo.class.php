<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Recibo extends Cadastro{
	function __construct($codrecibo = NULL){
		parent::__construct();
		$this->table = "recibo";
		$this->primarykey = array("codrecibo");
		$this->setcodrecibo($codrecibo);
		if(!is_null($this->getcodrecibo())){
			$this->searchbyobject();
		}
	}

	function getcodrecibo(){
		return $this->fields["codrecibo"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumrecibo(){
		return $this->fields["numrecibo"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"],"Y-m-d","d/m/Y") : $this->fields["dtemissao"]);
	}

	function getvalortotal($format = FALSE){
		return ($format ? number_format($this->fields["valortotal"],2,",","") : $this->fields["valortotal"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getquantimp(){
		return $this->fields["quantimp"];
	}
	
	function setcodrecibo($value){
		$this->fields["codrecibo"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumrecibo($value){
		$this->fields["numrecibo"] = value_numeric($value);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setvalortotal($value){
		$this->fields["valortotal"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}
	
	function setquantimp($value){
		$this->fields["quantimp"] = value_numeric($value);
	}
}
?>