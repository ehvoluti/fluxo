<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NotaFrete extends Cadastro{
    private $arr_notafiscal = array();
    private $flag_notafiscal = FALSE;

	function __construct($idnotafrete = NULL){
		parent::__construct();
		$this->table = "notafrete";
		$this->primarykey = array("idnotafrete");
		$this->setidnotafrete($idnotafrete);
		if(!is_null($this->getidnotafrete())){
			$this->searchbyobject();
		}
	}

    function flag_notafiscal($value){
		if(is_bool($value)){
			$this->flag_notafiscal = $value;
		}
	}

	function save(){
		$this->connect();
		$this->con->start_transaction();
		$object = objectbytable("notafrete",$this->getidnotafrete(),$this->con);
		if(parent::save($object)){
			// Ligacao das notas fiscais
			if($this->flag_notafiscal){
                $notafiscal = objectbytable("notafiscal",NULL,$this->con);
                $notafiscal->setidnotafrete($this->getidnotafrete());
                $arr_notafiscal = object_array($notafiscal);
                foreach($arr_notafiscal as $notafiscal){
                    $notafiscal->setidnotafrete(NULL);
                    $notafiscal->settotalnotafrete(0);
                    if(!$notafiscal->save()){
                        $this->con->rollback();
                        return FALSE;
                    }
                }
                foreach($this->arr_notafiscal as $inf_notafiscal){
                    $notafiscal = objectbytable("notafiscal",$inf_notafiscal["idnotafiscal"],$this->con);
                    $notafiscal->setidnotafrete($this->getidnotafrete());
                    $notafiscal->settotalnotafrete($inf_notafiscal["totalnotafrete"]);
                    if(!$notafiscal->save()){
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

    function searchatdatabase($query,$fetchAll = FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && !$fetchAll){
            // Ligacao das notas fiscais
            if($this->flag_notafiscal){
                $this->arr_notafiscal = array();
                $notafiscal = objectbytable("notafiscal",NULL,$this->con);
                $notafiscal->setidnotafrete($this->getidnotafrete());
                $arr_notafiscal = object_array($notafiscal);
                foreach($arr_notafiscal as $notafiscal){
                    $this->arr_notafiscal[] = array(
                        "idnotafiscal" => $notafiscal->getidnotafiscal(),
                        "totalnotafrete" => $notafiscal->gettotalnotafrete()
                    );
                }
            }
		}
		return $return;
	}

    function getfieldvalues(){
		parent::getfieldvalues();

        // Ligacao das notas fiscais
        $this->arr_notafiscal = array();
        $temporary = new Temporary("notafrete_notafiscal",FALSE);
        for($i = 0; $i < $temporary->length(); $i++){
            $this->arr_notafiscal[] = array(
                "idnotafiscal" => $temporary->getvalue($i,"idnotafiscal"),
                "totalnotafrete" => $temporary->getvalue($i,"totalnotafrete")
            );
        }
    }

    function setfieldvalues(){
		$html = parent::setfieldvalues();

        // Ligacao das notas fiscais
        $temporary = new Temporary("notafrete_notafiscal",TRUE);
		$temporary->setcolumns(array("idnotafiscal","totalnotafrete"));
		foreach($this->arr_notafiscal as $notafiscal){
			$temporary->append();
			$temporary->setvalue("last","idnotafiscal",$notafiscal["idnotafiscal"]);
            $temporary->setvalue("last","totalnotafrete",$notafiscal["totalnotafrete"]);
		}
		$temporary->save();

		return $html;
	}

	function getidnotafrete(){
		return $this->fields["idnotafrete"];
	}

	function getcodtransp(){
		return $this->fields["codtransp"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"],"Y-m-d","d/m/Y") : $this->fields["dtemissao"]);
	}

	function getdtentrega($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrega"],"Y-m-d","d/m/Y") : $this->fields["dtentrega"]);
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"],2,",","") : $this->fields["aliqicms"]);
	}

	function gettotalbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicms"],2,",","") : $this->fields["totalbaseicms"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"],2,",","") : $this->fields["totalicms"]);
	}

	function gettotalbaseisento($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseisento"],2,",","") : $this->fields["totalbaseisento"]);
	}

	function gettotalbaseoutras($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseoutras"],2,",","") : $this->fields["totalbaseoutras"]);
	}

	function getoutrasdespesas($format = FALSE){
		return ($format ? number_format($this->fields["outrasdespesas"],2,",","") : $this->fields["outrasdespesas"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

    function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

    function getcodespecie(){
		return $this->fields["codespecie"];
	}

    function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getchavecte(){
		return $this->fields["chavecte"];
	}

	function getxmlcte(){
		return $this->fields["xmlcte"];
	}

	function gettipodocumentofiscal(){
		return $this->fields["tipodocumentofiscal"];
	}

	function setidnotafrete($value){
		$this->fields["idnotafrete"] = value_numeric($value);
	}

	function setcodtransp($value){
		$this->fields["codtransp"] = value_numeric($value);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value,3);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value,9);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function settotalbaseicms($value){
		$this->fields["totalbaseicms"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function settotalbaseisento($value){
		$this->fields["totalbaseisento"] = value_numeric($value);
	}

	function settotalbaseoutras($value){
		$this->fields["totalbaseoutras"] = value_numeric($value);
	}

	function setoutrasdespesas($value){
		$this->fields["outrasdespesas"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

    function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

    function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

    function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setchavecte($value){
		$this->fields["chavecte"] = value_string($value);
	}

	function setxmlcte($value){
		$this->fields["xmlcte"] = value_string($value);
	}

	function settipodocumentofiscal($value){
		$this->fields["tipodocumentofiscal"] = value_string($value, 2);
	}

}
