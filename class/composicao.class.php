<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Composicao extends Cadastro{

	public $itcomposicao;
	protected $flag_clienteinteresse = FALSE;

	function __construct($codcomposicao = NULL){
		parent::__construct();
		$this->table = "composicao";
		$this->primarykey = "codcomposicao";
		$this->newrelation("composicao", "codproduto", "produto", "codproduto");
		$this->setcodcomposicao($codcomposicao);
		if($this->getcodcomposicao() != NULL){
			$this->searchbyobject();
		}
	}

	// Atualiza os precos da composicao
	// Se for passo um estabelecimento por parametro, atualiza apenas o estabelecimento informado, senao atualiza todos
	// O estabelecimento pode ser passado o objeto ou o codigo do estabelecimento
	function atualizar_precos($estabelecimento = NULL, $atualizar = "N"){
		$this->atualizar_custofilho($estabelecimento,"S");

		// Se nao for do tipo de preco automatico, nao deve atualizar o preco
		if(!in_array($this->gettipopreco(), array("A", "O")) && $atualizar == "N"){
			return TRUE;
		}

		if(strlen($estabelecimento) == 0){
			$estabelecimento = objectbytable("estabelecimento", NULL, $this->con);
			$arr_estabelecimento = object_array($estabelecimento);
		}else{
			if(!is_object($estabelecimento)){
				$estabelecimento = objectbytable("estabelecimento", $estabelecimento, $this->con);
			}
			$arr_estabelecimento = array($estabelecimento);
		}
		$this->con->start_transaction();
		$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
		$itcomposicao->setcodcomposicao($this->getcodcomposicao());
		$arr_itcomposicao = object_array($itcomposicao);

		$fatorvenda = 1;
		if($this->getfatorvenda() > 0 && param("COMPOSICAO","FATORATUAVENDA", $this->con) == "S"){
			$fatorvenda = $this->getfatorvenda();
		}
		foreach($arr_estabelecimento as $estabelecimento){
			$precovrj = 0;
			$precoatc = 0;
			$precoatcof = 0;
			$precovrjof = 0;
			$custorep = 0;
			foreach($arr_itcomposicao as $itcomposicao){
				$quantidade = $itcomposicao->getquantidade();
				$produtoestab = objectbytable("produtoestab", array($estabelecimento->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con, true);
				$precovrj += ($produtoestab->getprecovrj() * $fatorvenda) * $quantidade;
				$precoatc += ($produtoestab->getprecoatc() * $fatorvenda) * $quantidade;
				if($this->gettipocusto() != "N"){
					$custorep += $produtoestab->getcustorep() * $quantidade;
				}
				if($this->gettipopreco() == "O"){
					$precovrjof += ($produtoestab->getprecovrjof() * $fatorvenda) * $quantidade;
					$precoatcof += ($produtoestab->getprecoatcof() * $fatorvenda) * $quantidade;
				}
			}
			$produtoestab = objectbytable("produtoestab", array($estabelecimento->getcodestabelec(), $this->getcodproduto()), $this->con, true);
			$produtoestab->setprecovrj(number_format($precovrj, 2, ".", ""));
			$produtoestab->setprecoatc(number_format($precoatc, 2, ".", ""));
			if($this->gettipopreco() == "O"){
				$produtoestab->setprecovrjof(number_format($precovrjof, 2, ".", ""));
				$produtoestab->setprecoatcof(number_format($precoatcof, 2, ".", ""));
			}
			if($this->gettipocusto() != "N"){
					$produtoestab->setcustorep($custorep);
				}
			if(!$produtoestab->save()){
				$this->con->rollback();
				return FALSE;
			}
		}
		$this->con->commit();
		return TRUE;
	}

	function atualizar_custofilho($estabelecimento = NULL, $atualizar = "N"){
		// Se nao for do tipo de preco automatico, nao deve atualizar o preco
		if(!in_array($this->gettipopreco(), array("A", "O")) && $atualizar == "N"){
			return TRUE;
		}

		if(!($this->gettipocusto() == "F" && in_array($this->gettipo(), array("C", "D")))){
			return TRUE;
		}

		if(strlen($estabelecimento) == 0){
			$estabelecimento = objectbytable("estabelecimento", NULL, $this->con);
			$arr_estabelecimento = object_array($estabelecimento);
		}else{
			if(!is_object($estabelecimento)){
				$estabelecimento = objectbytable("estabelecimento", $estabelecimento, $this->con);
			}
			$arr_estabelecimento = array($estabelecimento);
		}
		$this->con->start_transaction();
		$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
		$itcomposicao->setcodcomposicao($this->getcodcomposicao());
		$arr_itcomposicao = object_array($itcomposicao);

		$fatorvenda = 1;
		if($this->getfatorvenda() > 0 && param("COMPOSICAO", "FATORATUAVENDA", $this->con) == "S"){
			$fatorvenda = $this->getfatorvenda();
		}
		foreach($arr_estabelecimento as $estabelecimento){
			$precovrj = 0;
			$precoatc = 0;
			$custorep = 0;

			if($this->gettipocusto() == "F" && in_array($this->gettipo(), array("C", "D"))){
				$produtoestabpai = objectbytable("produtoestab", array($estabelecimento->getcodestabelec(), $this->getcodproduto()), $this->con, true);

				foreach($arr_itcomposicao as $itcomposicao){
					$produtoestab = objectbytable("produtoestab", array($estabelecimento->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con, true);

					$totalvendafilho += $itcomposicao->getquantidade() * $produtoestab->getprecovrj();
				}
			}

			foreach($arr_itcomposicao as $itcomposicao){
				$quantidade = $itcomposicao->getquantidade();
				$produtoestab = objectbytable("produtoestab", array($estabelecimento->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con, true);

				$precovrj += ($produtoestab->getprecovrj() * $fatorvenda) * $quantidade;
				$precoatc += ($produtoestab->getprecoatc() * $fatorvenda) * $quantidade;
				$totalvendafilhoitem = $itcomposicao->getquantidade() * $produtoestab->getprecovrj();

				$fatorvendaitem = round($totalvendafilhoitem / $totalvendafilho, 4);
				$custototalitem = round($fatorvendaitem * $produtoestabpai->getcustorep(), 4);
				$custofinal = round($custototalitem / $quantidade, 2);
				$custorep = null;

				$produtoestab->setcustorep($custofinal);

				if(!$produtoestab->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}
		$this->con->commit();
		return TRUE;
	}

	function flag_itcomposicao($value){
		if(is_bool($value)){
			$this->flag_itcomposicao = $value;
		}
	}

	function save($object = null){
		$this->connect();
		// Verifica estoque do produto
		if(in_array($this->gettipo(), array("A", "C", "V"))){
			$produtoestab = objectbytable("produtoestab", NULL, $this->con);
			$produtoestab->setcodproduto($this->getcodproduto());
			$arr_produtoestab = object_array($produtoestab);
			foreach($arr_produtoestab as $produtoestab){
				if($produtoestab->getsldatual() != 0){
					$param = array(
						"codproduto=".$this->getcodproduto(),
						"codestabelec=".$produtoestab->getcodestabelec(),
						"quantidade=".number_format(abs($produtoestab->getsldatual()), 4, ",", ""),
						"qtdeunidade=1,0000"
					);
					$_SESSION["ERROR"] = "O produto <b>".$this->getcodproduto()."</b> deve conter o estoque zerado no estabelecimento <b>".$produtoestab->getcodestabelec()."</b>.<br>(Estoque atual: ".number_format($produtoestab->getsldatual(), 4, ",", ".").")<br><a onclick=\"$.messageBox('close'); openProgram('MovDiversa','".implode("&", $param)."')\">Clique aqui</a> para ajustar o estoque do produto.";
					return FALSE;
				}
			}
		}
		$this->con->start_transaction();
		if(parent::save($object)){
			if($this->flag_itcomposicao){
				if(sizeof($this->itcomposicao) == 0){
					$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel gravar a composi&ccedil;&atilde;o sem nenhum item.";
					$this->con->rollback();
					return FALSE;
				}
				$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
				$itcomposicao->setcodcomposicao($this->getcodcomposicao());
				$arr_itcomposicao = object_array($itcomposicao);
				foreach($arr_itcomposicao as $itcomposicao){
					if(!$itcomposicao->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->itcomposicao as $itcomposicao){
					if($itcomposicao->getcodproduto() == $this->getcodproduto()){
						$_SESSION["ERROR"] = "Existe um produto filho na composi&ccedil;&atilde;o igual ao produto pai.<br>Remova o produto da lista ou altere o produto pai da composi&ccedil;&atilde;o.";
						$this->con->rollback();
						return FALSE;
					}
					$itcomposicao->setcodcomposicao($this->getcodcomposicao());
					if(!$itcomposicao->save()){
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

	function getcodcomposicao(){
		return $this->fields["codcomposicao"];
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		// Itens da composicao
		if($this->flag_itcomposicao){
			$objectsession = new ObjectSession($this->con, "itcomposicao", "composicao_itcomposicao");
			$this->itcomposicao = $objectsession->getobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		if($this->flag_itcomposicao){
			$objectsession = new ObjectSession($this->con, "itcomposicao", "composicao_itcomposicao");
			$objectsession->clear();
			$objectsession->addobject($this->itcomposicao);
			$objectsession->save();
		}

		return $html;
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return)){
			if($this->flag_itcomposicao){
				$this->itcomposicao = array();
				$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
				$itcomposicao->setcodcomposicao($this->getcodcomposicao());
				$this->itcomposicao = object_array($itcomposicao);
			}
		}
		return $return;
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function gettipocusto(){
		return $this->fields["tipocusto"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getexplosaoauto(){
		return $this->fields["explosaoauto"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function getfatorvenda($format = FALSE){
		return ($format ? number_format($this->fields["fatorvenda"], 4, ",", "") : $this->fields["fatorvenda"]);
	}

	function setcodcomposicao($value){
		$this->fields["codcomposicao"] = value_numeric($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value, 1);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 1000);
	}

	function settipocusto($value){
		$this->fields["tipocusto"] = value_string($value, 1);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value, 1);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setexplosaoauto($value){
		$this->fields["explosaoauto"] = value_string($value, 1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_string($value, 20);
	}

	function setfatorvenda($value){
		$this->fields["fatorvenda"] = value_numeric($value);
	}

}
