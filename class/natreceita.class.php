<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NatReceita extends Cadastro{
	function __construct($natreceita = NULL, $tabela = NULL, $codigo = NULL){
		parent::__construct();
		$this->table = "natreceita";
		$this->primarykey = array("natreceita", "tabela", "codigo");
		$this->setnatreceita($natreceita);
		$this->settabela($tabela);
		$this->setcodigo($codigo);
		if(!is_null($this->getnatreceita()) && !is_null($this->gettabela()) && !is_null($this->getcodigo())){
			$this->searchbyobject();
		}
	}
    
    function setfieldvalues(){
		$html = parent::setfieldvalues();

        switch($this->gettabela()){
            case "P":
                $html .= "$(\"#codproduto\").val(\"".$this->getcodigo()."\"); ";
                break;
            case "D":
                $html .= "$(\"#coddepto\").val(\"".$this->getcodigo()."\"); ";
                break;
            case "G":
                $grupoprod = objectbytable("grupoprod",$this->getcodigo(),$this->con);
                $html .= "$(\"#coddepto\").val(\"".$grupoprod->getcoddepto()."\"); ";
                $html .= "filterchild(\"coddepto\",[\"".$grupoprod->getcodgrupo()."\"]); ";
                break;
            case "S":
                $subgrupo  = objectbytable("subgrupo",$this->getcodigo(),$this->con);
                $grupoprod = objectbytable("grupoprod",$subgrupo->getcodgrupo(),$this->con);
                $html .= "$(\"#coddepto\").val(\"".$grupoprod->getcoddepto()."\"); ";
                $html .= "filterchild(\"coddepto\",[\"".$grupoprod->getcodgrupo()."\",\"".$subgrupo->getcodsubgrupo()."\"]); ";
                break;
        }
        return $html;
	}
    
	function getnatreceita(){
		return $this->fields["natreceita"];
	}

	function gettabela(){
		return $this->fields["tabela"];
	}

	function getcodigo(){
		return $this->fields["codigo"];
	}

	function setnatreceita($value){
		$this->fields["natreceita"] = value_string($value,3);
	}

	function settabela($value){
		$this->fields["tabela"] = value_string($value,1);
	}

	function setcodigo($value){
		$this->fields["codigo"] = value_numeric($value);
	}
}
?>