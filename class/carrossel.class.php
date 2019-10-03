<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Carrossel extends Cadastro{
	function __construct($codcarrossel = NULL){
		parent::__construct();
		$this->table = "carrossel";
		$this->primarykey = array("codcarrossel");
		$this->setcodcarrossel($codcarrossel);
		
		if($this->getcodcarrossel() != NULL){
			$this->searchbyobject();
		}
	}

	function save(){
		$this->con->start_transaction();
		if(!parent::save()){
			$this->con->rollback();
			return FALSE;
		}

		if(is_file("../ecommerce/img/carrossel/temp.png")){
			copy("../ecommerce/img/carrossel/temp.png", "../ecommerce/img/carrossel/{$this->getcodcarrossel()}.png");
			unlink("../ecommerce/img/carrossel/temp.png");
		}

		$this->con->commit();
		return true;
	}

	function getcodcarrossel(){
		return $this->fields["codcarrossel"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getlink(){
		return $this->fields["link"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function setcodcarrossel($value){
		$this->fields["codcarrossel"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,100);
	}

	function setlink($value){
		$this->fields["link"] = value_string($value,200);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}
}