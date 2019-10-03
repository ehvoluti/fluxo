<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NcmEstado extends Cadastro{
	function __construct($idncm = NULL, $uf = NULL){
		parent::__construct();
		$this->table = "ncmestado";
		$this->primarykey = array("idncm", "uf");
		$this->setidncm($idncm);
		$this->setuf($uf);
		if(!is_null($this->getidncm()) && !is_null($this->getuf())){
			$this->searchbyobject();
		}
	}

	function getidncm(){
		return $this->fields["idncm"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getconvenioicms(){
		return $this->fields["convenioicms"];
	}

	function getaliqiva($format = FALSE){
        return ($format ? number_format($this->fields["aliqiva"],4,",","") : $this->fields["aliqiva"]);
    }

	function getajustariva(){
		return $this->fields["ajustariva"];
	}

	function getaliqfcp($format = FALSE){
		return ($format ? number_format($this->fields["aliqfcp"], 4, ",", "") : $this->fields["aliqfcp"]);
	}

	function getaliqinterna($format = FALSE){
		return ($format ? number_format($this->fields["aliqinterna"], 4, ",","") : $this->fields["aliqinterna"]);
	}

	function getcalculardifal(){
		return $this->fields["calculardifal"];
	}

	function getvalorpauta($format = FALSE){
		return ($format ? number_format($this->fields["valorpauta"], 4, ",","") : $this->fields["valorpauta"]);
	}

	function getcalculoliqmediast(){
		return $this->fields["calculoliqmediast"];
	}

	function setidncm($value){
		$this->fields["idncm"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setconvenioicms($value){
		$this->fields["convenioicms"] = value_string($value,1);
	}

	function setaliqiva($value){
        $this->fields["aliqiva"] = value_numeric($value);
    }

	function setajustariva($value){
		$this->fields["ajustariva"] = value_string($value,1);
	}

	function setaliqfcp($value){
		$this->fields["aliqfcp"] = value_numeric($value);
	}

	function setaliqinterna($value){
		$this->fields["aliqinterna"] = value_numeric($value);
	}

	function setcalculardifal($value){
		$this->fields["calculardifal"] = value_string($value,1);
	}

	function setvalorpauta($value){
		$this->fields["valorpauta"] = value_numeric($value);
	}

	function setcalculoliqmediast($value){
		$this->fields["calculoliqmediast"] = value_string($value, 1);
	}
}
?>