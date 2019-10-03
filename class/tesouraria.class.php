<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Tesouraria extends Cadastro{
	public $arr_tesourariafinaliz;
	protected $flag_tesourariafinaliz = FALSE;

	function __construct($codtesouraria = NULL){
		parent::__construct();
		$this->table = "tesouraria";
		$this->primarykey = array("codtesouraria");
		$this->setcodtesouraria($codtesouraria);
		if(!is_null($this->getcodtesouraria())){
			$this->searchbyobject();
		}
	}

	function flag_tesourariafinaliz($bool){
		if(is_bool($bool)){
			$this->flag_tesourariafinaliz = $bool;
		}
	}

	function save(){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save()){

			if($this->flag_tesourariafinaliz){
				$tesourariafinaliz = objectbytable("tesourariafinaliz", NULL, $this->con);
				$tesourariafinaliz->setcodtesouraria($this->getcodtesouraria());
				$arr_tesourariafinaliz = object_array($tesourariafinaliz);
				foreach($arr_tesourariafinaliz as $tesourariafinaliz){
					if(!$tesourariafinaliz->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->arr_tesourariafinaliz as $tesourariafinaliz){
					$tesourariafinaliz->setcodtesouraria($this->getcodtesouraria());
					$tesourariafinaliz->setcodestabelec($this->getcodestabelec());
					if($tesourariafinaliz->getvalortotal() > 0 || $tesourariafinaliz->getfundocaixa() > 0 || $tesourariafinaliz->getvalortotalpdv() > 0){
						if($tesourariafinaliz->getvalortotal() == 0){
							$tesourariafinaliz->setvalortotal("0.00");
						}
						if($tesourariafinaliz->getvalortotalpdv() == 0){
							$tesourariafinaliz->setvalortotalpdv("0.00");
						}
						if(!$tesourariafinaliz->save()){
							$this->con->rollback();
							return FALSE;
						}
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
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && $this->flag_tesourariafinaliz){
			$tesourariafinaliz = objectbytable("tesourariafinaliz", NULL, $this->con);
			$tesourariafinaliz->setcodtesouraria($this->getcodtesouraria());
			$this->arr_tesourariafinaliz = object_array($tesourariafinaliz);
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$this->arr_tesourariafinaliz = array();
		$temporary = new Temporary("tesouraria_tesourariafinaliz", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$tesourariafinaliz = objectbytable("tesourariafinaliz", NULL, $this->con);
			$tesourariafinaliz->setcodtesouraria($this->getcodtesouraria());
			$tesourariafinaliz->setcodestabelec($this->getcodestabelec());
			$tesourariafinaliz->setcodfinaliz($temporary->getvalue($i, "codfinaliz"));
			$tesourariafinaliz->setvalortotalpdv($temporary->getvalue($i, "valortotalpdv"));
			$tesourariafinaliz->setfundocaixa($temporary->getvalue($i, "fundocaixa"));
			$tesourariafinaliz->setvalortotal($temporary->getvalue($i, "valortotal"));
			$this->arr_tesourariafinaliz[] = $tesourariafinaliz;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$temporary = new Temporary("tesouraria_tesourariafinaliz", TRUE);
		$temporary->setcolumns(array("codfinaliz", "valortotalpdv","fundocaixa", "valortotal"));
		foreach($this->arr_tesourariafinaliz as $tesourariafinaliz){
			$temporary->append();
			$temporary->setvalue("last", "codfinaliz", $tesourariafinaliz->getcodfinaliz());
			$temporary->setvalue("last", "valortotalpdv", $tesourariafinaliz->getvalortotalpdv());
			$temporary->setvalue("last", "fundocaixa", $tesourariafinaliz->getfundocaixa());
			$temporary->setvalue("last", "valortotal", $tesourariafinaliz->getvalortotal());
		}
		$temporary->save();
		return $html;
	}

	function getcodtesouraria(){
		return $this->fields["codtesouraria"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"], "Y-m-d", "d/m/Y") : $this->fields["dtmovto"]);
	}

	function getcaixa(){
		return $this->fields["caixa"];
	}

	function getnumfechamento(){
		return $this->fields["numfechamento"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getvalortotal($format = FALSE){
		return ($format ? number_format($this->fields["valortotal"], 2, ",", "") : $this->fields["valortotal"]);
	}

	function getgeroulancto(){
		return $this->fields["geroulancto"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getrecebimento($format = FALSE){
		return ($format ? number_format($this->fields["recebimento"], 2, ",", "") : $this->fields["recebimento"]);
	}

	function setcodtesouraria($value){
		$this->fields["codtesouraria"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function setcaixa($value){
		$this->fields["caixa"] = value_numeric($value);
	}

	function setnumfechamento($value){
		$this->fields["numfechamento"] = value_numeric($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setvalortotal($value){
		$this->fields["valortotal"] = value_numeric($value);
	}

	function setgeroulancto($value){
		$this->fields["geroulancto"] = value_string($value, 1);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 200);
	}

	function setrecebimento($value){
		$this->fields["recebimento"] = value_numeric($value);
	}

}