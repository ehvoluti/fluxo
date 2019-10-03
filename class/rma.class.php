<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Rma extends Cadastro{
	public $arr_itrma;
    protected $flag_itrma = FALSE;

	function __construct($codrma = NULL){
		parent::__construct();
		$this->table = "rma";
		$this->primarykey = array("codrma");
		$this->setcodrma($codrma);
		if(!is_null($this->getcodrma())){
			$this->searchbyobject();
		}
	}

	function flag_itrma($value){
		if(is_bool($value)){
			$this->flag_itrma = $value;
		}
	}

	function save(){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save()){
			if($this->flag_itrma){
				$itrma = objectbytable("itrma",NULL,$this->con);
				$itrma->setcodrma($this->getcodrma());
				$arr_itrma = object_array($itrma);
				foreach($arr_itrma as $itrma){
					if(!$itrma->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->arr_itrma as $itrma){
					$itrma->setcodrma($this->getcodrma());
					if(!$itrma->save()){
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
            if($this->flag_itrma){
                $itrma = objectbytable("itrma",NULL,$this->con);
                $itrma->setcodrma($this->getcodrma());
                $this->arr_itrma = object_array($itrma);
            }
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

        $this->arr_itrma = array();
        $temporary = new Temporary("rma_itrma",FALSE);
        for($i = 0; $i < $temporary->length(); $i++){
            $itrma = objectbytable("itrma",NULL,$this->con);
            $itrma->setcodrma($this->getcodrma());
            $itrma->setiditnotafiscal($temporary->getvalue($i,"iditnotafiscal"));
			$itrma->setquantidade($temporary->getvalue($i,"quantidade"));
			$itrma->setnumeroserie($temporary->getvalue($i,"numeroserie"));
            $this->arr_itrma[] = $itrma;
        }
	}

	function setfieldvalues(){
		$temporary = new Temporary("rma_itrma",TRUE);
		$temporary->setcolumns(array("iditnotafiscal","quantidade","numeroserie"));
		foreach($this->arr_itrma as $itrma){
			$temporary->append();
			$temporary->setvalue("last","iditnotafiscal",$itrma->getiditnotafiscal());
            $temporary->setvalue("last","quantidade",$itrma->getquantidade());
			$temporary->setvalue("last","numeroserie",$itrma->getnumeroserie());
		}
		$temporary->save();

		return parent::setfieldvalues();
	}

	function getcodrma(){
		return $this->fields["codrma"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getmotivo(){
		return $this->fields["motivo"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function setcodrma($value){
		$this->fields["codrma"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setmotivo($value){
		$this->fields["motivo"] = value_string($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}
}
?>