<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItCupom extends Cadastro{
	function __construct($idcupom = NULL, $codproduto = NULL, $codprodutopai = NULL,$status = NULL){
		parent::__construct();
		$this->table = "itcupom";
		$this->primarykey = array("idcupom", "codproduto", "codprodutopai","status");
		$this->setidcupom($idcupom);
		$this->setcodproduto($codproduto);
		$this->setcodprodutopai($codprodutopai);
		$this->setstatus($status);
		if(!is_null($this->getidcupom()) && !is_null($this->getcodproduto()) && !is_null($this->getcodprodutopai()) && !is_null($this->getstatus())){
			$this->searchbyobject();
		}
	}

	function save($object = NULL){
		if(strlen($this->getcodprodutopai()) == 0){
			$this->setcodprodutopai($this->getcodproduto());
		}
		return parent::save($object);
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],4,",","") : $this->fields["preco"]);
	}

	function getdesconto($format = FALSE){
		return ($format ? number_format($this->fields["desconto"],4,",","") : $this->fields["desconto"]);
	}

	function getcustorep($format = FALSE){
		return ($format ? number_format($this->fields["custorep"],4,",","") : $this->fields["custorep"]);
	}

	function getcustosemimp($format = FALSE){
		return ($format ? number_format($this->fields["custosemimp"],4,",","") : $this->fields["custosemimp"]);
	}

	function getcustotab($format = FALSE){
		return ($format ? number_format($this->fields["custotab"],4,",","") : $this->fields["custotab"]);
	}

	function getvalortotal($format = FALSE){
		return ($format ? number_format($this->fields["valortotal"],4,",","") : $this->fields["valortotal"]);
	}

	function getacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["acrescimo"],4,",","") : $this->fields["acrescimo"]);
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"],2,",","") : $this->fields["aliqicms"]);
	}

	function getcodmovimento(){
		return $this->fields["codmovimento"];
	}

	function getidcupom(){
		return $this->fields["idcupom"];
	}

	function gettptribicms(){
		return $this->fields["tptribicms"];
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"],4,",","") : $this->fields["aliqpis"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"],4,",","") : $this->fields["aliqcofins"]);
	}

	function getcodprodutopai(){
		return $this->fields["codprodutopai"];
	}

	function getcomposicao(){
		return $this->fields["composicao"];
	}

	function gettotalbasepis($format = FALSE){
		return ($format ? number_format($this->fields["totalbasepis"],2,",","") : $this->fields["totalbasepis"]);
	}

	function gettotalbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["totalbasecofins"],2,",","") : $this->fields["totalbasecofins"]);
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"],2,",","") : $this->fields["totalpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"],2,",","") : $this->fields["totalcofins"]);
	}

	function getstatus(){
		return $this->fields["status"];
	}
	
	function getcodsupervisor(){
		return $this->fields["codsupervisor"];
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function setdesconto($value){
		$this->fields["desconto"] = value_numeric($value);
	}

	function setcustorep($value){
		$this->fields["custorep"] = value_numeric($value);
	}

	function setcustosemimp($value){
		$this->fields["custosemimp"] = value_numeric($value);
	}

	function setcustotab($value){
		$this->fields["custotab"] = value_numeric($value);
	}

	function setvalortotal($value){
		$this->fields["valortotal"] = value_numeric($value);
	}

	function setacrescimo($value){
		$this->fields["acrescimo"] = value_numeric($value);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function setcodmovimento($value){
		$this->fields["codmovimento"] = value_numeric($value);
	}

	function setidcupom($value){
		$this->fields["idcupom"] = value_numeric($value);
	}

	function settptribicms($value){
		$this->fields["tptribicms"] = value_string($value,1);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
	}

	function setcodprodutopai($value){
		$this->fields["codprodutopai"] = value_numeric($value);
	}

	function setcomposicao($value){
		$this->fields["composicao"] = value_string($value,1);
	}

	function settotalbasepis($value){
		$this->fields["totalbasepis"] = value_numeric($value);
	}

	function settotalbasecofins($value){
		$this->fields["totalbasecofins"] = value_numeric($value);
	}

	function settotalpis($value){
		$this->fields["totalpis"] = value_numeric($value);
	}

	function settotalcofins($value){
		$this->fields["totalcofins"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}
		
	function setcodsupervisor($value){
		$this->fields["codsupervisor"] = value_numeric($value);
	}
}
?>