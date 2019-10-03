<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Ncm extends Cadastro{
	public $arr_ncmestado;

	protected $flag_ncmestado = FALSE;

	function __construct($idncm = NULL){
		parent::__construct();
		$this->table = "ncm";
		$this->primarykey = "idncm";
		$this->setidncm($idncm);
		if($this->getidncm() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_ncmestado($bool){
		if(is_bool($bool)){
			$this->flag_ncmestado = $bool;
		}
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save($object)){
			if($this->flag_ncmestado){
				$ncmestado = objectbytable("ncmestado",NULL,$this->con);
				$ncmestado->setidncm($this->getidncm());
				$arr_ncmestado = object_array($ncmestado);
				foreach($arr_ncmestado as $ncmestado_bd){
					foreach($this->arr_ncmestado as $ncmestado_ob){
						if($ncmestado_bd->getuf() == $ncmestado_ob->getuf()){
							$ncmestado_bd->setcalculardifal($ncmestado_ob->getcalculardifal());
							$ncmestado_bd->setconvenioicms($ncmestado_ob->getconvenioicms());
							$ncmestado_bd->setaliqiva($ncmestado_ob->getaliqiva());
							$ncmestado_bd->setaliqinterna($ncmestado_ob->getaliqinterna());
							$ncmestado_bd->setaliqfcp($ncmestado_ob->getaliqfcp());
							$ncmestado_bd->setajustariva($ncmestado_ob->getajustariva());
							$ncmestado_bd->setvalorpauta($ncmestado_ob->getvalorpauta());
							$ncmestado_bd->setcalculoliqmediast($ncmestado_ob->getcalculoliqmediast());
							if($ncmestado_bd->save()){
								break;
							}else{
								$this->con->rollback();
								return FALSE;
							}
						}
					}
				}
			}
		}else{
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	function searchatdatabase($query,$fetchAll = FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && !$fetchAll){
            if($this->flag_ncmestado){
                $ncmestado = objectbytable("ncmestado",NULL,$this->con);
                $ncmestado->setidncm($this->getidncm());
                $this->arr_ncmestado = object_array($ncmestado);
            }
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

        $this->arr_ncmestado = array();
        $temporary = new Temporary("ncm_ncmestado",FALSE);
        for($i = 0; $i < $temporary->length(); $i++){
            $ncmestado = objectbytable("ncmestado",NULL,$this->con);
            $ncmestado->setidncm($this->getidncm());
            $ncmestado->setuf($temporary->getvalue($i,"uf"));
            $ncmestado->setcalculardifal($temporary->getvalue($i,"calculardifal"));
            $ncmestado->setconvenioicms($temporary->getvalue($i,"convenioicms"));
			$ncmestado->setaliqiva($temporary->getvalue($i,"aliqiva"));
			$ncmestado->setaliqfcp($temporary->getvalue($i,"aliqfcp"));
			$ncmestado->setaliqinterna($temporary->getvalue($i,"aliqinterna"));
			$ncmestado->setajustariva($temporary->getvalue($i,"ajustariva"));
			$ncmestado->setvalorpauta($temporary->getvalue($i,"valorpauta"));
			$ncmestado->setcalculoliqmediast($temporary->getvalue($i,"calculoliqmediast"));
            $this->arr_ncmestado[] = $ncmestado;
        }
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

        $temporary = new Temporary("ncm_ncmestado",TRUE);
		$temporary->setcolumns(array("uf","calculardifal","convenioicms","aliqinterna","aliqfcp","aliqiva","ajustariva","valorpauta", "calculoliqmediast"));
		foreach($this->arr_ncmestado as $ncmestado){
			$temporary->append();
			$temporary->setvalue("last","uf",$ncmestado->getuf());
            $temporary->setvalue("last","calculardifal",$ncmestado->getcalculardifal());
            $temporary->setvalue("last","convenioicms",$ncmestado->getconvenioicms());
			$temporary->setvalue("last","aliqinterna",$ncmestado->getaliqinterna());
			$temporary->setvalue("last","aliqfcp",$ncmestado->getaliqfcp());
			$temporary->setvalue("last","aliqiva",$ncmestado->getaliqiva());
			$temporary->setvalue("last","ajustariva",$ncmestado->getajustariva());
			$temporary->setvalue("last", "valorpauta", $ncmestado->getvalorpauta());
			$temporary->setvalue("last", "calculoliqmediast", $ncmestado->getcalculoliqmediast());
		}
		$temporary->save();

		return $html;
	}

	function getidncm(){
		return $this->fields["idncm"];
	}

	function getcodigoncm(){
		return $this->fields["codigoncm"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcodcfnfe(){
		return $this->fields["codcfnfe"];
	}

	function getcodcfnfs(){
		return $this->fields["codcfnfs"];
	}

	function getcodcfpdv(){
		return $this->fields["codcfpdv"];
	}

	function getcodpiscofinsent(){
		return $this->fields["codpiscofinsent"];
	}

	function getcodpiscofinssai(){
		return $this->fields["codpiscofinssai"];
	}

	function getcodipi(){
		return $this->fields["codipi"];
	}

    function getaliqiva($format = FALSE){
        return ($format ? number_format($this->fields["aliqiva"],4,",","") : $this->fields["aliqiva"]);
    }

	function getaliqmedia($format = FALSE){
        return ($format ? number_format($this->fields["aliqmedia"],4,",","") : $this->fields["aliqmedia"]);
    }

	function getidcest(){
		return $this->fields["idcest"];
	}

	function setidncm($value){
		$this->fields["idncm"] = value_numeric($value);
	}

	function setcodigoncm($value){
		$this->fields["codigoncm"] = value_string($value,10);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setcodcfnfe($value){
		$this->fields["codcfnfe"] = value_numeric($value);
	}

	function setcodcfnfs($value){
		$this->fields["codcfnfs"] = value_numeric($value);
	}

	function setcodcfpdv($value){
		$this->fields["codcfpdv"] = value_numeric($value);
	}

	function setcodpiscofinsent($value){
		$this->fields["codpiscofinsent"] = value_numeric($value);
	}

	function setcodpiscofinssai($value){
		$this->fields["codpiscofinssai"] = value_numeric($value);
	}

	function setcodipi($value){
		$this->fields["codipi"] = value_numeric($value);
	}

    function setaliqiva($value){
        $this->fields["aliqiva"] = value_numeric($value);
    }

    function setaliqmedia($value){
        $this->fields["aliqmedia"] = value_numeric($value);
    }

	function setidcest($value){
		$this->fields["idcest"] = value_numeric($value);
	}
}
?>