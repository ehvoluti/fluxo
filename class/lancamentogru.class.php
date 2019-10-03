<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LancamentoGru extends Cadastro{
	function __construct($codlanctogru = NULL){
		parent::__construct();
		$this->table = "lancamentogru";
		$this->primarykey = "codlanctogru";
		$this->setcodlanctogru($codlanctogru);
		if($this->getcodlanctogru() != NULL){
			$this->searchbyobject();
		}
	}

	function delete(){
		$this->connect();
		if($this->exists()){
			$lancamento = objectbytable("lancamento",NULL,$this->con);
			$lancamento->setcodlanctogru($this->getcodlanctogru());
			$search = $lancamento->searchbyobject();
			if($search !== FALSE){
				if(!is_array($search)){
					$search = array($search);
				}
				foreach($search as $key){
					$lancamento = objectbytable("lancamento",$key,$this->con);
					if($lancamento->getstatus() != "A"){
						$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel excluir o lan&ccedil;amento, pois existe parcela liquidada.<br><a onclick=\"$.messageBox('close'); openProgram('Lancto','codlancto=".$lancamento->getcodlancto()."')\">Clique aqui</a> para abrir a parcela.";
						return FALSE;
					}
				}
			}
		}
		return parent::delete();
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('codcatlancto','".$this->getcodsubcatlancto()."'); ";
		return $html;
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getcodlanctogru(){
		return $this->fields["codlanctogru"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getpagrec(){
		return $this->fields["pagrec"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
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

	function getvalorabatimento($format = FALSE){
		return ($format ? number_format($this->fields["valorabatimento"],2,",","") : $this->fields["valorabatimento"]);
	}

    function getvalorbruto($format = FALSE){
		return ($format ? number_format($this->fields["valorbruto"],2,",","") : $this->fields["valorbruto"]);
	}

    function getvalorliquido($format = FALSE){
		return ($format ? number_format($this->fields["valorliquido"],2,",","") : $this->fields["valorliquido"]);
	}

    function getvalordesconto($format = FALSE){
		return ($format ? number_format($this->fields["valordesconto"],2,",","") : $this->fields["valordesconto"]);
	}

    function getvaloracrescimo($format = FALSE){
		return ($format ? number_format($this->fields["valoracrescimo"],2,",","") : $this->fields["valoracrescimo"]);
	}

    function getvalorjuros($format = FALSE){
		return ($format ? number_format($this->fields["valorjuros"],2,",","") : $this->fields["valorjuros"]);
	}

	function getdtlancto($format = FALSE){
		return ($format ? convert_date($this->fields["dtlancto"],"Y-m-d","d/m/Y") : $this->fields["dtlancto"]);
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"],"Y-m-d","d/m/Y") : $this->fields["dtemissao"]);
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
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

	function getcodmoeda(){
		return $this->fields["codmoeda"];
	}

	function getfavorecido(){
		return $this->fields["favorecido"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
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

    function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setcodlanctogru($value){
		$this->fields["codlanctogru"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setpagrec($value){
		$this->fields["pagrec"] = value_string($value,1);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value,1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
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

	function setvalorabatimento($value){
		$this->fields["valorabatimento"] = value_numeric($value);
	}

    function setvalorbruto($value){
		$this->fields["valorbruto"] = value_numeric($value);
	}

    function setvalorliquido($value){
		$this->fields["valorliquido"] = value_numeric($value);
	}

    function setvalordesconto($value){
		$this->fields["valordesconto"] = value_numeric($value);
	}

    function setvaloracrescimo($value){
		$this->fields["valoracrescimo"] = value_numeric($value);
	}

    function setvalorjuros($value){
		$this->fields["valorjuros"] = value_numeric($value);
	}

	function setdtlancto($value){
		$this->fields["dtlancto"] = value_date($value);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,500);
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
		$this->fields["seunumero"] = value_string($value,20);
	}

	function setnossonumero($value){
		$this->fields["nossonumero"] = value_string($value,20);
	}

	function setcodmoeda($value){
		$this->fields["codmoeda"] = value_numeric($value);
	}

	function setfavorecido($value){
		$this->fields["favorecido"] = value_string($value,40);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value,3);
	}

    function setanocompetencia($value){
        $this->fields["anocompetencia"] = value_numeric($value);
    }

    function setmescompetencia($value){
        $this->fields["mescompetencia"] = value_numeric($value);
    }

    function setreferencia($value){
        $this->fields["referencia"] = value_string($value,35);
    }

    function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>