<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class SubOcorrencia extends Cadastro{
	function __construct($codsubocorrencia = NULL){
		parent::__construct();
		$this->table = "subocorrencia";
		$this->primarykey = array("codsubocorrencia");
		$this->setcodsubocorrencia($codsubocorrencia);
		if(!is_null($this->getcodsubocorrencia())){
			$this->searchbyobject();
		}
	}

	function getcodsubocorrencia(){
		return $this->fields["codsubocorrencia"];
	}

	function getcodocorrencia(){
		return $this->fields["codocorrencia"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getdtexecucao($format = FALSE){
		return ($format ? convert_date($this->fields["dtexecucao"],"Y-m-d","d/m/Y") : $this->fields["dtexecucao"]);
	}

	function gethrexecucao(){
		return substr($this->fields["hrexecucao"],0,8);
	}

	function gettarefa(){
		return $this->fields["tarefa"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"],"Y-m-d","d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"],0,8);
	}

	function getdtlimite($format = FALSE){
		return ($format ? convert_date($this->fields["dtlimite"],"Y-m-d","d/m/Y") : $this->fields["dtlimite"]);
	}

	function gethrlimite(){
		return substr($this->fields["hrlimite"],0,8);
	}

	function getconclusao(){
		return $this->fields["conclusao"];
	}

	function setcodsubocorrencia($value){
		$this->fields["codsubocorrencia"] = value_numeric($value);
	}

	function setcodocorrencia($value){
		$this->fields["codocorrencia"] = value_numeric($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setdtexecucao($value){
		$this->fields["dtexecucao"] = value_date($value);
	}

	function sethrexecucao($value){
		$this->fields["hrexecucao"] = value_time($value);
	}

	function settarefa($value){
		$this->fields["tarefa"] = value_string($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setdtlimite($value){
		$this->fields["dtlimite"] = value_date($value);
	}

	function sethrlimite($value){
		$this->fields["hrlimite"] = value_time($value);
	}

	function setconclusao($value){
		$this->fields["conclusao"] = value_string($value);
	}
}
?>