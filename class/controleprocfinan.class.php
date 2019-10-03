<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ControleProcFinan extends Cadastro{
	function __construct($codcontrprocfinan = NULL){
		parent::__construct();
		$this->table = "controleprocfinan";
		$this->primarykey = array("codcontrprocfinan");
		$this->setcodcontrprocfinan($codcontrprocfinan);
		if(!is_null($this->getcodcontrprocfinan())){
			$this->searchbyobject();
		}
	}

	function getcodcontrprocfinan(){
		return $this->fields["codcontrprocfinan"];
	}

	function gettipoprocesso(){
		return $this->fields["tipoprocesso"];
	}

	function getdataprocesso($format = FALSE){
		return ($format ? convert_date($this->fields["dataprocesso"],"Y-m-d","d/m/Y") : $this->fields["dataprocesso"]);
	}

	function gethoraprocesso(){
		return $this->fields["horaprocesso"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

    function getpagrec(){
        return $this->fields["pagrec"];
    }
    
	function setcodcontrprocfinan($value){
		$this->fields["codcontrprocfinan"] = value_numeric($value);
	}

	function settipoprocesso($value){
		$this->fields["tipoprocesso"] = value_string($value,2);
	}

	function setdataprocesso($value){
		$this->fields["dataprocesso"] = value_date($value);
	}

	function sethoraprocesso($value){
		$this->fields["horaprocesso"] = value_string($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}
    
    function setpagrec($value){
        $this->fields["pagrec"] = value_string($value,1);
    }
}
?>