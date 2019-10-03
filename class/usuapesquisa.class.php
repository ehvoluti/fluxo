<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class UsuaPesquisa extends Cadastro{
	function __construct($login = NULL){
		parent::__construct();
		$this->table = "usuapesquisa";
		$this->primarykey = array("login");
		$this->setlogin($login);
		if(!is_null($this->getlogin())){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getpesqadministradora(){
		return $this->fields["pesqadministradora"];
	}

	function getpesqbanco(){
		return $this->fields["pesqbanco"];
	}

	function getpesqcatlancto(){
		return $this->fields["pesqcatlancto"];
	}

	function getpesqcidade(){
		return $this->fields["pesqcidade"];
	}

	function getpesqclassfiscal(){
		return $this->fields["pesqclassfiscal"];
	}

	function getpesqcliente(){
		return $this->fields["pesqcliente"];
	}

	function getpesqcondpagto(){
		return $this->fields["pesqcondpagto"];
	}

	function getpesqcontabilidade(){
		return $this->fields["pesqcontabilidade"];
	}

	function getpesqcotacao(){
		return $this->fields["pesqcotacao"];
	}

	function getpesqdepartamento(){
		return $this->fields["pesqdepartamento"];
	}

	function getpesqecf(){
		return $this->fields["pesqecf"];
	}

	function getpesqembalagem(){
		return $this->fields["pesqembalagem"];
	}

	function getpesqemitente(){
		return $this->fields["pesqemitente"];
	}

	function getpesqespecie(){
		return $this->fields["pesqespecie"];
	}

	function getpesqestabelecimento(){
		return $this->fields["pesqestabelecimento"];
	}

	function getpesqetiqgondola(){
		return $this->fields["pesqetiqgondola"];
	}

	function getpesqfamilia(){
		return $this->fields["pesqfamilia"];
	}

	function getpesqfinalizadora(){
		return $this->fields["pesqfinalizadora"];
	}

	function getpesqfornecedor(){
		return $this->fields["pesqfornecedor"];
	}

	function getpesqfuncionario(){
		return $this->fields["pesqfuncionario"];
	}

	function getpesqgrupo(){
		return $this->fields["pesqgrupo"];
	}

	function getpesqgrupoprod(){
		return $this->fields["pesqgrupoprod"];
	}

	function getpesqinteresse(){
		return $this->fields["pesqinteresse"];
	}

	function getpesqinventario(){
		return $this->fields["pesqinventario"];
	}

	function getpesqipi(){
		return $this->fields["pesqipi"];
	}

	function getpesqlancamento(){
		return $this->fields["pesqlancamento"];
	}

	function getpesqlancamentogru(){
		return $this->fields["pesqlancamentogru"];
	}

	function getpesqlayout(){
		return $this->fields["pesqlayout"];
	}

	function getpesqmarca(){
		return $this->fields["pesqmarca"];
	}

	function getpesqnatoperacao(){
		return $this->fields["pesqnatoperacao"];
	}

	function getpesqncm(){
		return $this->fields["pesqncm"];
	}

	function getpesqnutricional(){
		return $this->fields["pesqnutricional"];
	}

	function getpesqocorrencia(){
		return $this->fields["pesqocorrencia"];
	}

	function getpesqoferta(){
		return $this->fields["pesqoferta"];
	}

	function getpesqparametro(){
		return $this->fields["pesqparametro"];
	}

	function getpesqpiscofins(){
		return $this->fields["pesqpiscofins"];
	}

	function getpesqproduto(){
		return $this->fields["pesqproduto"];
	}

	function getpesqreceita(){
		return $this->fields["pesqreceita"];
	}

	function getpesqrelatorio(){
		return $this->fields["pesqrelatorio"];
	}

	function getpesqsazonal(){
		return $this->fields["pesqsazonal"];
	}

	function getpesqsimprod(){
		return $this->fields["pesqsimprod"];
	}

	function getpesqstatuscliente(){
		return $this->fields["pesqstatuscliente"];
	}

	function getpesqsubcatlancto(){
		return $this->fields["pesqsubcatlancto"];
	}

	function getpesqsubgrupo(){
		return $this->fields["pesqsubgrupo"];
	}

	function getpesqtipodocumento(){
		return $this->fields["pesqtipodocumento"];
	}

	function getpesqtransportadora(){
		return $this->fields["pesqtransportadora"];
	}

	function getpesqunidade(){
		return $this->fields["pesqunidade"];
	}

	function getpesqusuario(){
		return $this->fields["pesqusuario"];
	}
    
    function getpesqprograma(){
        return $this->fields["pesqprograma"];
    }

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setpesqadministradora($value){
		$this->fields["pesqadministradora"] = value_string($value,1);
	}

	function setpesqbanco($value){
		$this->fields["pesqbanco"] = value_string($value,1);
	}

	function setpesqcatlancto($value){
		$this->fields["pesqcatlancto"] = value_string($value,1);
	}

	function setpesqcidade($value){
		$this->fields["pesqcidade"] = value_string($value,1);
	}

	function setpesqclassfiscal($value){
		$this->fields["pesqclassfiscal"] = value_string($value,1);
	}

	function setpesqcliente($value){
		$this->fields["pesqcliente"] = value_string($value,1);
	}

	function setpesqcondpagto($value){
		$this->fields["pesqcondpagto"] = value_string($value,1);
	}

	function setpesqcontabilidade($value){
		$this->fields["pesqcontabilidade"] = value_string($value,1);
	}

	function setpesqcotacao($value){
		$this->fields["pesqcotacao"] = value_string($value,1);
	}

	function setpesqdepartamento($value){
		$this->fields["pesqdepartamento"] = value_string($value,1);
	}

	function setpesqecf($value){
		$this->fields["pesqecf"] = value_string($value,1);
	}

	function setpesqembalagem($value){
		$this->fields["pesqembalagem"] = value_string($value,1);
	}

	function setpesqemitente($value){
		$this->fields["pesqemitente"] = value_string($value,1);
	}

	function setpesqespecie($value){
		$this->fields["pesqespecie"] = value_string($value,1);
	}

	function setpesqestabelecimento($value){
		$this->fields["pesqestabelecimento"] = value_string($value,1);
	}

	function setpesqetiqgondola($value){
		$this->fields["pesqetiqgondola"] = value_string($value,1);
	}

	function setpesqfamilia($value){
		$this->fields["pesqfamilia"] = value_string($value,1);
	}

	function setpesqfinalizadora($value){
		$this->fields["pesqfinalizadora"] = value_string($value,1);
	}

	function setpesqfornecedor($value){
		$this->fields["pesqfornecedor"] = value_string($value,1);
	}

	function setpesqfuncionario($value){
		$this->fields["pesqfuncionario"] = value_string($value,1);
	}

	function setpesqgrupo($value){
		$this->fields["pesqgrupo"] = value_string($value,1);
	}

	function setpesqgrupoprod($value){
		$this->fields["pesqgrupoprod"] = value_string($value,1);
	}

	function setpesqinteresse($value){
		$this->fields["pesqinteresse"] = value_string($value,1);
	}

	function setpesqinventario($value){
		$this->fields["pesqinventario"] = value_string($value,1);
	}

	function setpesqipi($value){
		$this->fields["pesqipi"] = value_string($value,1);
	}

	function setpesqlancamento($value){
		$this->fields["pesqlancamento"] = value_string($value,1);
	}

	function setpesqlancamentogru($value){
		$this->fields["pesqlancamentogru"] = value_string($value,1);
	}

	function setpesqlayout($value){
		$this->fields["pesqlayout"] = value_string($value,1);
	}

	function setpesqmarca($value){
		$this->fields["pesqmarca"] = value_string($value,1);
	}

	function setpesqnatoperacao($value){
		$this->fields["pesqnatoperacao"] = value_string($value,1);
	}

	function setpesqncm($value){
		$this->fields["pesqncm"] = value_string($value,1);
	}

	function setpesqnutricional($value){
		$this->fields["pesqnutricional"] = value_string($value,1);
	}

	function setpesqocorrencia($value){
		$this->fields["pesqocorrencia"] = value_string($value,1);
	}

	function setpesqoferta($value){
		$this->fields["pesqoferta"] = value_string($value,1);
	}

	function setpesqparametro($value){
		$this->fields["pesqparametro"] = value_string($value,1);
	}

	function setpesqpiscofins($value){
		$this->fields["pesqpiscofins"] = value_string($value,1);
	}

	function setpesqproduto($value){
		$this->fields["pesqproduto"] = value_string($value,1);
	}

	function setpesqreceita($value){
		$this->fields["pesqreceita"] = value_string($value,1);
	}

	function setpesqrelatorio($value){
		$this->fields["pesqrelatorio"] = value_string($value,1);
	}

	function setpesqsazonal($value){
		$this->fields["pesqsazonal"] = value_string($value,1);
	}

	function setpesqsimprod($value){
		$this->fields["pesqsimprod"] = value_string($value,1);
	}

	function setpesqstatuscliente($value){
		$this->fields["pesqstatuscliente"] = value_string($value,1);
	}

	function setpesqsubcatlancto($value){
		$this->fields["pesqsubcatlancto"] = value_string($value,1);
	}

	function setpesqsubgrupo($value){
		$this->fields["pesqsubgrupo"] = value_string($value,1);
	}

	function setpesqtipodocumento($value){
		$this->fields["pesqtipodocumento"] = value_string($value,1);
	}

	function setpesqtransportadora($value){
		$this->fields["pesqtransportadora"] = value_string($value,1);
	}

	function setpesqunidade($value){
		$this->fields["pesqunidade"] = value_string($value,1);
	}

	function setpesqusuario($value){
		$this->fields["pesqusuario"] = value_string($value,1);
	}
    
    function setpesqprograma($value){
        $this->fields["pesqprograma"] = value_string($value,1);
    }
}
?>