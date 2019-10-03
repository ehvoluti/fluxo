<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NegociacaoPreco extends Cadastro{
	public $arr_itnegociacaopreco = array();
	
	function __construct($codnegociacaopreco = NULL){
		parent::__construct();
		$this->table = "negociacaopreco";
		$this->primarykey = array("codnegociacaopreco");
		$this->setcodnegociacaopreco($codnegociacaopreco);
		if($this->getcodnegociacaopreco() != NULL){
			$this->searchbyobject();
		}
	}
	
	function save(){
		$this->connect();
		
		$this->con->start_transaction();
		$object = objectbytable("negociacaopreco",array($this->getcodnegociacaopreco()),$this->con);
		
		if(!parent::save($object)){
			$this->con->rollback();
			return FALSE;
		}else{
			$objectsession = new ObjectSession($this->con,"itnegociacaopreco","negociacaopreco_itnegociacaopreco");
			$arr_objectsession = $objectsession->getobject();

			$itnegociacaopreco = objectbytable("itnegociacaopreco",NULL,$this->con);
			$arr_column = $itnegociacaopreco->getcolumnnames();

			foreach($arr_objectsession AS $objectsession){				
				$itnegociacaopreco = objectbytable("itnegociacaopreco",NULL,$this->con);
								
				foreach($arr_column as $column){
					call_user_func(array($itnegociacaopreco,"set".$column),call_user_func(array($objectsession,"get".$column)));				
				}
				if($objectsession->getprecovrj() > 0){
					$produtoestab = objectbytable("produtoestab",array($this->getcodestabelec(),$objectsession->getcodproduto()), $this->con);
					$produtoestab->setprecovrj($objectsession->getprecovrj());
					if(!$produtoestab->save()){
						$this->con->rollback();
						die(messagebox("error","",$_SESSION["ERROR"]));
					}
				}
				
				if(strlen($itnegociacaopreco->getcodnegociacaopreco()) == 0){
					$itnegociacaopreco->setcodnegociacaopreco($this->getcodnegociacaopreco());
				}				

				if(!$itnegociacaopreco->save()){
					$this->con->rollback();
					die(messagebox("error","",$_SESSION["ERROR"]));
				}	
			}		
			$this->con->commit();
			return TRUE;
		}
	}
	
	function getfieldvalues(){
		parent::getfieldvalues();		
		$objectsession = new ObjectSession($this->con,"itnegociacaopreco","negociacaopreco_itnegociacaopreco");
		$this->arr_itnegociacaopreco = $objectsession->getobject();			
	}

	function searchatdatabase($query,$fetchAll = FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && !is_array($return[0])){
			$this->arr_itnegociacaopreco = array();
			
			$itnegociacaopreco = objectbytable("itnegociacaopreco",NULL,$this->con);
			$itnegociacaopreco->setcodnegociacaopreco($this->getcodnegociacaopreco());			
			$this->arr_itnegociacaopreco = object_array($itnegociacaopreco);			
		}
		return $return;
	}
	
	function setfieldvalues(){
		$html = parent::setfieldvalues();
		
		$objectsession = new ObjectSession($this->con,"itnegociacaopreco","negociacaopreco_itnegociacaopreco");
		$objectsession->clear();
		foreach($this->arr_itnegociacaopreco AS $itnegociacaopreco){
			$objectsession->addobject($itnegociacaopreco);
		}
		$objectsession->save();	
		return $html;
	} 
				
	function getcodnegociacaopreco(){
		return $this->fields["codnegociacaopreco"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}
	
	function getdescricao(){
		return $this->fields["descricao"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getdtvigor($format = FALSE){
		return ($format ? convert_date($this->fields["dtvigor"],"Y-m-d","d/m/Y") : $this->fields["dtvigor"]);
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function setcodnegociacaopreco($value){
		$this->fields["codnegociacaopreco"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}
	
	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value,1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setdtvigor($value){
		$this->fields["dtvigor"] = value_date($value);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value,2);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>