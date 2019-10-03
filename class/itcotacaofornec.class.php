<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItCotacaoFornec extends Cadastro{
	function __construct($codcotacao = NULL, $codfornec = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "itcotacaofornec";
		$this->primarykey = array("codcotacao", "codfornec", "codproduto");
		$this->setcodcotacao($codcotacao);
		$this->setcodfornec($codfornec);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodcotacao()) && !is_null($this->getcodfornec()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function save($object = null){
		// Recalcula o preco decisao do produto
		$cotacao = objectbytable("cotacao",$this->getcodcotacao(),$this->con);
		$estabelecimento = objectbytable("estabelecimento",$cotacao->getcodestabelec(),$this->con);
		$fornecedor = objectbytable("fornecedor",$this->getcodfornec(),$this->con);
		$condpagto = objectbytable("condpagto",$fornecedor->getcodcondpagto(),$this->con);
		$produto = objectbytable("produto",$this->getcodproduto(),$this->con);
		$classfiscal = objectbytable("classfiscal",$produto->getcodcfnfe(),$this->con);
		$estado_estabelecimento = objectbytable("estado",$estabelecimento->getuf(),$this->con);
		$estado_fornecedor = objectbytable("estado",$fornecedor->getuf(),$this->con);

		if(strlen($condpagto->getcodcondpagto()) == 0){
			$_SESSION["ERROR"] = "O fornecedor <b>".$fornecedor->getcodfornec()."</b> (".$fornecedor->getnome().") n&atilde;o tem uma condi&ccedil;&atilde;o de pagamento pr&eacute; definida.<br><a onclick=\"$.messageBox('close'); openProgram('Fornecedor','codfornec=".$fornecedor->getcodfornec()."')\">Clique aqui</a> para abrir o cadastro do fornecedor.";
			return FALSE;
		}

		if($estado_estabelecimento->getuf() == $estado_fornecedor->getuf()){
			$aliqicms = $classfiscal->getaliqicms() * (1 - $classfiscal->getaliqredicms() / 100);
		}else{
			$aliqicms = $IcmsInterestadual[$estado_fornecedor->getregiao()][$estado_estabelecimento->getregiao()];
		}
		if(in_array($classfiscal->gettptribicms(),array("F","I"))){
			$aliqicms = 0;
		}

		$preco_sem_imposto = $this->getpreco() * (1 - $aliqicms / 100);

		$total_dia = 0;
		for($i = 1; $i <= 12; $i++){
			$dia = call_user_func(array($condpagto,"getdia".$i));
			if($dia > 0){
				$total_dia += $dia;
			}else{
				break;
			}
		}
		$i--;

		$prazo_medio = ($i === 0 ? 0 : ($total_dia / $i));
		if($total_dia == 0){
			$preco_decisao = $preco_sem_imposto;
		}else{
			$preco_decisao = $preco_sem_imposto / pow((1 + ($estabelecimento->gettaxajuromensal() / 30) / 100),$prazo_medio);
		}
		$this->setprecodecisao($preco_decisao);

		return parent::save($object);
	}

	function getcodcotacao(){
		return $this->fields["codcotacao"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],4,",","") : $this->fields["preco"]);
	}

	function getprecodecisao($format = FALSE){
		return ($format ? number_format($this->fields["precodecisao"],4,",","") : $this->fields["precodecisao"]);
	}

	function setcodcotacao($value){
		$this->fields["codcotacao"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function setprecodecisao($value){
		$this->fields["precodecisao"] = value_numeric($value);
	}
}
?>