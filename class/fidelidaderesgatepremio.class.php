<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FidelidadeResgatePremio extends Cadastro{

	function __construct($codfidelidaderesgatepremio = NULL){
		parent::__construct();
		$this->table = "fidelidaderesgatepremio";
		$this->primarykey = array("codfidelidaderesgatepremio");
		$this->setcodfidelidaderesgatepremio($codfidelidaderesgatepremio);

		if(!is_null($this->getcodfidelidaderesgatepremio())){
			$this->searchbyobject();
		}
	}

	function getcodfidelidaderesgatepremio(){
		return $this->fields["codfidelidaderesgatepremio"];
	}

	function getcodfidelidaderesgate(){
		return $this->fields["codfidelidaderesgate"];
	}

	function getcodfidelidadepremio(){
		return $this->fields["codfidelidadepremio"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getpontosresgatados(){
		return $this->fields["pontosresgatados"];
	}

	function getquantidadepremio(){
		return $this->fields["quantidadepremio"];
	}

	function setcodfidelidaderesgatepremio($value){
		$this->fields["codfidelidaderesgatepremio"] = value_numeric($value);
	}

	function setcodfidelidaderesgate($value){
		$this->fields["codfidelidaderesgate"] = value_numeric($value);
	}

	function setcodfidelidadepremio($value){
		$this->fields["codfidelidadepremio"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setpontosresgatados($value){
		$this->fields["pontosresgatados"] = value_numeric($value);
	}

	function setquantidadepremio($value){
		$this->fields["quantidadepremio"] = value_numeric($value);
	}
}