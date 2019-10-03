<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_once("../class/grupoprod.class.php");

class SubGrupo extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->newrelation("subgrupo","codgrupo","grupoprod","codgrupo");
		$this->newrelation("grupoprod","coddepto","departamento","coddepto");
		$this->table = "subgrupo";
		$this->primarykey = "codsubgrupo";
		$this->setcodsubgrupo($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getfieldvalues(){
		parent::getfieldvalues();
		$this->setcoddepto($_REQUEST["coddepto"]);
	}
	
	function setfieldvalues(){
		$grupoprod = objectbytable("grupoprod",$this->getcodgrupo(),$this->con); // Cria o grupo
		$html  = parent::setfieldvalues();
		$html .= "$('#coddepto').val('".$grupoprod->getcoddepto()."'); "; // Pega o codigo do depatamento no grupo joga no campo da formulario
		$html .= "filterchild('coddepto','".$this->getcodgrupo()."'); "; // Filtra o departamento
		return $html;
	}
		
	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getcodsubgrupo(){
		return $this->fields["codsubgrupo"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}
	
	function getpontos(){
		return $this->fields["pontos"];
	}
	
	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setcodsubgrupo($value){
		$this->fields["codsubgrupo"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
	
	function setpontos($value){
		$this->fields["pontos"] = value_numeric($value);
	}
	
	function setcoddepto($value){
		$this->setrelationvalue("grupoprod","coddepto",value_numeric($value));
	}
}
?>