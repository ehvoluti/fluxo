<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Lancamento extends Cadastro{

	function __construct($codlancto = NULL){
		parent::__construct();
		$this->table = "lancamento";
		$this->primarykey = array("codlancto");
		$this->autoinc = TRUE;
		$this->newrelation("lancamento", "codestabelec", "estabelecimento", "codestabelec");
		$this->newrelation("lancamento", "codcatlancto", "catlancto", "codcatlancto");
		$this->newrelation("lancamento", "codsubcatlancto", "subcatlancto", "codsubcatlancto");
		$this->setcodlancto($codlancto);
		if($this->getcodlancto() != NULL){
			$this->searchbyobject();
		}
	}

	function incluir_historico($texto){
		$this->sethistorico($this->gethistorico().date("d/m/Y H:i:s")."\n".$texto."\n\n");
	}

	function delete($forcar = FALSE){
		$this->connect();
		// Verifica se existe entrada de nota no lancamento
		if(strlen($this->getidnotafiscal()) > 0 && param("FINANCEIRO", "BLOQDELLANCTONF", $this->con) == "S"){
			$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel excluir o lan&ccedil;amento, pois existem notas fiscais relacionadas com o mesmo.";
			return FALSE;
		}
		return parent::delete();
	}

	function save($object = null){
		if(in_array($this->getcodtipoaceite(), array(8))){
			if(strlen($this->getanocompetencia()) == 0 || strlen($this->getmescompetencia()) == 0){
				$_SESSION["ERROR"] = "Informe o ano e m&ecirc;s de compet&ecirc;ncia quando for pagamento de GPS.";
				return FALSE;
			}
		}

		if(parent::save($object)){
			if(strlen($this->getcodlancto()) == 0){
				$this->searchbyobject();
			}
			return TRUE;
		}else{
			return FALSE;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('codcatlancto','".$this->getcodsubcatlancto()."'); ";
		return $html;
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getpagrec(){
		return $this->fields["pagrec"];
	}

	function getprevreal(){
		return $this->fields["prevreal"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getparcela(){
		return $this->fields["parcela"];
	}

	function gettotalparcelas(){
		return $this->fields["totalparcelas"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getfavorecido(){
		return $this->fields["favorecido"];
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getidnotadiversa(){
		return $this->fields["idnotadiversa"];
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getvalorparcela($format = FALSE){
		return ($format ? number_format($this->fields["valorparcela"], 2, ",", "") : $this->fields["valorparcela"]);
	}

	function getvalordescto($format = FALSE){
		return ($format ? number_format($this->fields["valordescto"], 2, ",", "") : $this->fields["valordescto"]);
	}

	function getvaloracresc($format = FALSE){
		return ($format ? number_format($this->fields["valoracresc"], 2, ",", "") : $this->fields["valoracresc"]);
	}

	function getvalorpago($format = FALSE){
		return ($format ? number_format($this->fields["valorpago"], 2, ",", "") : $this->fields["valorpago"]);
	}

	function getdtlancto($format = FALSE){
		return ($format ? convert_date($this->fields["dtlancto"], "Y-m-d", "d/m/Y") : $this->fields["dtlancto"]);
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"], "Y-m-d", "d/m/Y") : $this->fields["dtemissao"]);
	}

	function getdtentrada($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrada"], "Y-m-d", "d/m/Y") : $this->fields["dtentrada"]);
	}

	function getdtvencto($format = FALSE){
		return ($format ? convert_date($this->fields["dtvencto"], "Y-m-d", "d/m/Y") : $this->fields["dtvencto"]);
	}

	function getdtliquid($format = FALSE){
		return ($format ? convert_date($this->fields["dtliquid"], "Y-m-d", "d/m/Y") : $this->fields["dtliquid"]);
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getobservacao(){
		return str_replace("\r", "", $this->fields["observacao"]);
	}

	function getcodccusto(){
		return $this->fields["codccusto"];
	}

	function getcodcontacred(){
		return $this->fields["codcontacred"];
	}

	function getcodcontadeb(){
		return $this->fields["codcontadeb"];
	}

	function getcodhistorico(){
		return $this->fields["codhistorico"];
	}

	function getseunumero(){
		return $this->fields["seunumero"];
	}

	function getnossonumero(){
		return $this->fields["nossonumero"];
	}

	function getcodbarras(){
		return $this->fields["codbarras"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getvalorabatimento($format = FALSE){
		return ($format ? number_format($this->fields["valorabatimento"], 2, ",", "") : $this->fields["valorabatimento"]);
	}

	function getvalorliquido($format = FALSE){
		return ($format ? number_format($this->fields["valorliquido"], 2, ",", "") : $this->fields["valorliquido"]);
	}

	function getcodmoeda(){
		return $this->fields["codmoeda"];
	}

	function getdtreconc($format = FALSE){
		return ($format ? convert_date($this->fields["dtreconc"], "Y-m-d", "d/m/Y") : $this->fields["dtreconc"]);
	}

	function getcodlanctopai(){
		return $this->fields["codlanctopai"];
	}

	function getvalorparcelaorig($format = FALSE){
		return ($format ? number_format($this->fields["valorparcelaorig"], 2, ",", "") : $this->fields["valorparcelaorig"]);
	}

	function getnumlote(){
		return $this->fields["numlote"];
	}

	function getcodlanctogru(){
		return $this->fields["codlanctogru"];
	}

	function getseqremessa(){
		return $this->fields["seqremessa"];
	}

	function getprocessogr(){
		return $this->fields["processogr"];
	}

	function getprocessoll(){
		return $this->fields["processoll"];
	}

	function getdocliquidacao(){
		return $this->fields["docliquidacao"];
	}

	function getcodocorrencia(){
		return $this->fields["codocorrencia"];
	}

	function getmotivoocorrencia(){
		return $this->fields["motivoocorrencia"];
	}

	function getvalorjuros($format = FALSE){
		return ($format ? number_format($this->fields["valorjuros"], 2, ",", "") : $this->fields["valorjuros"]);
	}

	function getcodespecieliq(){
		return $this->fields["codespecieliq"];
	}

	function getcodbancocheq(){
		return $this->fields["codbancocheq"];
	}

	function getgerafluxo(){
		return $this->fields["gerafluxo"];
	}

	function getnumeroduplicata(){
		return $this->fields["numeroduplicata"];
	}

	function getdtaceite($format = FALSE){
		return ($format ? convert_date($this->fields["dtaceite"], "Y-m-d", "d/m/Y") : $this->fields["dtaceite"]);
	}

	function getcodsituacao(){
		return $this->fields["codsituacao"];
	}

	function getcodrecibo(){
		return $this->fields["codrecibo"];
	}

	function getcodtipoaceite(){
		return $this->fields["codtipoaceite"];
	}

	function getagenciacedente(){
		return $this->fields["agenciacedente"];
	}

	function getcontacedente(){
		return $this->fields["contacedente"];
	}

	function getanocompetencia(){
		return $this->fields["anocompetencia"];
	}

	function getmescompetencia(){
		return $this->fields["mescompetencia"];
	}

	function getreferencia(){
		return $this->fields["referencia"];
	}

	function gethistorico(){
		return  $this->fields["historico"];
	}

	function getocorrencia(){
		return $this->fields["ocorrencia"];
	}

	function getdtremessa($format = FALSE){
		return ($format ? convert_date($this->fields["dtremessa"], "Y-m-d", "d/m/Y") : $this->fields["dtremessa"]);
	}

	function getcodcheque(){
		return $this->fields["codcheque"];
	}

	function getidnotafrete(){
		return $this->fields["idnotafrete"];
	}

	function gethoralog(){
		return $this->fields["horalog"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function gettid(){
		return $this->fields["tid"];
	}

	function getvalorjurosprorrog($format = FALSE){
		return ($format ? number_format($this->fields["valorjurosprorrog"], 2, ",", "") : $this->fields["valorjurosprorrog"]);
	}

	function getdtprorrog($format = FALSE){
		return ($format ? convert_date($this->fields["dtprorrog"], "Y-m-d", "d/m/Y") : $this->fields["dtprorrog"]);
	}

	function getcodfinaliz(){
		return $this->fields["codfinaliz"];
	}

	function getdtdupemitida($format = FALSE){
		return ($format ? convert_date($this->fields["dtdupemitida"], "Y-m-d", "d/m/Y") : $this->fields["dtdupemitida"]);
	}

	function getdupemitida(){
		return $this->fields["dupemitida"];
	}

	function getsincpdv(){
		return $this->fields["sincpdv"];
	}

	function getidofx(){
		return $this->fields["idofx"];
	}

	function getcnpjboleto(){
		return $this->fields["cnpjboleto"];
	}

	function gettipocriacao(){
		return $this->fields["tipocriacao"];
	}

	function gettipoliquidacao(){
		return $this->fields["tipoliquidacao"];
	}

	function getnumerotid(){
		return $this->fields["numerotid"];
	}

	function getdtcreditocc($format = FALSE){
		return ($format ? convert_date($this->fields["dtcreditocc"], "Y-m-d", "d/m/Y") : $this->fields["dtcreditocc"]);
	}

	function getstatusliquidacao(){
		return $this->fields["statusliquidacao"];
	}

	function getrefavisobancario(){
		return $this->fields["refavisobancario"];
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setpagrec($value){
		$this->fields["pagrec"] = value_string($value, 1);
	}

	function setprevreal($value){
		$this->fields["prevreal"] = value_string($value, 1);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setparcela($value){
		$this->fields["parcela"] = value_numeric($value);
	}

	function settotalparcelas($value){
		$this->fields["totalparcelas"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value, 1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setfavorecido($value){
		$this->fields["favorecido"] = value_string($value, 40);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setvalorparcela($value){
		$this->fields["valorparcela"] = value_numeric($value);
	}

	function setvalordescto($value){
		$this->fields["valordescto"] = value_numeric($value);
	}

	function setvaloracresc($value){
		$this->fields["valoracresc"] = value_numeric($value);
	}

	function setvalorpago($value){
		$this->fields["valorpago"] = value_numeric($value);
	}

	function setdtlancto($value){
		$this->fields["dtlancto"] = value_date($value);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setdtentrada($value){
		$this->fields["dtentrada"] = value_date($value);
	}

	function setdtvencto($value){
		$this->fields["dtvencto"] = value_date($value);
	}

	function setdtliquid($value){
		$this->fields["dtliquid"] = value_date($value);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setcodccusto($value){
		$this->fields["codccusto"] = value_numeric($value);
	}

	function setcodcontacred($value){
		$this->fields["codcontacred"] = value_numeric($value);
	}

	function setcodcontadeb($value){
		$this->fields["codcontadeb"] = value_numeric($value);
	}

	function setcodhistorico($value){
		$this->fields["codhistorico"] = value_numeric($value);
	}

	function setseunumero($value){
		$this->fields["seunumero"] = value_string($value, 20);
	}

	function setnossonumero($value){
		$this->fields["nossonumero"] = value_string($value, 20);
	}

	function setcodbarras($value){
		$this->fields["codbarras"] = value_string($value, 60);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value, 3);
	}

	function setvalorabatimento($value){
		$this->fields["valorabatimento"] = value_numeric($value);
	}

	function setvalorliquido($value){
		$this->fields["valorliquido"] = value_numeric($value);
	}

	function setcodmoeda($value){
		$this->fields["codmoeda"] = value_numeric($value);
	}

	function setdtreconc($value){
		$this->fields["dtreconc"] = value_date($value);
	}

	function setcodlanctopai($value){
		$this->fields["codlanctopai"] = value_numeric($value);
	}

	function setvalorparcelaorig($value){
		$this->fields["valorparcelaorig"] = value_numeric($value);
	}

	function setnumlote($value){
		$this->fields["numlote"] = value_numeric($value);
	}

	function setcodlanctogru($value){
		$this->fields["codlanctogru"] = value_numeric($value);
	}

	function setseqremessa($value){
		$this->fields["seqremessa"] = value_numeric($value);
	}

	function setprocessogr($value){
		$this->fields["processogr"] = value_numeric($value);
	}

	function setprocessoll($value){
		$this->fields["processoll"] = value_numeric($value);
	}

	function setdocliquidacao($value){
		$this->fields["docliquidacao"] = value_string($value);
	}

	function setcodocorrencia($value){
		$this->fields["codocorrencia"] = value_string($value, 2);
	}

	function setmotivoocorrencia($value){
		$this->fields["motivoocorrencia"] = value_string(utf8_to_win1252($value));
	}

	function setvalorjuros($value){
		$this->fields["valorjuros"] = value_numeric($value);
	}

	function setcodespecieliq($value){
		$this->fields["codespecieliq"] = value_numeric($value);
	}

	function setcodbancocheq($value){
		$this->fields["codbancocheq"] = value_numeric($value);
	}

	function setgerafluxo($value){
		$this->fields["gerafluxo"] = value_string($value, 1);
	}

	function setnumeroduplicata($value){
		$this->fields["numeroduplicata"] = value_string($value, 30);
	}

	function setdtaceite($value){
		$this->fields["dtaceite"] = value_date($value);
	}

	function setcodsituacao($value){
		$this->fields["codsituacao"] = value_numeric($value);
	}

	function setcodrecibo($value){
		$this->fields["codrecibo"] = value_numeric($value);
	}

	function setcodtipoaceite($value){
		$this->fields["codtipoaceite"] = value_numeric($value);
	}

	function setagenciacedente($value){
		$this->fields["agenciacedente"] = value_string($value, 20);
	}

	function setcontacedente($value){
		$this->fields["contacedente"] = value_string($value, 20);
	}

	function setanocompetencia($value){
		$this->fields["anocompetencia"] = value_numeric($value);
	}

	function setmescompetencia($value){
		$this->fields["mescompetencia"] = value_numeric($value);
	}

	function setreferencia($value){
		$this->fields["referencia"] = value_string($value, 35);
	}

	function sethistorico($value){
		$this->fields["historico"] = value_string($value);
	}

	function setocorrencia($value){
		$this->fields["ocorrencia"] = value_string($value, 200);
	}

	function setdtremessa($value){
		$this->fields["dtremessa"] = value_date($value);
	}

	function setcodcheque($value){
		$this->fields["codcheque"] = value_numeric($value);
	}

	function setidnotafrete($value){
		$this->fields["idnotafrete"] = value_numeric($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setidnotadiversa($value){
		$this->fields["idnotadiversa"] = value_numeric($value);
	}

	function settid($value){
		$this->fields["tid"] = value_string($value, 20);
	}

	function setvalorjurosprorrog($value){
		$this->fields["valorjurosprorrog"] = value_numeric($value);
	}

	function setdtprorrog($value){
		$this->fields["dtprorrog"] = value_date($value);
	}

	function setcodfinaliz($value){
		$this->fields["codfinaliz"] = value_string($value, 5);
	}

	function setdtdupemitida($value){
		$this->fields["dtdupemitida"] = value_date($value);
	}

	function setdupemitida($value){
		$this->fields["dupemitida"] = value_string($value, 1);
	}

	function setsincpdv($value){
		$this->fields["sincpdv"] = value_numeric($value);
	}

	function setidofx($value){
		$this->fields["idofx"] = value_string($value, 16);
	}

	function setcnpjboleto($value){
		$this->fields["cnpjboleto"] = value_string($value, 20);
	}

	function settipocriacao($value){
		$this->fields["tipocriacao"] = value_string($value, 2);
	}

	function settipoliquidacao($value){
		$this->fields["tipoliquidacao"] = value_string($value, 2);
	}

	function setnumerotid($value){
		$this->fields["numerotid"] = value_string($value, 20);
	}

	function setdtcreditocc($value){
		$this->fields["dtcreditocc"] = value_date($value);
	}

	function setstatusliquidacao($value){
		$this->fields["statusliquidacao"] = value_string($value, 1);
	}

	function setrefavisobancario($value){
		$this->fields["refavisobancario"] = value_string($value, 7);
	}
}