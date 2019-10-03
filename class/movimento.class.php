<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Movimento extends Cadastro{
	function __construct($codmovimento = NULL){
		parent::__construct();
		$this->table = "movimento";
		$this->primarykey = array("codmovimento");
		$this->newrelation("movimento","codestabelec","estabelecimento","codestabelec");
		$this->newrelation("movimento","codproduto","produto","codproduto");
		$this->newrelation("movimento","codtpdocto","tipodocumento","codtpdocto");
		$this->setcodmovimento($codmovimento);
		if(!is_null($this->getcodmovimento())){
			$this->searchbyobject();
		}
	}

	function save($object = null){
		// Verifica se o produto estab disponivel no estabelecimento
		$produtoestab = objectbytable("produtoestab",array($this->getcodestabelec(),$this->getcodproduto()),$this->con);
		if($produtoestab->getdisponivel() == "N"){
			$produto = objectbytable("produto",$this->getcodproduto(),$this->con);
			$estabelecimento = objectbytable("estabelecimento",$this->getcodestabelec(),$this->con);
			$_SESSION["ERROR"] = "O produto ".$produto->getcodproduto()." (".$produto->getdescricao().") n&atilde;o se encontra dispon&iacute;vel para o estabelecimento ".$estabelecimento->getcodestabelec()." (".$estabelecimento->getnome().").";
			return FALSE;
		}

		// Verifica se o tipo de movimento (E/S) foi preenchido
		if(strlen($this->gettipo()) == 0 && strlen($this->getcodtpdocto()) > 0){
			$tipodocumento = objectbytable("tipodocumento",$this->getcodtpdocto(),$this->con);
			$this->settipo($tipodocumento->gettipo());
		}
		return parent::save($object);
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}

	function getdtvalidade($format = FALSE){
		return ($format ? convert_date($this->fields["dtvalidade"],"Y-m-d","d/m/Y") : $this->fields["dtvalidade"]);
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcupom(){
		return $this->fields["cupom"];
	}

	function getpdv($format = FALSE){
		return ($format ? number_format($this->fields["pdv"],0,",","") : $this->fields["pdv"]);
	}

	function getqtdeunidade($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidade"],4,",","") : $this->fields["qtdeunidade"]);
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function gethrmovto(){
		return $this->fields["hrmovto"];
	}

	function getcodtpdocto(){
		return $this->fields["codtpdocto"];
	}

	function getcodmovimento(){
		return $this->fields["codmovimento"];
	}

	function getcodlote(){
		return $this->fields["codlote"];
	}

    function getcustorep($format = FALSE){
        return ($format ? number_format($this->fields["custorep"],2,",","") : $this->fields["custorep"]);
    }

    function getusuario(){
        return $this->fields["usuario"];
    }

    function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

    function gethoralog(){
        return $this->fields["horalog"];
    }

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setcupom($value){
		$this->fields["cupom"] = value_string($value,20);
	}

	function setpdv($value){
		$this->fields["pdv"] = value_numeric($value);
	}

	function setqtdeunidade($value){
		$this->fields["qtdeunidade"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function sethrmovto($value){
		$this->fields["hrmovto"] = value_time($value);
	}

	function setcodtpdocto($value){
		$this->fields["codtpdocto"] = value_numeric($value);
	}

	function setcodmovimento($value){
		$this->fields["codmovimento"] = value_numeric($value);
	}

	function setcodlote($value){
		$this->fields["codlote"] = value_numeric($value);
	}

    function setcustorep($value){
        $this->fields["custorep"] = value_numeric($value);
    }

    function setusuario($value){
        $this->fields["usuario"] = value_string($value,20);
    }

    function setdatalog($value){
        $this->fields["datalog"] = value_date($value);
    }

    function sethoralog($value){
        $this->fields["horalog"] = value_time($value);
    }
}
?>