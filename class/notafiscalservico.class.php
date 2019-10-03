<?php
require_file("class/cadastro.class.php");

class NotaFiscalServico extends Cadastro{
	public $itnotafiscalservico;
	protected $flag_itnotafiscalservico = FALSE;

	function __construct($idnotafiscalservico = NULL, $codestabelec = NULL, $codparceiro = NULL, $numnotafis = NULL, $serie = NULL){
		parent::__construct();
		$this->table = "notafiscalservico";
		$this->primarykey = array("idnotafiscalservico");
		$this->setidnotafiscalservico($idnotafiscalservico);
		$this->setcodestabelec($codestabelec);
		$this->setcodparceiro($codparceiro);
		$this->setnumnotafis($numnotafis);
		$this->setserie($serie);
		if(!is_null($this->getidnotafiscalservico()) && !is_null($this->getcodestabelec()) && !is_null($this->getcodparceiro()) && !is_null($this->getnumnotafis()) && !is_null($this->getserie())){
			$this->searchbyobject();
		}
	}

	function flag_itnotafiscalservico($value){
		if(is_bool($value)){
			$this->flag_itnotafiscalservico = $value;
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return[0])){
			if($this->flag_itnotafiscalservico){
				$this->itnotafiscalservico = array();
				$itnotafiscalservico = objectbytable("itnotafiscalservico", NULL, $this->con);
				$itnotafiscalservico->setidnotafiscalservico($this->getidnotafiscalservico());
				$this->itnotafiscalservico = object_array($itnotafiscalservico);

				$objectsession = new ObjectSession($this->con, "itnotafiscalservico", "notafiscalservico_itnotafiscalservico");
				$objectsession->clear();
				$objectsession->addobject($this->itnotafiscalservico);
				$objectsession->save();
			}
		}
		return $return;
	}

	function save(){
		$objectSession = new ObjectSession($this->con, "itnotafiscalservico","notafiscalservico_itnotafiscalservico");
		$this->itnotafiscalservico = $objectSession->getobject();

		$this->con->start_transaction();

		if($this->getidnotafiscalservico() > 0){
			$itnotafiscalservico = objectbytable("itnotafiscalservico", null, $this->con);
			$itnotafiscalservico->setidnotafiscalservico($this->getidnotafiscalservico());

			$arr_itnotafiscalservico = object_array($itnotafiscalservico);
			foreach($arr_itnotafiscalservico as $itnotafiscalservico_db){
				$found = FALSE;
				foreach($this->itnotafiscalservico as $itnotafiscalservico_ob){
					if($itnotafiscalservico_db->getcodproduto() == $itnotafiscalservico_ob->getcodproduto()){
						$found = TRUE;
						break;
					}
				}
				if(!$found){
					if(!$itnotafiscalservico_db->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}

		$objectSession = new ObjectSession($this->con, "itnotafiscalservico","notafiscalservico_itnotafiscalservico");
		$this->itnotafiscalservico = $objectSession->getobject();

		if(!parent::save()){
			$this->con->rollback();
			return FALSE;
		}

		foreach($this->itnotafiscalservico as $itnotafiscalservico){
			$itnotafiscalservico->setidnotafiscalservico($this->getidnotafiscalservico());
			if(!$itnotafiscalservico->save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		$objectSession->clear();
		$objectSession->save();
		$this->con->commit();
		return true;
	}

	function getidnotafiscalservico(){
		return $this->fields["idnotafiscalservico"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getsubserie(){
		return $this->fields["subserie"];
	}

	function getindicadoroperacao(){
		return $this->fields["indicadoroperacao"];
	}

	function getindicadoremitente(){
		return $this->fields["indicadoremitente"];
	}
	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodigosituacao(){
		return $this->fields["codigosituacao"];
	}

	function getchavenfse(){
		return $this->fields["chavenfse"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"],"Y-m-d","d/m/Y") : $this->fields["dtemissao"]);
	}

	function getdtentrega($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrega"],"Y-m-d","d/m/Y") : $this->fields["dtentrega"]);
	}

	function getvalorliquido($format = FALSE){
		return ($format ? number_format($this->fields["valorliquido"],2,",","") : $this->fields["valorliquido"]);
	}

	function getindicadorpagamento(){
		return $this->fields["indicadorpagamento"];
	}

	function getvalordesconto($format = FALSE){
		return ($format ? number_format($this->fields["valordesconto"],2,",","") : $this->fields["valordesconto"]);
	}

	function getbasecalculopis($format = FALSE){
		return ($format ? number_format($this->fields["basecalculopis"],2,",","") : $this->fields["basecalculopis"]);
	}

	function getvalorpis($format = FALSE){
		return ($format ? number_format($this->fields["valorpis"],2,",","") : $this->fields["valorpis"]);
	}

	function getbasecalculocofins($format = FALSE){
		return ($format ? number_format($this->fields["basecalculocofins"],2,",","") : $this->fields["basecalculocofins"]);
	}

	function getvalorcofins($format = FALSE){
		return ($format ? number_format($this->fields["valorcofins"],2,",","") : $this->fields["valorcofins"]);
	}

	function getvalorpisretido($format = FALSE){
		return ($format ? number_format($this->fields["valorpisretido"],2,",","") : $this->fields["valorpisretido"]);
	}

	function getvalorcofinsretido($format = FALSE){
		return ($format ? number_format($this->fields["valorcofinsretido"],2,",","") : $this->fields["valorcofinsretido"]);
	}

	function getvaloriss($format = FALSE){
		return ($format ? number_format($this->fields["valoriss"],2,",","") : $this->fields["valoriss"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getgerafiscal(){
		return $this->fields["gerafiscal"];
	}

	function setidnotafiscalservico($value){
		$this->fields["idnotafiscalservico"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value,3);
	}

	function setsubserie($value){
		$this->fields["subserie"] = value_string($value,20);
	}

	function setindicadoroperacao($value){
		$this->fields["indicadoroperacao"] = value_string($value,1);
	}

	function setindicadoremitente($value){
		$this->fields["indicadoremitente"] = value_string($value,1);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value,1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodigosituacao($value){
		$this->fields["codigosituacao"] = value_string($value,2);
	}

	function setchavenfse($value){
		$this->fields["chavenfse"] = value_string($value,44);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setvalorliquido($value){
		$this->fields["valorliquido"] = value_numeric($value);
	}

	function setindicadorpagamento($value){
		$this->fields["indicadorpagamento"] = value_string($value,1);
	}

	function setvalordesconto($value){
		$this->fields["valordesconto"] = value_numeric($value);
	}

	function setbasecalculopis($value){
		$this->fields["basecalculopis"] = value_numeric($value);
	}

	function setvalorpis($value){
		$this->fields["valorpis"] = value_numeric($value);
	}

	function setbasecalculocofins($value){
		$this->fields["basecalculocofins"] = value_numeric($value);
	}

	function setvalorcofins($value){
		$this->fields["valorcofins"] = value_numeric($value);
	}

	function setvalorpisretido($value){
		$this->fields["valorpisretido"] = value_numeric($value);
	}

	function setvalorcofinsretido($value){
		$this->fields["valorcofinsretido"] = value_numeric($value);
	}

	function setvaloriss($value){
		$this->fields["valoriss"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setgerafiscal($value){
		$this->fields["gerafiscal"] = value_string($value,1);
	}
}
?>