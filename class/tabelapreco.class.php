<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TabelaPreco extends Cadastro{
    private $arr_ittabelapreco = array();
    private $flag_ittabelapreco = FALSE;

	function __construct($codtabela = NULL){
		parent::__construct();
		$this->table = "tabelapreco";
		$this->primarykey = array("codtabela");
		$this->setcodtabela($codtabela);
		if(!is_null($this->getcodtabela())){
			$this->searchbyobject();
		}
	}

    function flag_ittabelapreco($value){
		if(is_bool($value)){
			$this->flag_ittabelapreco = $value;
		}
	}

    function save($object = null){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save($object)){
			// Ligacao das excecoes da tabela de preco
			if($this->flag_ittabelapreco){
                $ittabelapreco = objectbytable("ittabelapreco",NULL,$this->con);
                $ittabelapreco->setcodtabela($this->getcodtabela());
                $arr_ittabelapreco = object_array($ittabelapreco);
                foreach($arr_ittabelapreco as $ittabelapreco){
                    if(!$ittabelapreco->delete()){
                        $this->con->rollback();
                        return FALSE;
                    }
                }
                foreach($this->arr_ittabelapreco as $ittabelapreco){
                    $ittabelapreco->setcodtabela($this->getcodtabela());
                    if(!$ittabelapreco->save()){
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
            // Ligacao das excecoes da tabela de preco
            if($this->flag_ittabelapreco){
                $this->arr_ittabelapreco = array();
                $ittabelapreco = objectbytable("ittabelapreco",NULL,$this->con);
                $ittabelapreco->setcodtabela($this->getcodtabela());
                $this->arr_ittabelapreco = object_array($ittabelapreco);
            }
		}
		return $return;
	}

    function getfieldvalues(){
		parent::getfieldvalues();

        // Ligacao das excecoes da tabela de preco
        $this->arr_ittabelapreco = array();
        $temporary = new Temporary("tabelapreco_ittabelapreco",FALSE);
        for($i = 0; $i < $temporary->length(); $i++){
            $ittabelapreco = objectbytable("ittabelapreco",NULL,$this->con);
            $ittabelapreco->setcodtabela($this->getcodtabela());
            $ittabelapreco->setcodproduto($temporary->getvalue($i,"codproduto"));
            $ittabelapreco->setpercpreco($temporary->getvalue($i,"percpreco"));
            $this->arr_ittabelapreco[] = $ittabelapreco;
        }
    }

    function setfieldvalues(){
		$html = parent::setfieldvalues();

        // Ligacao das excecoes da tabela de preco
        $temporary = new Temporary("tabelapreco_ittabelapreco",TRUE);
		$temporary->setcolumns(array("codproduto","percpreco"));
		foreach($this->arr_ittabelapreco as $ittabelapreco){
			$temporary->append();
			$temporary->setvalue("last","codproduto",$ittabelapreco->getcodproduto());
            $temporary->setvalue("last","percpreco",$ittabelapreco->getpercpreco());
		}
		$temporary->save();

		return $html;
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getpercpreco($format = FALSE){
		return ($format ? number_format($this->fields["percpreco"],2,",","") : $this->fields["percpreco"]);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value,1);
	}

	function setpercpreco($value){
		$this->fields["percpreco"] = value_numeric($value);
	}
}
?>