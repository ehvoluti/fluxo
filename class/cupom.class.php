<?php
require_file("class/cadastro.class.php");

class Cupom extends Cadastro{
	function __construct($idcupom = NULL){
		parent::__construct();
		$this->table = "cupom";
		$this->primarykey = array("idcupom");
		$this->setidcupom($idcupom);
		if(!is_null($this->getidcupom())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcaixa($format = FALSE){
		return ($format ? number_format($this->fields["caixa"],0,",","") : $this->fields["caixa"]);
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function gethrmovto(){
		return substr($this->fields["hrmovto"],0,8);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"],2,",","") : $this->fields["totaldesconto"]);
	}

	function getcupom(){
		return $this->fields["cupom"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"],2,",","") : $this->fields["totalacrescimo"]);
	}

	function getidcupom(){
		return $this->fields["idcupom"];
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"],2,",","") : $this->fields["totalbruto"]);
	}

	function getnumeroecf(){
		return $this->fields["numeroecf"];
	}

	function getcpfcnpj(){
		return $this->fields["cpfcnpj"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getcomissao($format = FALSE){
		return ($format ? number_format($this->fields["comissao"],2,",","") : $this->fields["comissao"]);
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

	function getseqecf(){
		return $this->fields["seqecf"];
	}

	function getcodecf(){
		return $this->fields["codecf"];
	}

	function getchavecfe(){
		return $this->fields["chavecfe"];
	}

	function getreferencia(){
		return $this->fields["referencia"];
	}

	function getoperador(){
		return $this->fields["operador"];
	}

	function getnumfabricacao(){
		return $this->fields["numfabricacao"];
	}
	
	function getcodsupervisor(){
		return $this->fields["codsupervisor"];
	}

	function getnomecliente(){
		return $this->fields["nomecliente"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcaixa($value){
		$this->fields["caixa"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function sethrmovto($value){
		$this->fields["hrmovto"] = value_time($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function settotaldesconto($value){
		$this->fields["totaldesconto"] = value_numeric($value);
	}

	function setcupom($value){
		$this->fields["cupom"] = value_string($value,7);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function settotalacrescimo($value){
		$this->fields["totalacrescimo"] = value_numeric($value);
	}

	function setidcupom($value){
		$this->fields["idcupom"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function setnumeroecf($value){
		$this->fields["numeroecf"] = value_numeric($value);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value,20);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setcomissao($value){
		$this->fields["comissao"] = value_numeric($value);
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

	function setseqecf($value){
		$this->fields["seqecf"] = value_string($value,9);
	}

	function setcodecf($value){
		$this->fields["codecf"] = value_numeric($value);
	}

	function setchavecfe($value){
		$this->fields["chavecfe"] = value_string($value,44);
	}

	function setreferencia($value){
		$this->fields["referencia"] = value_string($value,100);
	}

	function setoperador($value){
		$this->fields["operador"] = value_numeric($value);
	}

	function setnumfabricacao($value){
		$this->fields["numfabricacao"] = value_string($value);
	}
	
	function setcodsupervisor($value){
		$this->fields["codsupervisor"] = value_numeric($value);
	}

	function setnomecliente($value){
		$this->fields["nomecliente"] = value_string($value);
	}
}
