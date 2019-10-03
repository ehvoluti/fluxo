<?php
require_file("class/cadastro.class.php");

class ControleCarne extends Cadastro{
	function __construct($idcontrolecarne = NULL){
		parent::__construct();
		$this->table = "controlecarne";
		$this->primarykey = array("idcontrolecarne");
		$this->setidcontrolecarne($idcontrolecarne);
		if(!is_null($this->getidcontrolecarne())){
			$this->searchbyobject();
		}
	}

	function getidcontrolecarne(){
		return $this->fields["idcontrolecarne"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"],"Y-m-d","d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"],0,8);
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getimpresso(){
		return $this->fields["impresso"];
	}

	function getconteudo(){
		return $this->fields["conteudo"];
	}

	function setidcontrolecarne($value){
		$this->fields["idcontrolecarne"] = value_numeric($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setimpresso($value){
		$this->fields["impresso"] = value_string($value,1);
	}

	function setconteudo($value){
		$this->fields["conteudo"] = value_string($value);
	}
}
?>