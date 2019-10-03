<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItNotaDiversa extends Cadastro{
	function __construct($idnotadiversa = NULL, $natoperacao = NULL){
		parent::__construct();
		$this->table = "itnotadiversa";
		$this->primarykey = array("idnotadiversa", "natoperacao");
		$this->setidnotadiversa($idnotadiversa);		
		$this->setnatoperacao($natoperacao);
		if(!is_null($this->getidnotadiversa()) && !is_null($this->getnatoperacao())){
			$this->searchbyobject();
		}
	}

	function getidnotadiversa(){
		return $this->fields["idnotadiversa"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"],2,",","") : $this->fields["totalicms"]);
	}

	function getbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["baseicms"],2,",","") : $this->fields["baseicms"]);
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"],2,",","") : $this->fields["aliqicms"]);
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"],2,",","") : $this->fields["totalpis"]);
	}

	function getbasepis($format = FALSE){
		return ($format ? number_format($this->fields["basepis"],2,",","") : $this->fields["basepis"]);
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"],2,",","") : $this->fields["aliqpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"],2,",","") : $this->fields["totalcofins"]);
	}

	function getbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["basecofins"],2,",","") : $this->fields["basecofins"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"],2,",","") : $this->fields["aliqcofins"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"],2,",","") : $this->fields["totalicmssubst"]);
	}

	function getbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["baseicmssubst"],2,",","") : $this->fields["baseicmssubst"]);
	}

	function getaliqicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmssubst"],2,",","") : $this->fields["aliqicmssubst"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"],2,",","") : $this->fields["totalipi"]);
	}

	function getbaseipi($format = FALSE){
		return ($format ? number_format($this->fields["baseipi"],2,",","") : $this->fields["baseipi"]);
	}

	function getaliqipi($format = FALSE){
		return ($format ? number_format($this->fields["aliqipi"],2,",","") : $this->fields["aliqipi"]);
	}

	function gettotalbaseoutras($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseoutras"],2,",","") : $this->fields["totalbaseoutras"]);
	}
	
	function gettotalbaseisenta($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseisenta"],2,",","") : $this->fields["totalbaseisenta"]);
	}

	function getcodcstpiscofins(){
		return $this->fields["codcstpiscofins"];
	}
	
	function getcodcstipi(){
		return $this->fields["codcstipi"];
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function setidnotadiversa($value){
		$this->fields["idnotadiversa"] = value_numeric($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value,9);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function setbaseicms($value){
		$this->fields["baseicms"] = value_numeric($value);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function settotalpis($value){
		$this->fields["totalpis"] = value_numeric($value);
	}

	function setbasepis($value){
		$this->fields["basepis"] = value_numeric($value);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function settotalcofins($value){
		$this->fields["totalcofins"] = value_numeric($value);
	}

	function setbasecofins($value){
		$this->fields["basecofins"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
	}

	function settotalicmssubst($value){
		$this->fields["totalicmssubst"] = value_numeric($value);
	}

	function setbaseicmssubst($value){
		$this->fields["baseicmssubst"] = value_numeric($value);
	}

	function setaliqicmssubst($value){
		$this->fields["aliqicmssubst"] = value_numeric($value);
	}

	function settotalipi($value){
		$this->fields["totalipi"] = value_numeric($value);
	}

	function setbaseipi($value){
		$this->fields["baseipi"] = value_numeric($value);
	}

	function setaliqipi($value){
		$this->fields["aliqipi"] = value_numeric($value);
	}

	function settotalbaseoutras($value){
		$this->fields["totalbaseoutras"] = value_numeric($value);
	}
	
	function settotalbaseisenta($value){
		$this->fields["totalbaseisenta"] = value_numeric($value);
	}

	function setcodcstpiscofins($value){
		$this->fields["codcstpiscofins"] = value_string($value,2);
	}
	
	function setcodcstipi($value){
		$this->fields["codcstipi"] = value_string($value,2);
	}
	
	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}
}