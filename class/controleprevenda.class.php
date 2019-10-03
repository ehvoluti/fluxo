<?php
require_file("class/cadastro.class.php");

class ControlePreVenda extends Cadastro{
	function __construct($codcontroleprevenda = NULL){
		parent::__construct();
		$this->table = "controleprevenda";
		$this->primarykey = array("codcontroleprevenda");
		$this->setcodcontroleprevenda($codcontroleprevenda);
		if(!is_null($this->getcodcontroleprevenda())){
			$this->searchbyobject();
		}
	}

	function getcodcontroleprevenda(){
		return $this->fields["codcontroleprevenda"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"],"Y-m-d","d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"],0,8);
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnomearquivo(){
		return $this->fields["nomearquivo"];
	}

	function getconteudo(){
		return $this->fields["conteudo"];
	}

	function getprocessado(){
		return $this->fields["processado"];
	}

	function setcodcontroleprevenda($value){
		$this->fields["codcontroleprevenda"] = value_numeric($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnomearquivo($value){
		$this->fields["nomearquivo"] = value_string($value,500);
	}

	function setconteudo($value){
		$this->fields["conteudo"] = value_string($value);
	}

	function setprocessado($value){
		$this->fields["processado"] = value_string($value,1);
	}
}
?>