<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class PisCofins extends Cadastro{
	function __construct($codpiscofins = NULL){
		parent::__construct();
		$this->table = "piscofins";
		$this->primarykey = array("codpiscofins");
		$this->setcodpiscofins($codpiscofins);
		if(!is_null($this->getcodpiscofins())){
			$this->searchbyobject();
		}
	}

	function gerar_descricao(){
		if(strlen($this->gettipo()) == 0){
			return NULL;
		}else{
			switch($this->gettipo()){
				//case "T": $tipo = "Tributado"; break;
				//case "I": $tipo = "Isento"; break;
				//case "M": $tipo = "Monofasico"; break;
				//case "N": $tipo = "Nao Tributado"; break;
				//case "Z": $tipo = "Zero"; break;
				default : $tipo = $this->gettipo(); break;
			}
			$descricao  = $tipo." - PIS ".$this->getaliqpis(TRUE)."% Cofins ".$this->getaliqcofins(TRUE)."% ";
			$descricao .= "CST ".$this->getcodcst();
			return $descricao;
		}
	}

	function save($object = null){
		if(strlen($this->getdescricao()) == 0){
			$this->setdescricao($this->gerar_descricao());
		}
		return parent::save($object);
	}

	function getcodpiscofins(){
		return $this->fields["codpiscofins"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"],4,",","") : $this->fields["aliqpis"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"],4,",","") : $this->fields["aliqcofins"]);
	}

	function getcodcst(){
		return $this->fields["codcst"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getcodccs(){
		return $this->fields["codccs"];
	}

	function getredpis($format = FALSE){
		return ($format ? number_format($this->fields["redpis"],4,",","") : $this->fields["redpis"]);
	}

	function getredcofins($format = FALSE){
		return ($format ? number_format($this->fields["redcofins"],4,",","") : $this->fields["redcofins"]);
	}

	function setcodpiscofins($value){
		$this->fields["codpiscofins"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
	}

	function setcodcst($value){
		$this->fields["codcst"] = value_string($value,2);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setcodccs($value){
		$this->fields["codccs"] = value_string($value,2);
	}

	function setredpis($value){
		$this->fields["redpis"] = value_numeric($value);
	}

	function setredcofins($value){
		$this->fields["redcofins"] = value_numeric($value);
	}
}
?>