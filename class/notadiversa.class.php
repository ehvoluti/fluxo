<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_once("../class/temporary.class.php");

class NotaDiversa extends Cadastro{

	public $itnotadiversa;
	protected $flag_itnotadiversa = false;

	function __construct($idnotadiversa = NULL){
		parent::__construct();
		$this->table = "notadiversa";
		$this->primarykey = array("idnotadiversa");
		$this->setidnotadiversa($idnotadiversa);
		if(!is_null($this->getidnotadiversa())){
			$this->searchbyobject();
		}
	}

	function flag_itnotadiversa($value){
		if(is_bool($value)){
			$this->flag_itnotadiversa = $value;
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return[0])){
			if($this->flag_itnotadiversa){
				$this->itnotadiversa = array();
				$itnotadiversa = objectbytable("itnotadiversa", NULL, $this->con);
				$itnotadiversa->setidnotadiversa($this->getidnotadiversa());
				$this->itnotadiversa = object_array($itnotadiversa);

				$objectsession = new ObjectSession($this->con, "itnotadiversa", "notadiversa_itnotadiversa");
				$objectsession->clear();
				$objectsession->addobject($this->itnotadiversa);
				$objectsession->save();
			}
		}
		return $return;
	}

	function save(){
		$objectsession = new ObjectSession($this->con, "itnotadiversa", "notadiversa_itnotadiversa");
		$this->itnotadiversa = $objectsession->getobject();

		$this->con->start_transaction();

		// Busca os itens antigos e verifica se deve ser apagado
		if($this->getidnotadiversa() > 0){
			$itnotadiversa = objectbytable("itnotadiversa", NULL, $this->con);
			$itnotadiversa->setidnotadiversa($this->getidnotadiversa());

			$arr_itnotadiversa = object_array($itnotadiversa);
			foreach($arr_itnotadiversa as $itnotadiversa_db){
				$found = FALSE;
				foreach($this->itnotadiversa as $itnotadiversa_ob){
					if($itnotadiversa_db->getnatoperacao() === $itnotadiversa_ob->getnatoperacao()){
						$found = TRUE;
						break;
					}
				}
				if(!$found){
					if(!$itnotadiversa_db->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}

		$objectsession = new ObjectSession($this->con, "itnotadiversa", "notadiversa_itnotadiversa");
		$this->itnotadiversa = $objectsession->getobject();

		if(!parent::save()){
			$this->con->rollback();
			return FALSE;
		}

		foreach($this->itnotadiversa as $itnotadiversa){
			$itnotadiversa->setidnotadiversa($this->getidnotadiversa());
			if(!$itnotadiversa->save()){
				$this->con->rollback();
				return FALSE;
			}
		}
		$objectsession->clear();
		$objectsession->save();
		$this->con->commit();
		return true;
	}

	function getidnotadiversa(){
		return $this->fields["idnotadiversa"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"], "Y-m-d", "d/m/Y") : $this->fields["dtemissao"]);
	}

	function getdtvencto($format = FALSE){
		return ($format ? convert_date($this->fields["dtvencto"], "Y-m-d", "d/m/Y") : $this->fields["dtvencto"]);
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"], 2, ",", "") : $this->fields["totalliquido"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"], 2, ",", "") : $this->fields["totalbruto"]);
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"], 2, ",", "") : $this->fields["aliqicms"]);
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"], 2, ",", "") : $this->fields["aliqpis"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"], 2, ",", "") : $this->fields["aliqcofins"]);
	}

	function getbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["baseicms"], 2, ",", "") : $this->fields["baseicms"]);
	}

	function getbasepis($format = FALSE){
		return ($format ? number_format($this->fields["basepis"], 2, ",", "") : $this->fields["basepis"]);
	}

	function getbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["basecofins"], 2, ",", "") : $this->fields["basecofins"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"], 2, ",", "") : $this->fields["totalicms"]);
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"], 2, ",", "") : $this->fields["totalpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"], 2, ",", "") : $this->fields["totalcofins"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getdtdigitacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtdigitacao"], "Y-m-d", "d/m/Y") : $this->fields["dtdigitacao"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"], 2, ",", "") : $this->fields["totalicmssubst"]);
	}

	function getbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["baseicmssubst"], 2, ",", "") : $this->fields["baseicmssubst"]);
	}

	function getaliqicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmssubst"], 2, ",", "") : $this->fields["aliqicmssubst"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"], 2, ",", "") : $this->fields["totalipi"]);
	}

	function getbaseipi($format = FALSE){
		return ($format ? number_format($this->fields["baseipi"], 2, ",", "") : $this->fields["baseipi"]);
	}

	function getaliqipi($format = FALSE){
		return ($format ? number_format($this->fields["aliqipi"], 2, ",", "") : $this->fields["aliqipi"]);
	}

	function gettotalbaseoutras($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseoutras"], 2, ",", "") : $this->fields["totalbaseoutras"]);
	}

	function getcodcstpiscofins(){
		return $this->fields["codcstpiscofins"];
	}

	function getnumnotafisfinal(){
		return $this->fields["numnotafisfinal"];
	}

	function gettipodocumentofiscal(){
		return $this->fields["tipodocumentofiscal"];
	}
	
	function getpagrec(){
		return $this->fields["pagrec"];
	}

	function settotalbaseoutras($value){
		$this->fields["totalbaseoutras"] = value_numeric($value);
	}

	function setidnotadiversa($value){
		$this->fields["idnotadiversa"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value, 1);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value, 3);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setdtvencto($value){
		$this->fields["dtvencto"] = value_date($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
	}

	function setbaseicms($value){
		$this->fields["baseicms"] = value_numeric($value);
	}

	function setbasepis($value){
		$this->fields["basepis"] = value_numeric($value);
	}

	function setbasecofins($value){
		$this->fields["basecofins"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function settotalpis($value){
		$this->fields["totalpis"] = value_numeric($value);
	}

	function settotalcofins($value){
		$this->fields["totalcofins"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setdtdigitacao($value){
		$this->fields["dtdigitacao"] = value_date($value);
	}

	function setaliqicmssubst($value){
		$this->fields["aliqicmssubst"] = value_numeric($value);
	}

	function setbaseicmssubst($value){
		$this->fields["baseicmssubst"] = value_numeric($value);
	}

	function settotalicmssubst($value){
		$this->fields["totalicmssubst"] = value_numeric($value);
	}

	function setaliqipi($value){
		$this->fields["aliqipi"] = value_numeric($value);
	}

	function setbaseipi($value){
		$this->fields["baseipi"] = value_numeric($value);
	}

	function settotalipi($value){
		$this->fields["totalipi"] = value_numeric($value);
	}

	function setcodcstpiscofins($value){
		$this->fields["codcstpiscofins"] = value_string($value, 2);
	}

	function setnumnotafisfinal($value){
		$this->fields["numnotafisfinal"] = value_numeric($value);
	}

	function settipodocumentofiscal($value){
		$this->fields["tipodocumentofiscal"] = value_string($value, 2);
	}
	
	function setpagrec($value){
		$this->fields["pagrec"] = value_string($value, 1);
	}

}