<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FidelidadePremio extends Cadastro{

	function __construct($codfidelidadepremio = NULL){
		parent::__construct();
		$this->table = "fidelidadepremio";
		$this->primarykey = array("codfidelidadepremio");
		$this->setcodfidelidadepremio($codfidelidadepremio);

		if(!is_null($this->getcodfidelidadepremio())){
			$this->searchbyobject();
		}
	}

	public function getcodfidelidadepremio(){
		return $this->fields["codfidelidadepremio"];
	}

	public function getcodproduto(){
		return $this->fields["codproduto"];
	}

	public function getdescricao(){
		return $this->fields["descricao"];
	}

	public function getpontosresgate(){
		return $this->fields["pontosresgate"];
	}

    public function getdisponivel(){
        return $this->fields["disponivel"];
    }
    
	public function setcodfidelidadepremio($value){
		$this->fields["codfidelidadepremio"] = value_numeric($value);
	}

	public function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	public function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 100);
	}

	public function setpontosresgate($value){
		$this->fields["pontosresgate"] = value_numeric($value);
	}
    
    public function setdisponivel($value){
        $this->fields["disponivel"] = value_string($value, 1);
    }
}
