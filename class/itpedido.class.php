<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/itemcalculo.class.php");

class ItPedido extends Cadastro{

	function __construct($iditpedido = NULL){
		parent::__construct();
		$this->table = "itpedido";
		$this->primarykey = array("iditpedido");
		$this->setiditpedido($iditpedido);
		if(!is_null($this->getiditpedido())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcustorep($format = FALSE){
		return ($format ? number_format($this->fields["custorep"], 4, ",", "") : $this->fields["custorep"]);
	}

	function getcustosemimp($format = FALSE){
		return ($format ? number_format($this->fields["custosemimp"], 4, ",", "") : $this->fields["custosemimp"]);
	}

	function getcustotab($format = FALSE){
		return ($format ? number_format($this->fields["custotab"], 4, ",", "") : $this->fields["custotab"]);
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"], 4, ",", "") : $this->fields["quantidade"]);
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"], 6, ",", "") : $this->fields["preco"]);
	}

	function getpercipi($format = FALSE){
		return ($format ? number_format($this->fields["percipi"], 4, ",", "") : $this->fields["percipi"]);
	}

	function getvalipi($format = FALSE){
		return ($format ? number_format($this->fields["valipi"], 4, ",", "") : $this->fields["valipi"]);
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"], 4, ",", "") : $this->fields["aliqicms"]);
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->fields["percdescto"], 4, ",", "") : $this->fields["percdescto"]);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->fields["valdescto"], 6, ",", "") : $this->fields["valdescto"]);
	}

	function getreffornec(){
		return $this->fields["reffornec"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getqtdeatendida($format = FALSE){
		return ($format ? number_format($this->fields["qtdeatendida"], 4, ",", "") : $this->fields["qtdeatendida"]);
	}

	function getredicms($format = FALSE){
		return ($format ? number_format($this->fields["redicms"], 4, ",", "") : $this->fields["redicms"]);
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getqtdeunidade($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidade"], 4, ",", "") : $this->fields["qtdeunidade"]);
	}

	function getbonificado(){
		return $this->fields["bonificado"];
	}

	function getpercacresc($format = FALSE){
		return ($format ? number_format($this->fields["percacresc"], 4, ",", "") : $this->fields["percacresc"]);
	}

	function getvalacresc($format = FALSE){
		return ($format ? number_format($this->fields["valacresc"], 6, ",", "") : $this->fields["valacresc"]);
	}

	function getpercfrete($format = FALSE){
		return ($format ? number_format($this->fields["percfrete"], 4, ",", "") : $this->fields["percfrete"]);
	}

	function getvalfrete($format = FALSE){
		return ($format ? number_format($this->fields["valfrete"], 6, ",", "") : $this->fields["valfrete"]);
	}

	function getaliqiva($format = FALSE){
		return ($format ? number_format($this->fields["aliqiva"], 4, ",", "") : $this->fields["aliqiva"]);
	}

	function gettipoipi(){
		return $this->fields["tipoipi"];
	}

	function gettptribicms(){
		return $this->fields["tptribicms"];
	}

	function getvalorpauta($format = FALSE){
		return ($format ? number_format($this->fields["valorpauta"], 4, ",", "") : $this->fields["valorpauta"]);
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"], 4, ",", "") : $this->fields["totaldesconto"]);
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"], 4, ",", "") : $this->fields["totalacrescimo"]);
	}

	function gettotalfrete($format = FALSE){
		return ($format ? number_format($this->fields["totalfrete"], 4, ",", "") : $this->fields["totalfrete"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"], 4, ",", "") : $this->fields["totalipi"]);
	}

	function gettotalbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicms"], 4, ",", "") : $this->fields["totalbaseicms"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"], 4, ",", "") : $this->fields["totalicms"]);
	}

	function gettotalbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmssubst"], 4, ",", "") : $this->fields["totalbaseicmssubst"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"], 4, ",", "") : $this->fields["totalicmssubst"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"], 4, ",", "") : $this->fields["totalbruto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"], 4, ",", "") : $this->fields["totalliquido"]);
	}

	function gettotalarecolher($format = FALSE){
		return ($format ? number_format($this->fields["totalarecolher"], 4, ",", "") : $this->fields["totalarecolher"]);
	}

	function getseqitem(){
		return $this->fields["seqitem"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function gettotalbaseii($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseii"], 4, ",", "") : $this->fields["totalbaseii"]);
	}

	function gettotalii($format = FALSE){
		return ($format ? number_format($this->fields["totalii"], 4, ",", "") : $this->fields["totalii"]);
	}

	function getvaliof($format = FALSE){
		return ($format ? number_format($this->fields["valiof"], 4, ",", "") : $this->fields["valiof"]);
	}

	function getdespaduaneira($format = FALSE){
		return ($format ? number_format($this->fields["despaduaneira"], 4, ",", "") : $this->fields["despaduaneira"]);
	}

	function getvalseguro($format = FALSE){
		return ($format ? number_format($this->fields["valseguro"], 6, ",", "") : $this->fields["valseguro"]);
	}

	function getnumadicao(){
		return $this->fields["numadicao"];
	}

	function getseqadicao(){
		return $this->fields["seqadicao"];
	}

	function getvaldesctodi($format = FALSE){
		return ($format ? number_format($this->fields["valdesctodi"], 4, ",", "") : $this->fields["valdesctodi"]);
	}

	function getpercseguro($format = FALSE){
		return ($format ? number_format($this->fields["percseguro"], 4, ",", "") : $this->fields["percseguro"]);
	}

	function gettotalseguro($format = FALSE){
		return ($format ? number_format($this->fields["totalseguro"], 4, ",", "") : $this->fields["totalseguro"]);
	}

	function getaliqii($format = FALSE){
		return ($format ? number_format($this->fields["aliqii"], 4, ",", "") : $this->fields["aliqii"]);
	}

	function getvalsiscomex($format = FALSE){
		return ($format ? number_format($this->fields["valsiscomex"], 4, ",", "") : $this->fields["valsiscomex"]);
	}

	function gettotalcif($format = FALSE){
		return ($format ? number_format($this->fields["totalcif"], 4, ",", "") : $this->fields["totalcif"]);
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"], 4, ",", "") : $this->fields["aliqpis"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"], 4, ",", "") : $this->fields["aliqcofins"]);
	}

	function gettotalbasepis($format = FALSE){
		return ($format ? number_format($this->fields["totalbasepis"], 4, ",", "") : $this->fields["totalbasepis"]);
	}

	function gettotalbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["totalbasecofins"], 4, ",", "") : $this->fields["totalbasecofins"]);
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"], 2, ",", "") : $this->fields["totalpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"], 2, ",", "") : $this->fields["totalcofins"]);
	}

	function getredpis($format = FALSE){
		return ($format ? number_format($this->fields["redpis"], 4, ",", "") : $this->fields["redpis"]);
	}

	function getredcofins($format = FALSE){
		return ($format ? number_format($this->fields["redcofins"], 4, ",", "") : $this->fields["redcofins"]);
	}

	function getiditpedido(){
		return $this->fields["iditpedido"];
	}

	function getnumerolote(){
		return $this->fields["numerolote"];
	}

	function getdtvalidade($format = FALSE){
		return ($format ? convert_date($this->fields["dtvalidade"], "Y-m-d", "d/m/Y") : $this->fields["dtvalidade"]);
	}

	function getcodestabelectransf(){
		return $this->fields["codestabelectransf"];
	}

	function getdtentrega($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrega"], "Y-m-d", "d/m/Y") : $this->fields["dtentrega"]);
	}

	function getiditnotafiscalvd(){
		return $this->fields["iditnotafiscalvd"];
	}

	function getprecopolitica($format = FALSE){
		return ($format ? number_format($this->fields["precopolitica"], 4, ".", "") : $this->fields["precopolitica"]);
	}

	function getqtdeunidadeconf($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidadeconf"], 4, ".", "") : $this->fields["qtdeunidadeconf"]);
	}

	function getquantidadeconf($format = FALSE){
		return ($format ? number_format($this->fields["quantidadeconf"], 4, ".", "") : $this->fields["quantidadeconf"]);
	}

	function gettotalgnre($format = FALSE){
		return ($format ? number_format($this->fields["totalgnre"], 2, ",", "") : $this->fields["totalgnre"]);
	}

	function getpedcliente(){
		return $this->fields["pedcliente"];
	}

	function getseqitemcliente(){
		return $this->fields["seqitemcliente"];
	}

	function getvalorafrmm($format = FALSE){
		return ($format ? number_format($this->fields["valorafrmm"], 4, ",", "") : $this->fields["valorafrmm"]);
	}

	function getbasecalcufdest($format = FALSE){
		return ($format ? number_format($this->fields["basecalcufdest"], 2, ",", "") : $this->fields["basecalcufdest"]);
	}

	function getvalorbcfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["valorbcfcpufdest"], 4, ",", "") : $this->fields["valorbcfcpufdest"]);
	}

	function getaliqfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["aliqfcpufdest"], 4, ",", "") : $this->fields["aliqfcpufdest"]);
	}

	function getvalorfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["valorfcpufdest"], 2, ",", "") : $this->fields["valorfcpufdest"]);
	}

	function getaliqicmsufdest($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsufdest"], 4, ",", "") : $this->fields["aliqicmsufdest"]);
	}

	function getvaloricmsufdest($format = FALSE){
		return ($format ? number_format($this->fields["valoricmsufdest"], 2, ",", "") : $this->fields["valoricmsufdest"]);
	}

	function getaliqicmsinter($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsinter"], 4, ",", "") : $this->fields["aliqicmsinter"]);
	}

	function getvaloricmsufremet($format = FALSE){
		return ($format ? number_format($this->fields["valoricmsufremet"], 2, ",", "") : $this->fields["valoricmsufremet"]);
	}

	function getaliqicminterpart($format = FALSE){
		return ($format ? number_format($this->fields["aliqicminterpart"], 4, ",", "") : $this->fields["aliqicminterpart"]);
	}

	function getbasestretido($format = FALSE){
		return ($format ? number_format($this->fields["basestretido"], 4, ",", "") : $this->fields["basestretido"]);
	}

	function getvalorstretido($format = FALSE){
		return ($format ? number_format($this->fields["valorstretido"], 4, ",", "") : $this->fields["valorstretido"]);
	}

	function getqtdeunidadexml($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidadexml"], 4, ".", "") : $this->fields["qtdeunidadexml"]);
	}

	function getquantidadexml($format = FALSE){
		return ($format ? number_format($this->fields["quantidadexml"], 4, ".", "") : $this->fields["quantidadexml"]);
	}

	function getidcodigoservico(){
		return $this->fields["idcodigoservico"];
	}

	function getnattributacao(){
		return $this->fields["nattributacao"];
	}

	function getissretido(){
		return $this->fields["issretido"];
	}

	function getnatbccredito(){
		return $this->fields["natbccredito"];
	}

	function getaliquotainss($format = FALSE){
		return ($format ? number_format($this->fields["aliquotainss"], 2, ",", "") : $this->fields["aliquotainss"]);
	}

	function getvalorinss($format = FALSE){
		return ($format ? number_format($this->fields["valorinss"], 2, ",", "") : $this->fields["valorinss"]);
	}

	function getaliquotair($format = FALSE){
		return ($format ? number_format($this->fields["aliquotair"], 2, ",", "") : $this->fields["aliquotair"]);
	}

	function getvalorir($format = FALSE){
		return ($format ? number_format($this->fields["valorir"], 2, ",", "") : $this->fields["valorir"]);
	}

	function getaliquotacsll($format = FALSE){
		return ($format ? number_format($this->fields["aliquotacsll"], 2, ",", "") : $this->fields["aliquotacsll"]);
	}

	function getvalorcsll($format = FALSE){
		return ($format ? number_format($this->fields["valorcsll"], 2, ",", "") : $this->fields["valorcsll"]);
	}

	function getvalordesoneracao($format = FALSE){
		return ($format ? number_format($this->fields["valordesoneracao"], 4,",","") : $this->fields["valordesoneracao"]);
	}

	function getaliqicmsdesoneracao($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsdesoneracao"], 4,",","") : $this->fields["aliqicmsdesoneracao"]);
	}

	function getmotivodesoneracao(){
		return $this->fields["motivodesoneracao"];
	}

	function getbasecalculofcpst($format = FALSE){
		return ($format ? number_format($this->fields["basecalculofcpst"], 4,",","") : $this->fields["basecalculofcpst"]);
	}

	function getpercfcpst($format = FALSE){
		return ($format ? number_format($this->fields["percfcpst"], 4,",","") : $this->fields["percfcpst"]);
	}

	function getvalorfcpst($format = FALSE){
		return ($format ? number_format($this->fields["valorfcpst"], 4,",","") : $this->fields["valorfcpst"]);
	}

	function gettotalbaseicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmsnaoaproveitavel"], 2,",","") : $this->fields["totalbaseicmsnaoaproveitavel"]);
	}

	function gettotalicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsnaoaproveitavel"], 2,",","") : $this->fields["totalicmsnaoaproveitavel"]);
	}

	function getaliqicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsnaoaproveitavel"], 4,",","") : $this->fields["aliqicmsnaoaproveitavel"]);
	}

	function getredicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["redicmsnaoaproveitavel"], 4,",","") : $this->fields["redicmsnaoaproveitavel"]);
	}

	function getcsticmsnaoaproveitavel(){
		return $this->fields["csticmsnaoaproveitavel"];
	}

	/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
	function getprecovrj($format = FALSE){
		return ($format ? number_format($this->fields["precovrj"], 2, ",", "") : $this->fields["precovrj"]);
	}

	function getprecovrjof($format = FALSE){
		return ($format ? number_format($this->fields["precovrjof"], 2, ",", "") : $this->fields["precovrjof"]);
	}

	/* OS 5242 */
	function getentregaretira(){
		return $this->fields["entregaretira"];
	}

	function getqtdeentregueretirada($format = FALSE){
		return ($format ? number_format($this->fields["qtdeentregueretirada"], 2, ",", "") : $this->fields["qtdeentregueretirada"]);
	}
	/* */
	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
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

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
		$this->setprecopolitica($this->getpreco());
	}

	function setpercipi($value){
		$this->fields["percipi"] = value_numeric($value);
	}

	function setvalipi($value){
		$this->fields["valipi"] = value_numeric($value);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function setpercdescto($value){
		$this->fields["percdescto"] = value_numeric($value);
	}

	function setvaldescto($value){
		$this->fields["valdescto"] = value_numeric($value);
	}

	function setreffornec($value){
		$this->fields["reffornec"] = value_string($value, 20);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setqtdeatendida($value){
		$this->fields["qtdeatendida"] = value_numeric($value);
	}

	function setredicms($value){
		$this->fields["redicms"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setqtdeunidade($value){
		$this->fields["qtdeunidade"] = value_numeric($value);
	}

	function setbonificado($value){
		$this->fields["bonificado"] = value_string($value, 1);
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

	function setaliqiva($value){
		$this->fields["aliqiva"] = value_numeric($value);
	}

	function settipoipi($value){
		$this->fields["tipoipi"] = value_string($value, 1);
	}

	function settptribicms($value){
		$this->fields["tptribicms"] = value_string($value, 1);
	}

	function setvalorpauta($value){
		$this->fields["valorpauta"] = value_numeric($value);
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

	function settotalipi($value){
		$this->fields["totalipi"] = value_numeric($value);
	}

	function settotalbaseicms($value){
		$this->fields["totalbaseicms"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function settotalbaseicmssubst($value){
		$this->fields["totalbaseicmssubst"] = value_numeric($value);
	}

	function settotalicmssubst($value){
		$this->fields["totalicmssubst"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function settotalarecolher($value){
		$this->fields["totalarecolher"] = value_numeric($value);
	}

	function setseqitem($value){
		$this->fields["seqitem"] = value_numeric($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 500);
	}

	function settotalbaseii($value){
		$this->fields["totalbaseii"] = value_numeric($value);
	}

	function settotalii($value){
		$this->fields["totalii"] = value_numeric($value);
	}

	function setvaliof($value){
		$this->fields["valiof"] = value_numeric($value);
	}

	function setdespaduaneira($value){
		$this->fields["despaduaneira"] = value_numeric($value);
	}

	function setvalseguro($value){
		$this->fields["valseguro"] = value_numeric($value);
	}

	function setnumadicao($value){
		$this->fields["numadicao"] = value_numeric($value);
	}

	function setseqadicao($value){
		$this->fields["seqadicao"] = value_numeric($value);
	}

	function setvaldesctodi($value){
		$this->fields["valdesctodi"] = value_numeric($value);
	}

	function setpercseguro($value){
		$this->fields["percseguro"] = value_numeric($value);
	}

	function settotalseguro($value){
		$this->fields["totalseguro"] = value_numeric($value);
	}

	function setaliqii($value){
		$this->fields["aliqii"] = value_numeric($value);
	}

	function setvalsiscomex($value){
		$this->fields["valsiscomex"] = value_numeric($value);
	}

	function settotalcif($value){
		$this->fields["totalcif"] = value_numeric($value);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
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

	function setredpis($value){
		$this->fields["redpis"] = value_numeric($value);
	}

	function setredcofins($value){
		$this->fields["redcofins"] = value_numeric($value);
	}

	function setiditpedido($value){
		$this->fields["iditpedido"] = value_numeric($value);
	}

	function setnumerolote($value){
		$this->fields["numerolote"] = value_string($value, 40);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function setcodestabelectransf($value){
		$this->fields["codestabelectransf"] = value_numeric($value);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setiditnotafiscalvd($value){
		$this->fields["iditnotafiscalvd"] = value_numeric($value);
	}

	function setprecopolitica($value){
		$this->fields["precopolitica"] = value_numeric($value);
	}

	function setqtdeunidadeconf($value){
		$this->fields["qtdeunidadeconf"] = value_numeric($value);
	}

	function setquantidadeconf($value){
		$this->fields["quantidadeconf"] = value_numeric($value);
	}

	function settotalgnre($value){
		$this->fields["totalgnre"] = value_numeric($value);
	}

	function setpedcliente($value){
		$this->fields["pedcliente"] = value_string($value, 10);
	}

	function setseqitemcliente($value){
		$this->fields["seqitemcliente"] = value_numeric($value);
	}

	function setvalorafrmm($value){
		$this->fields["valorafrmm"] = value_numeric($value);
	}

	function setbasecalcufdest($value){
		$this->fields["basecalcufdest"] = value_numeric($value);
	}

	function setvalorbcfcpufdest($value){
		$this->fields["valorbcfcpufdest"] = value_numeric($value);
	}

	function setaliqfcpufdest($value){
		$this->fields["aliqfcpufdest"] = value_numeric($value);
	}

	function setvalorfcpufdest($value){
		$this->fields["valorfcpufdest"] = value_numeric($value);
	}

	function setaliqicmsufdest($value){
		$this->fields["aliqicmsufdest"] = value_numeric($value);
	}

	function setvaloricmsufdest($value){
		$this->fields["valoricmsufdest"] = value_numeric($value);
	}

	function setaliqicmsinter($value){
		$this->fields["aliqicmsinter"] = value_numeric($value);
	}

	function setvaloricmsufremet($value){
		$this->fields["valoricmsufremet"] = value_numeric($value);
	}

	function setaliqicminterpart($value){
		$this->fields["aliqicminterpart"] = value_numeric($value);
	}

	function setbasestretido($value){
		$this->fields["basestretido"] = value_numeric($value);
	}

	function setvalorstretido($value){
		$this->fields["valorstretido"] = value_numeric($value);
	}

	function setqtdeunidadecml($value){
		$this->fields["qtdeunidadexml"] = value_numeric($value);
	}

	function setquantidadexml($value){
		$this->fields["quantidadexml"] = value_numeric($value);
	}

	function setidcodigoservico($value){
		$this->fields["idcodigoservico"] = value_string($value, 20);
	}

	function setnattributacao($value){
		$this->fields["nattributacao"] = value_string($value, 1);
	}

	function setissretido($value){
		$this->fields["issretido"] = value_string($value, 1);
	}

	function setnatbccredito($value){
		$this->fields["natbccredito"] = value_string($value, 2);
	}

	function setaliquotainss($value){
		$this->fields["aliquotainss"] = value_numeric($value);
	}

	function setvalorinss($value){
		$this->fields["valorinss"] = value_numeric($value);
	}

	function setaliquotair($value){
		$this->fields["aliquotair"] = value_numeric($value);
	}

	function setvalorir($value){
		$this->fields["valorir"] = value_numeric($value);
	}

	function setaliquotacsll($value){
		$this->fields["aliquotacsll"] = value_numeric($value);
	}

	function setvalorcsll($value){
		$this->fields["valorcsll"] = value_numeric($value);
	}

	function setvalordesoneracao($value){
		$this->fields["valordesoneracao"] = value_numeric($value);
	}

	function setaliqicmsdesoneracao($value){
		$this->fields["aliqicmsdesoneracao"] = value_numeric($value);
	}

	function setmotivodesoneracao($value){
		$this->fields["motivodesoneracao"] = value_string($value, 1);
	}
	function setbasecalculofcpst($value){
		$this->fields["basecalculofcpst"] = value_numeric($value);
	}

	function setpercfcpst($value){
		$this->fields["percfcpst"] = value_numeric($value);
	}

	function setvalorfcpst($value){
		$this->fields["valorfcpst"] = value_numeric($value);
	}

	function settotalbaseicmsnaoaproveitavel($value){
		$this->fields["totalbaseicmsnaoaproveitavel"] = value_numeric($value);
	}

	function settotalicmsnaoaproveitavel($value){
		$this->fields["totalicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setaliqicmsnaoaproveitavel($value){
		$this->fields["aliqicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setredicmsnaoaproveitavel($value){
		$this->fields["redicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setcsticmsnaoaproveitavel($value){
		$this->fields["csticmsnaoaproveitavel"] = value_string($value, 3);
	}

	/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
	function setprecovrj($value){
		$this->fields["precovrj"] = value_numeric($value);
	}

	function setprecovrjof($value){
		$this->fields["precovrjof"] = value_numeric($value);
	}
	/* OS 5242 */
	function setentregaretira($value){
		$this->fields["entregaretira"] = value_string($value, 1);
	}

	function setqtdeentregueretirada($value){
		$this->fields["qtdeentregueretirada"] = value_numeric($value);
	}
	/* */
}