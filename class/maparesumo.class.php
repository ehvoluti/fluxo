<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class MapaResumo extends Cadastro{
	public $maparesumoimposto;
	public $cupom;
	protected $flag_maparesumoimposto = FALSE;

	function __construct($codmaparesumo = NULL){
		parent::__construct();
		$this->table = "maparesumo";
		$this->primarykey = array("codmaparesumo");
		$this->setcodmaparesumo($codmaparesumo);
		if(!is_null($this->getcodmaparesumo())){
			$this->searchbyobject();
		}
	}

	function flag_maparesumoimposto($value){
		if(is_bool($value)){
			$this->flag_maparesumoimposto = $value;
		}
	}

	function save(){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save()){
			if($this->flag_maparesumoimposto){
				$totalliquido = 0;
				foreach($this->maparesumoimposto as $maparesumoimposto){
					$totalliquido += $maparesumoimposto->gettotalliquido();
				}
				if((string) $this->gettotalliquido() != (string) $totalliquido){
					$this->con->rollback();
					$_SESSION["ERROR"] = "A soma das bases informadas &eacute; diferente do total l&iacute;quido do movimento.<br>Data do movimento: ".$this->getdtmovto(TRUE)."<br>Caixa: ".$this->getcaixa()."<br>Total do mapa resumo: ".number_format($this->gettotalliquido(),2,",",".")."<br>Total por tributa&ccedil;&atilde;o: ".number_format($totalliquido,2,",",".");
					return FALSE;
				}

				$maparesumoimposto = objectbytable("maparesumoimposto",NULL,$this->con);
				$maparesumoimposto->setcodmaparesumo($this->getcodmaparesumo());
				$search = $maparesumoimposto->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search[0])){
						$search = array($search);
					}
					foreach($search as $key){
						$maparesumoimposto = objectbytable("maparesumoimposto",$key,$this->con);
						if(!$maparesumoimposto->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
				foreach($this->maparesumoimposto as $maparesumoimposto){
					$maparesumoimposto->setcodmaparesumo($this->getcodmaparesumo());
					if(!$maparesumoimposto->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && $this->flag_maparesumoimposto){
			$this->maparesumoimposto = array();
			$maparesumoimposto = objectbytable("maparesumoimposto",NULL,$this->con);
			$maparesumoimposto->setcodmaparesumo($this->getcodmaparesumo());
			$search = $maparesumoimposto->searchbyobject();
			if($search !== FALSE){
				if(!is_array($search[0])){
					$search = array($search);
				}
				foreach($search as $key){
					$this->maparesumoimposto[] = objectbytable("maparesumoimposto",$key,$this->con);
				}
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$this->maparesumoimposto = array();
		$temporary = new Temporary("maparesumo_maparesumoimposto",FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$maparesumoimposto = objectbytable("maparesumoimposto",NULL,$this->con);
			$maparesumoimposto->setcodmaparesumo($this->getcodmaparesumo());
			$maparesumoimposto->settptribicms($temporary->getvalue($i,"tptribicms"));
			$maparesumoimposto->setaliqicms($temporary->getvalue($i,"aliqicms"));
			$maparesumoimposto->settotalliquido($temporary->getvalue($i,"totalliquido"));
			$maparesumoimposto->settotalicms($temporary->getvalue($i,"totalicms"));
			$this->maparesumoimposto[] = $maparesumoimposto;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('codestabelec','".$this->getcodecf()."'); "; // Filtra o ecf a partir da loja
		$temporary = new Temporary("maparesumo_maparesumoimposto",TRUE);
		$temporary->setcolumns(array("tptribicms","aliqicms","totalliquido","totalicms"));
		foreach($this->maparesumoimposto as $maparesumoimposto){
			$temporary->append();
			$temporary->setvalue("last","tptribicms",$maparesumoimposto->gettptribicms());
			$temporary->setvalue("last","aliqicms",number_format($maparesumoimposto->getaliqicms(),2,",",""));
			$temporary->setvalue("last","totalliquido",number_format($maparesumoimposto->gettotalliquido(),2,",",""));
			$temporary->setvalue("last","totalicms",number_format($maparesumoimposto->gettotalicms(),2,",",""));
		}
		$temporary->save();
		return $html;
	}

	function getcodmaparesumo(){
		return $this->fields["codmaparesumo"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcaixa(){
		return $this->fields["caixa"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function getcodecf(){
		return $this->fields["codecf"];
	}

	function getnummaparesumo(){
		return $this->fields["nummaparesumo"];
	}

	function getnumeroreducoes(){
		return $this->fields["numeroreducoes"];
	}

	function getoperacaoini(){
		return $this->fields["operacaoini"];
	}

	function getoperacaofim(){
		return $this->fields["operacaofim"];
	}

	function getoperacaonaofiscini(){
		return $this->fields["operacaonaofiscini"];
	}

	function getoperacaonaofiscfim(){
		return $this->fields["operacaonaofiscfim"];
	}

	function getreinicioini(){
		return $this->fields["reinicioini"];
	}

	function getreiniciofim(){
		return $this->fields["reiniciofim"];
	}

	function getcuponsnaofiscemit(){
		return $this->fields["cuponsnaofiscemit"];
	}

	function getcuponsfiscemit(){
		return $this->fields["cuponsfiscemit"];
	}

	function getitenscancelados(){
		return $this->fields["itenscancelados"];
	}

	function getcuponscancelados(){
		return $this->fields["cuponscancelados"];
	}

	function getnumerodescontos(){
		return $this->fields["numerodescontos"];
	}

	function getnumeroacrescimos(){
		return $this->fields["numeroacrescimos"];
	}

	function getgtinicial($format = FALSE){
		return ($format ? number_format($this->fields["gtinicial"],2,",","") : $this->fields["gtinicial"]);
	}

	function getgtfinal($format = FALSE){
		return ($format ? number_format($this->fields["gtfinal"],2,",","") : $this->fields["gtfinal"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"],2,",","") : $this->fields["totalbruto"]);
	}

	function gettotalcupomcancelado($format = FALSE){
		return ($format ? number_format($this->fields["totalcupomcancelado"],2,",","") : $this->fields["totalcupomcancelado"]);
	}

	function gettotalitemcancelado($format = FALSE){
		return ($format ? number_format($this->fields["totalitemcancelado"],2,",","") : $this->fields["totalitemcancelado"]);
	}

	function gettotaldescontocupom($format = FALSE){
		return ($format ? number_format($this->fields["totaldescontocupom"],2,",","") : $this->fields["totaldescontocupom"]);
	}

	function gettotaldescontoitem($format = FALSE){
		return ($format ? number_format($this->fields["totaldescontoitem"],2,",","") : $this->fields["totaldescontoitem"]);
	}

	function gettotalacrescimocupom($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimocupom"],2,",","") : $this->fields["totalacrescimocupom"]);
	}

	function gettotalacrescimoitem($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimoitem"],2,",","") : $this->fields["totalacrescimoitem"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function getnumseriefabecf(){
		return $this->fields["numseriefabecf"];
	}

	function getcuponsnfisccanc(){
		return $this->fields["cuponsnfisccanc"];
	}

	function getnumeroecf(){
		return $this->fields["numeroecf"];
	}

	function setcodmaparesumo($value){
		$this->fields["codmaparesumo"] = value_numeric($value);
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

	function setcodecf($value){
		$this->fields["codecf"] = value_numeric($value);
	}

	function setnummaparesumo($value){
		$this->fields["nummaparesumo"] = value_numeric($value);
	}

	function setnumeroreducoes($value){
		$this->fields["numeroreducoes"] = value_numeric($value);
	}

	function setoperacaoini($value){
		$this->fields["operacaoini"] = value_numeric($value);
	}

	function setoperacaofim($value){
		$this->fields["operacaofim"] = value_numeric($value);
	}

	function setoperacaonaofiscini($value){
		$this->fields["operacaonaofiscini"] = value_numeric($value);
	}

	function setoperacaonaofiscfim($value){
		$this->fields["operacaonaofiscfim"] = value_numeric($value);
	}

	function setreinicioini($value){
		$this->fields["reinicioini"] = value_numeric($value);
	}

	function setreiniciofim($value){
		$this->fields["reiniciofim"] = value_numeric($value);
	}

	function setcuponsnaofiscemit($value){
		$this->fields["cuponsnaofiscemit"] = value_numeric($value);
	}

	function setcuponsfiscemit($value){
		$this->fields["cuponsfiscemit"] = value_numeric($value);
	}

	function setitenscancelados($value){
		$this->fields["itenscancelados"] = value_numeric($value);
	}

	function setcuponscancelados($value){
		$this->fields["cuponscancelados"] = value_numeric($value);
	}

	function setnumerodescontos($value){
		$this->fields["numerodescontos"] = value_numeric($value);
	}

	function setnumeroacrescimos($value){
		$this->fields["numeroacrescimos"] = value_numeric($value);
	}

	function setgtinicial($value){
		$this->fields["gtinicial"] = value_numeric($value);
	}

	function setgtfinal($value){
		$this->fields["gtfinal"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function settotalcupomcancelado($value){
		$this->fields["totalcupomcancelado"] = value_numeric($value);
	}

	function settotalitemcancelado($value){
		$this->fields["totalitemcancelado"] = value_numeric($value);
	}

	function settotaldescontocupom($value){
		$this->fields["totaldescontocupom"] = value_numeric($value);
	}

	function settotaldescontoitem($value){
		$this->fields["totaldescontoitem"] = value_numeric($value);
	}

	function settotalacrescimocupom($value){
		$this->fields["totalacrescimocupom"] = value_numeric($value);
	}

	function settotalacrescimoitem($value){
		$this->fields["totalacrescimoitem"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setnumseriefabecf($value){
		$this->fields["numseriefabecf"] = value_string($value,20);
	}

	function setcuponsnfisccanc($value){
		$this->fields["cuponsnfisccanc"] = value_numeric($value);
	}

	function setnumeroecf($value){
		$this->fields["numeroecf"] = value_numeric($value);
	}
}
?>