<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItOrcamento extends Cadastro{
	function __construct($codorcamento = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "itorcamento";
		$this->primarykey = array("codorcamento", "codproduto");
		$this->setcodorcamento($codorcamento);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodorcamento()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getcodorcamento(){
		return $this->fields["codorcamento"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],2,",","") : $this->fields["quantidade"]);
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getqtdeunidade($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidade"],2,",","") : $this->fields["qtdeunidade"]);
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->fields["percdescto"],2,",","") : $this->fields["percdescto"]);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->fields["valdescto"],2,",","") : $this->fields["valdescto"]);
	}

	function getpercacresc($format = FALSE){
		return ($format ? number_format($this->fields["percacresc"],2,",","") : $this->fields["percacresc"]);
	}

	function getvalacresc($format = FALSE){
		return ($format ? number_format($this->fields["valacresc"],2,",","") : $this->fields["valacresc"]);
	}

	function getpercfrete($format = FALSE){
		return ($format ? number_format($this->fields["percfrete"],2,",","") : $this->fields["percfrete"]);
	}

	function getvalfrete($format = FALSE){
		return ($format ? number_format($this->fields["valfrete"],2,",","") : $this->fields["valfrete"]);
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"],2,",","") : $this->fields["totaldesconto"]);
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"],2,",","") : $this->fields["totalacrescimo"]);
	}

	function gettotalfrete($format = FALSE){
		return ($format ? number_format($this->fields["totalfrete"],2,",","") : $this->fields["totalfrete"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"],2,",","") : $this->fields["totalbruto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function getprecovrj($format = FALSE){
		return ($format ? number_format($this->fields["precovrj"], 2, ",", "") : $this->fields["precovrj"]);
	}

	function getprecovrjof($format = FALSE){
		return ($format ? number_format($this->fields["precovrjof"], 2, ",", "") : $this->fields["precovrjof"]);
	}

	function setcodorcamento($value){
		$this->fields["codorcamento"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setqtdeunidade($value){
		$this->fields["qtdeunidade"] = value_numeric($value);
	}

	function setpercdescto($value){
		$this->fields["percdescto"] = value_numeric($value);
	}

	function setvaldescto($value){
		$this->fields["valdescto"] = value_numeric($value);
	}

	function setpercacresc($value){
		$this->fields["percacresc"] = value_numeric($value);
	}

	function setvalacresc($value){
		$this->fields["valacresc"] = value_numeric($value);
	}

	function setpercfrete($value){
		$this->fields["percfrete"] = value_numeric($value);
	}

	function setvalfrete($value){
		$this->fields["valfrete"] = value_numeric($value);
	}

	function settotaldesconto($value){
		$this->fields["totaldesconto"] = value_numeric($value);
	}

	function settotalacrescimo($value){
		$this->fields["totalacrescimo"] = value_numeric($value);
	}

	function settotalfrete($value){
		$this->fields["totalfrete"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value,200);
	}

	function setprecovrj($value){
		$this->fields["precovrj"] = value_numeric($value);
	}

	function setprecovrjof($value){
		$this->fields["precovrjof"] = value_numeric($value);
	}
}
?>