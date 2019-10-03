<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/itinventario.class.php");
require_file("class/temporary.class.php");

class Inventario extends Cadastro{
	public $itinventario;
	protected $flag_itinventario = FALSE;

	function __construct($codinventario = NULL){
		parent::__construct();
		$this->table = "inventario";
		$this->primarykey = array("codinventario");
		$this->setcodinventario($codinventario);
		if($this->getcodinventario() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_itinventario($value){
		if(is_bool($value)){
			$this->flag_itinventario = $value;
		}
	}

	function save($object = null){
        $this->connect();
		$this->con->start_transaction();
		$object = objectbytable("inventario",$this->getcodinventario(),$this->con);
		if(parent::save($object)){
			if($this->flag_itinventario){
				// Apaga os itens para criar de novo
                $itinventario = objectbytable("itinventario",NULL,$this->con);
                $itinventario->setcodinventario($this->getcodinventario());
                $arr_itinventario = object_array($itinventario);
                foreach($arr_itinventario as $itinventario){
                    if(!$itinventario->delete()){
                        $this->con->rollback();
                        return FALSE;
                    }
                }
				// Cria os itens de novo
				foreach($this->itinventario as $i => $itinventario){
					$itinventario->setcodinventario($this->getcodinventario());
					$itinventario->setordem($i + 1);
					if(!$itinventario->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			// Atualiza tudo no banco de dados
			$this->con->commit();
			return TRUE;
		}else{
            $this->con->rollback();
			return FALSE;
		}
	}

	function searchatdatabase($query,$fetchAll=FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && !is_array($return[0])){
			if($this->flag_itinventario){
				/*
				$this->itinventario = array();
				$itinventario = objectbytable("itinventario",NULL,$this->con);
				$itinventario->setcodinventario($this->getcodinventario());
				$arr = $itinventario->searchbyobject(NULL,NULL,TRUE);
				foreach($arr as $row){
					$itinventario = objectbytable("itinventario",NULL,$this->con);
					foreach($row as $column => $value){
						call_user_func(array($itinventario,"set".$column),$value);
					}
					$this->itinventario[] = $itinventario;
				}
				*/
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$temporary = new Temporary("inventario_itinventario",FALSE);
		$this->itinventario = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$itinventario = new ItInventario();
			$itinventario->setconnection($this->con);
			$itinventario->setcodproduto($temporary->getvalue($i,"codproduto"));
			if(strlen($this->getdatacong()) > 0){
				$sldatual = $temporary->getvalue($i,"sldatual");
				$sldinventario = $temporary->getvalue($i,"sldinventario");
			}else{
				$sldatual = 0;
				$sldinventario = 0;
			}
			$itinventario->setsldatual($sldatual);
			$itinventario->setsldinventario($sldinventario);
			$this->itinventario[] = $itinventario;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		/*
		$temporary = new Temporary("inventario_itinventario",TRUE);
		$temporary->setcolumns(array("codproduto","sldatual","sldinventario","custo","preco","descricaofiscal"));
		foreach($this->itinventario as $itinventario){
			$temporary->append();
			$temporary->setvalue("last","codproduto",$itinventario->getcodproduto());
			$temporary->setvalue("last","sldatual",$itinventario->getsldatual());
			$temporary->setvalue("last","sldinventario",$itinventario->getsldinventario());
			$temporary->setvalue("last","custo",$itinventario->getcusto());
			$temporary->setvalue("last","preco",$itinventario->getpreco());
		}
		$temporary->save();
		*/
		return $html;
	}

	function getcodinventario(){
		return $this->fields["codinventario"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdata($format = FALSE){
		if($format){
			return convert_date($this->fields["data"],"Y-m-d","d/m/Y");
		}else{
			return $this->fields["data"];
		}
	}

	function getdatacong($format = FALSE){
		if($format){
			return convert_date($this->fields["datacong"],"Y-m-d","d/m/Y");
		}else{
			return $this->fields["datacong"];
		}
	}

	function getatualizado(){
		return $this->fields["atualizado"];
	}

	function getzeranegativo(){
		return $this->fields["zeranegativo"];
	}

	function getcontagens(){
		return $this->fields["contagens"];
	}

	function getdataatua($format = FALSE){
		if($format){
			return convert_date($this->fields["dataatua"],"Y-m-d","d/m/Y");
		}else{
			return $this->fields["dataatua"];
		}
	}

	function setcodinventario($value){
		$this->fields["codinventario"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function setdatacong($value){
		$this->fields["datacong"] = value_date($value);
	}

	function setatualizado($value){
		$this->fields["atualizado"] = value_string($value,1);
	}

	function setzeranegativo($value){
		$this->fields["zeranegativo"] = value_string($value,1);
	}

	function setcontagens($value){
		$value = value_numeric($value);
		$this->fields["contagens"] = (in_array($value,array(1,2)) ? $value : NULL);
	}

	function setdataatua($value){
		$this->fields["dataatua"] = value_date($value);
	}
}
?>