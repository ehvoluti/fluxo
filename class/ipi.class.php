<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Ipi extends Cadastro{
	function __construct($codipi = NULL){
		parent::__construct();
		$this->table = "ipi";
		$this->primarykey = "codipi";
		$this->setcodipi($codipi);
		if($this->getcodipi() != NULL){
			$this->searchbyobject();
		}
	}

	function save($object = null){
        if(strlen($this->gettptribipi()) == 0){
            $this->settptribipi("P");
        }
		if(strlen($this->getcodcstent()) === 0 && strlen($this->getcodcstsai()) > 0 && $this->getcodcstsai() >= 50){
			$this->setcodcstent(str_pad(($this->getcodcstsai() - 50), 2, "0", STR_PAD_LEFT));
		}
		if(strlen($this->getcodcstsai()) === 0 && strlen($this->getcodcstent()) > 0 && $this->getcodcstent() < 50){
			$this->setcodcstsai($this->getcodcstent() + 50);
		}
		if(strlen($this->getdescricao()) == 0){
			$this->setdescricao(($this->gettptribipi() == "P" ? "Percentual " : "Fixo ").number_format($this->getaliqipi(),2,",",".")." - CST ".$this->getcodcstsai());
		}
		return parent::save($object);
	}

	function getcodipi(){
		return $this->fields["codipi"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function gettptribipi($format = FALSE){
		return $this->fields["tptribipi"];
	}

	function getaliqipi($format = FALSE){
		return ($format ? number_format($this->fields["aliqipi"],2,",","") : $this->fields["aliqipi"]);
	}

	function getcodcstent(){
		return $this->fields["codcstent"];
	}

	function getcodcstsai(){
		return $this->fields["codcstsai"];
	}

	function setcodipi($value){
		$this->fields["codipi"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function settptribipi($value){
		$this->fields["tptribipi"] = value_string($value,1);
	}

	function setaliqipi($value){
		$this->fields["aliqipi"] = value_numeric($value);
	}

	function setcodcstent($value){
		$this->fields["codcstent"] = value_string($value,2);
	}

	function setcodcstsai($value){
		$this->fields["codcstsai"] = value_string($value,2);
	}
}
?>