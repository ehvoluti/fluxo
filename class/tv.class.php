<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TV extends Cadastro{

	public $ittv;

	function __construct($codtv = NULL){
		parent::__construct();
		$this->table = "tv";
		$this->primarykey = "codtv";
		$this->setcodtv($codtv);
		$this->newrelation("tv", "codtv", "ittv", "codtv");
		if($this->getcodtv() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_ittv($value){
		if(is_bool($value)){
			$this->flag_ittv = $value;
		}
	}

	function save($object = null){
		$this->con->start_transaction();
		if(!parent::save()){
			$this->con->rollback();
			return FALSE;
		}

		if(is_file("../tv/img/temp.png")){
			copy("../tv/img/temp.png", "../tv/img/{$this->getcodtv()}.png");
			unlink("../tv/img/temp.png");
		}

		if(parent::save($object)){
			if($this->flag_ittv){
//				if(sizeof($this->ittv) == 0){
//					$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel gravar a tv sem nenhum item.";
//					$this->con->rollback();
//					return FALSE;
//				}
				$ittv = objectbytable("ittv", NULL, $this->con);
				$ittv->setcodtv($this->getcodtv());
				$arr_ittv = object_array($ittv);
				foreach($arr_ittv as $ittv){
					if(!$ittv->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->ittv as $ittv){
					$ittv->setcodtv($this->getcodtv());
					$ittv->setcodproduto($ittv->getcodproduto());
					if(!$ittv->save()){
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

		$this->con->commit();
		return true;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		// Itens da tv
		if($this->flag_ittv){
			$objectsession = new ObjectSession($this->con, "ittv", "tv_ittv");
			$this->ittv = $objectsession->getobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		if($this->flag_ittv){
			$objectsession = new ObjectSession($this->con, "ittv", "tv_ittv");
			$objectsession->clear();
			$objectsession->addobject($this->ittv);
			$objectsession->save();
		}

		return $html;
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return)){
			if($this->flag_ittv){
				$this->ittv = array();
				$ittv = objectbytable("ittv", NULL, $this->con);
				$ittv->setcodtv($this->getcodtv());
				$this->ittv = object_array($ittv);
			}
		}
		return $return;
	}

	function getcodtv(){
		return $this->fields["codtv"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getgrupo(){
		return $this->fields["grupo"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function gettempo(){
		return $this->fields["tempo"];
	}

	function getcorfonte(){
		return $this->fields["corfonte"];
	}

	function getcorfonteoferta(){
		return $this->fields["corfonteoferta"];
	}

	function gettipofonte(){
		return $this->fields["tipofonte"];
	}

	function getposicaox(){
		return $this->fields["posicaox"];
	}

	function getposicaoy(){
		return $this->fields["posicaoy"];
	}

	function geturlexterna(){
		return $this->fields["urlexterna"];
	}

	function seturlexterna($value){
		$this->fields["urlexterna"] = value_string($value);
	}

	function setposicaox($value){
		$this->fields["posicaox"] = value_numeric($value);
	}

	function setposicaoy($value){
		$this->fields["posicaoy"] = value_numeric($value);
	}

	function setcorfonte($value){
		$this->fields["corfonte"] = value_string($value);
	}

	function setcorfonteoferta($value){
		$this->fields["corfonteoferta"] = value_string($value);
	}

	function settipofonte($value){
		$this->fields["tipofonte"] = value_string($value);
	}

	function setcodtv($value){
		$this->fields["codtv"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,100);
	}

	function setgrupo($value){
		$this->fields["grupo"] = value_string($value,200);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function settempo($value){
		$this->fields["tempo"] = value_numeric($value);
	}
}