<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Orcamento extends Cadastro{

	public $itorcamento;
	protected $flag_itorcamento = FALSE;

	function __construct($codorcamento = NULL){
		parent::__construct();
		$this->table = "orcamento";
		$this->primarykey = "codorcamento";
		$this->newrelation("orcamento", "codestabelec", "estabelecimento", "codestabelec");
		$this->newrelation("orcamento", "codcliente", "cliente", "codcliente");
		$this->setcodorcamento($codorcamento);
		if($this->getcodorcamento() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_itorcamento($value){
		if(is_bool($value)){
			$this->flag_itorcamento = $value;
		}
	}

	function save($object = null){
		// Inicia uma transacao
		$this->connect();

		// Inicia uma transacao
		$this->con->start_transaction();

		// Grava o orcamento
		if(is_null($object)){
			$object = objectbytable("orcamento", array($this->getcodorcamento()), $this->con);
		}
		$param_orcamento_orcasemitem = param("ORCAMENTO","ORCASEMITEM",$this->con);
		if(!parent::save($object)){
			$this->con->rollback();
			return FALSE;
		}else{
			// Verifica se esta trabalhando com os itens
			if($this->flag_itorcamento){
				// Verifica se a lista de itens nao esta vazia
				if(count($this->itorcamento) === 0 && $param_orcamento_orcasemitem != "S"){
					$_SESSION["ERROR"] = "Informe os itens do or&ccedil;amento";
					$this->con->rollback();
					return FALSE;
				}
				// Apaga os itens para criar de novo
				$itorcamento = objectbytable("itorcamento", NULL, $this->con);
				$itorcamento->setcodorcamento($this->getcodorcamento());
				$arr_itorcamento = object_array($itorcamento);
				foreach($arr_itorcamento as $itorcamento){
					if(!$itorcamento->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				// Cria os itens de novo
				foreach($this->itorcamento as $itorcamento){
					$itorcamento->setconnection($this->con);
					$itorcamento->setcodorcamento($this->getcodorcamento());
					if(!$itorcamento->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			$this->con->commit();
			return TRUE;
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return)){
			$this->itorcamento = array();
			// Verifica se vai trabalhar com os itens
			if($this->flag_itorcamento){
				$itorcamento = objectbytable("itorcamento", NULL, $this->con);
				$itorcamento->setcodorcamento($this->getcodorcamento());
				$search = $itorcamento->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search[0])){
						$search = array($search);
					}
					foreach($search as $key){
						$this->itorcamento[] = objectbytable("itorcamento", $key, $this->con);
					}
				}
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		// Itens do orcamento
		if($this->flag_itorcamento){
			$objectsession = new ObjectSession($this->con, "itorcamento", "orcamento_itorcamento");
			$this->itorcamento = $objectsession->getobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		if($this->flag_itorcamento){
			$objectsession = new ObjectSession($this->con, "itorcamento", "orcamento_itorcamento");
			$objectsession->clear();
			$objectsession->addobject($this->itorcamento);
			$objectsession->save();
		}

		return $html;
	}

	function getcodorcamento(){
		return $this->fields["codorcamento"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"], "Y-m-d", "d/m/Y") : $this->fields["dtemissao"]);
	}

	function gethremissao(){
		return $this->fields["hremissao"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getdtstatus($format = FALSE){
		return ($format ? convert_date($this->fields["dtstatus"], "Y-m-d", "d/m/Y") : $this->fields["dtstatus"]);
	}

	function getdtvalidade($format = FALSE){
		return ($format ? convert_date($this->fields["dtvalidade"], "Y-m-d", "d/m/Y") : $this->fields["dtvalidade"]);
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"], 2, ",", "") : $this->fields["totaldesconto"]);
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"], 2, ",", "") : $this->fields["totalacrescimo"]);
	}

	function gettotalfrete($format = FALSE){
		return ($format ? number_format($this->fields["totalfrete"], 2, ",", "") : $this->fields["totalfrete"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"], 2, ",", "") : $this->fields["totalbruto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"], 2, ",", "") : $this->fields["totalliquido"]);
	}

	function getnumeroitens(){
		return $this->fields["numeroitens"];
	}

	function getratdesconto(){
		return $this->fields["ratdesconto"];
	}

	function getratvaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["ratvaldesconto"], 2, ",", "") : $this->fields["ratvaldesconto"]);
	}

	function getratacrescimo(){
		return $this->fields["ratacrescimo"];
	}

	function getratvalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["ratvalacrescimo"], 2, ",", "") : $this->fields["ratvalacrescimo"]);
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getorcprev(){
		return $this->fields["orcprev"];
	}

	function getespecificacoes(){
		return $this->fields["especificacoes"];
	}

	function getcodmoeda(){
		return $this->fields["codmoeda"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function geturlpagamento(){
		return $this->fields["urlpagamento"];
	}

	function getorigemreg(){
		return $this->fields["origemreg"];
	}

    function getreciboimpresso(){
        return $this->fields["reciboimpresso"];
    }
    
	function setcodorcamento($value){
		$this->fields["codorcamento"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function sethremissao($value){
		$this->fields["hremissao"] = value_string($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setdtstatus($value){
		$this->fields["dtstatus"] = value_date($value);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function settotaldesconto($value){
		$this->fields["totaldesconto"] = value_numeric($value);
	}

	function settotalacrescimo($value){
		$this->fields["totalacrescimo"] = value_numeric($value);
	}

	function settotalfrete($value){
		$this->fields["totalfrete"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setnumeroitens($value){
		$this->fields["numeroitens"] = value_numeric($value);
	}

	function setratdesconto($value){
		$this->fields["ratdesconto"] = value_string($value, 1);
	}

	function setratvaldesconto($value){
		$this->fields["ratvaldesconto"] = value_numeric($value);
	}

	function setratacrescimo($value){
		$this->fields["ratacrescimo"] = value_string($value, 1);
	}

	function setratvalacrescimo($value){
		$this->fields["ratvalacrescimo"] = value_numeric($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value, 1);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value, 1);
	}

	function setorcprev($value){
		$this->fields["orcprev"] = value_string($value, 1);
	}

	function setespecificacoes($value){
		$this->fields["especificacoes"] = value_string($value, 1);
	}

	function setcodmoeda($value){
		$this->fields["codmoeda"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value);
	}

	function seturlpagamento($value){
		$this->fields["urlpagamento"] = value_string($value);
	}

	function setorigemreg($value){
		$this->fields["origemreg"] = value_string($value, 1);
	}
    
    function setreciboimpresso($value){
        $this->fields["reciboimpresso"] = value_string($value, 1);
    }
}