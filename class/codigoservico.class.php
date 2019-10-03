<?php
require_file("class/cadastro.class.php");

class CodigoServico extends Cadastro{
	function __construct($idcodigoservico = NULL){
		parent::__construct();
		$this->table = "codigoservico";
		$this->primarykey = array("idcodigoservico");
		$this->setidcodigoservico($idcodigoservico);
		if(!is_null($this->getidcodigoservico())){
			$this->searchbyobject();
		}
	}

	function getidcodigoservico(){
		return $this->fields["idcodigoservico"];
	}

	function getcodigosubitem(){
		return $this->fields["codigosubitem"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getaliquotaiss($format = FALSE){
		return ($format ? number_format($this->fields["aliquotaiss"],4,",","") : $this->fields["aliquotaiss"]);
	}

	function getaliquotainss($format = FALSE){
		return ($format ? number_format($this->fields["aliquotainss"],4,",","") : $this->fields["aliquotainss"]);
	}

	function getaliquotair($format = FALSE){
		return ($format ? number_format($this->fields["aliquotair"],4,",","") : $this->fields["aliquotair"]);
	}

	function getaliquotacsll($format = FALSE){
		return ($format ? number_format($this->fields["aliquotacsll"],4,",","") : $this->fields["aliquotacsll"]);
	}

	function setidcodigoservico($value){
		$this->fields["idcodigoservico"] = value_string($value,10);
	}

	function setcodigosubitem($value){
		$this->fields["codigosubitem"] = value_string($value,10);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,200);
	}

	function setaliquotaiss($value){
		$this->fields["aliquotaiss"] = value_numeric($value);
	}

	function setaliquotainss($value){
		$this->fields["aliquotainss"] = value_numeric($value);
	}

	function setaliquotair($value){
		$this->fields["aliquotair"] = value_numeric($value);
	}

	function setaliquotacsll($value){
		$this->fields["aliquotacsll"] = value_numeric($value);
	}
}
?>