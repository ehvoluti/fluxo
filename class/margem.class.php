<?php
require_once("../class/connection.class.php");
require_once("../def/function.php");

class Margem{
	private $con;
	private $tipomargem;
	private $arr_estabelecimento;
	private $produto;
	private $arr_produtoestab;
	private $classfiscalnfe;
	private $classfiscalnfs;
	private $piscofins;
	private $ipi;
	private $margematc;
	private $custorep;
	private $custotab;
	private $margemvrj;
	private $precovrj;
	private $precoatc;
	private $precosugestaoatc;
	private $precosugestaovrj;
    private $precomargemzeroatc;
    private $precomargemzerovrj;

	function __construct($con = NULL){
		if($con != NULL){
			$this->con = $con;
		}else{
			$this->con = new Connection();
		}
		$parametro = objectbytable("parametro",array("ESTOQUE","TIPOMARGEM"),$this->con);
		$this->tipomargem = $parametro->getvalor();
	}

	function calcular(){
		$this->_calcular("atc");
		$this->_calcular("vrj");
	}

	private function _calcular($tipo){
		$inclusao = false;
		$tipo = strtolower($tipo);
		if(!in_array($tipo,array("atc","vrj"))){
			return FALSE;
		}
		if(sizeof($this->arr_estabelecimento) == 1){
			$custotab = (sizeof($this->custotab) > 0 ? $this->custotab : $this->arr_produtoestab[0]->getcustotab());
			$custorep = (sizeof($this->custorep) > 0 ? $this->custorep : $this->arr_produtoestab[0]->getcustorep());
			$custosemimp = $this->arr_produtoestab[0]->getcustosemimp();
			$precoatc = ($this->arr_produtoestab[0]->getprecoatcof() > 0 ? $this->arr_produtoestab[0]->getprecoatcof() : $this->arr_produtoestab[0]->getprecoatc());
			$precovrj = ($this->arr_produtoestab[0]->getprecovrjof() > 0 ? $this->arr_produtoestab[0]->getprecovrjof() : $this->arr_produtoestab[0]->getprecovrj());
			$margematc = $this->arr_produtoestab[0]->getmargematc();
			$margemvrj = sizeof($this->margemvrj) > 0 ? $this->margemvrj : $this->arr_produtoestab[0]->getmargemvrj();
			$despoperacional = $this->arr_estabelecimento[0]->getdespoperacional();
		}else{
			if(is_object($this->produto)){
				$custotab = (sizeof($this->custotab) > 0 ? $this->custotab : $this->produto->getcustotab());
				$custorep = (sizeof($this->custorep) > 0 ? $this->custorep : $this->produto->getcustorep());
				$precoatc = ($this->produto->getprecoatcof() > 0 ? $this->produto->getprecoatcof() : $this->produto->getprecoatc());
				$precovrj = ($this->produto->getprecovrjof() > 0 ? $this->produto->getprecovrjof() : $this->produto->getprecovrj());
				$margematc = (sizeof($this->margematc) > 0 ? $this->margematc : $this->produto->getmargematc());
				$margemvrj = (sizeof($this->margemvrj) > 0 ? $this->margemvrj : $this->produto->getmargemvrj());
			}else{
				$custotab = $this->custotab;
				$custorep = $this->custorep;
				$precoatc = $this->precoatc;
				$precovrj = $this->precovrj;
				$margematc = $this->margematc;
				$margemvrj = $this->margemvrj;
			}

			$custosemimp = 0;
			$despoperacional = 0;
			if(sizeof($this->arr_produtoestab) > 0){
				foreach($this->arr_produtoestab as $produtoestab){
					$custosemimp += $produtoestab->getcustosemimp();
				}
				$custosemimp /= sizeof($this->arr_produtoestab);
				foreach($this->arr_estabelecimento as $estabelecimento){
					$despoperacional += $estabelecimento->getdespoperacional();
				}
				$despoperacional /= sizeof($this->arr_estabelecimento);
			}

		}

		if(!(sizeof($this->arr_produtoestab) > 0 || is_object($this->produto))){
			$inclusao = true;
		}

		if(sizeof($this->arr_produtoestab) > 0 || is_object($this->produto) || $inclusao){
			if($inclusao){
				$aliqicmssai = 0;
			}else{
				$aliqicmssai = (in_array($this->classfiscalnfs->gettptribicms(),array("T","R")) ? ($this->classfiscalnfs->getaliqicms() * (1 - $this->classfiscalnfs->getaliqredicms() / 100)) : 0);
			}

			if($tipo == "atc"){
				$precobru = $precoatc;
				$margemcad = $margematc;
			}else{
				$precobru = $precovrj;
				$margemcad = $margemvrj;
			}
			$custobru = $custorep;
			$custoliq = $custosemimp;
			if($inclusao){
				$precoliq = $precobru;
			}else{
				$precoliq = $precobru - ($precobru * $this->piscofins->getaliqpis() / 100) - ($precobru * $this->piscofins->getaliqcofins() / 100);
			}

			$precoliq -= $precobru * $aliqicmssai / 100;
			$lucrobru = $precobru - $custobru - $precobru * $despoperacional / 100;
			$lucroliq = $precoliq - $custoliq - $precobru * $despoperacional / 100;
			if($inclusao){
				$precomargemzero = $custoliq;
			}else{
				$precomargemzero = $custoliq / (1 - $aliqicmssai / 100 - $this->piscofins->getaliqpis() / 100 - $this->piscofins->getaliqcofins() / 100 - $despoperacional / 100);
			}


			switch($this->tipomargem){
				case 0: // Lucro bruto sobre custo
					$margem = ($custobru != 0 ? $lucrobru / $custobru * 100 : ($lucrobru != 0 ? 100 : 0));
					$precosugestao = ($custobru * (1 + $margemcad / 100)) / (1 - $despoperacional / 100);
				break;
				case 1: // Lucro liquido sobre custo
					$margem = ($custoliq != 0 ? $lucroliq / $custobru * 100 : ($lucroliq != 0 ? 100 : 0));
					if($inclusao){
						$precosugestao = ($custoliq + $custobru * $margemcad / 100) / (1 - $despoperacional / 100);
					}else{
						$precosugestao = ($custoliq + $custobru * $margemcad / 100) / (1 - $aliqicmssai / 100 - $this->piscofins->getaliqpis() / 100 - $this->piscofins->getaliqcofins() / 100 - $despoperacional / 100);
					}
				break;
				case 2: // Lucro bruto sobre venda
					$margem = ($precobru != 0 ? $lucrobru / $precobru * 100 : 0);
					$precosugestao = $custobru / (1 - $margemcad / 100 - $despoperacional / 100);
				break;
				case 3: // Lucro liquido sobre venda					
					$margem = ($precobru != 0 ? $lucroliq / $precobru * 100 : 0);
					//echo "Preco bruto: $precobru <br>Lucro Liquido: $lucroliq<br>Margem: $margem<br><br>";
					if($inclusao){
						$precosugestao = $custoliq / (1 - $margemcad / 100 - $despoperacional / 100);
					}else{
						$precosugestao = $custoliq / (1 - $aliqicmssai / 100 - $this->piscofins->getaliqpis() / 100 - $this->piscofins->getaliqcofins() / 100 - $margemcad / 100 - $despoperacional / 100);
					}
				break;
				case 4:	//Lucro bruto com diferença de aliquota (considerando IPI)
					$margem = ($custobru != 0 ? $lucrobru / $custobru * 100 : ($lucrobru != 0 ? 100 : 0));
					$aliqicmsent = (1 + $this->ipi->getaliqipi() /100 - $this->piscofins->getaliqpis() / 100 - $this->piscofins->getaliqcofins() / 100 - ($custoliq / $custorep) * (1 + $this->ipi->getaliqipi() /100)) * 100;
					$precosugestao = $custorep * ((1 + $margemcad / 100) * (1 + $this->ipi->getaliqipi() /100) + $aliqicmssai/100 - $aliqicmsent/100);
				break;
				case 5:	//Lucro bruto com diferença de aliquota (sem considerando IPI)
					$margem = ($custobru != 0 ? $lucrobru / $custobru * 100 : ($lucrobru != 0 ? 100 : 0));
					$aliqicmsent = (1 + $this->ipi->getaliqipi() /100 - $this->piscofins->getaliqpis() / 100 - $this->piscofins->getaliqcofins() / 100 - ($custoliq / $custorep) * (1 + $this->ipi->getaliqipi()/100)) * 100;
					$precosugestao = $custorep * ((1 + $margemcad / 100) + $aliqicmssai/100 - $aliqicmsent/100);
				break;
			}
			if($tipo == "atc"){
				$this->margematc = $margem;
				$this->precosugestaoatc = $precosugestao;
                $this->precomargemzeroatc = $precomargemzero;
			}else{
				$this->margemvrj = $margem;
				$this->precosugestaovrj = $precosugestao;
                $this->precomargemzerovrj = $precomargemzero;
			}
		}
	}

	function getmargematc($format = FALSE){
		return ($format ? number_format($this->margematc,2,",","") : $this->margematc);
	}

	function getprecosugestaoatc($format = FALSE){
		return ($format ? number_format($this->precosugestaoatc,2,",","") : $this->precosugestaoatc);
	}

  function getprecomargemzeroatc ($format = FALSE){
		return ($format ? number_format($this->precomargemzeroatc,2,",","") : $this->precomargemzeroatc);
  }

	function getmargemvrj($format = FALSE){
		return ($format ? number_format($this->margemvrj,2,",","") : $this->margemvrj);
	}

	function getprecosugestaovrj($format = FALSE){
		return ($format ? number_format($this->precosugestaovrj,2,",","") : $this->precosugestaovrj);
	}

    function getprecomargemzerovrj ($format = FALSE){
		return ($format ? number_format($this->precomargemzerovrj,2,",","") : $this->precomargemzerovrj);
    }

	function setcodestabelec($value){
		$this->setestabelecimento(objectbytable("estabelecimento",$value,$this->con));
	}

	function setcustotab($value){
		$this->custotab = value_numeric($value);
	}

	function setcustorep($value){
		$this->custorep = value_numeric($value);
	}

	function setmargemvrj($value){
		$this->margemvrj = value_numeric($value);
	}

	function setmargematc($value){
		$this->margematc = value_numeric($value);
	}

	function setcodproduto($value){
		if(strlen($value) > 0){
			$this->setproduto(objectbytable("produto",$value,$this->con));
		}else{
			$this->calcular();
		}
	}

	function setestabelecimento($object){
		if(is_object($object)){
			$object = array($object);
		}
		if(is_array($object)){
			$this->arr_estabelecimento = $object;
		}else{
			$this->arr_estabelecimento = array($object);
		}
		if(is_object($this->produto)){
			$this->arr_produtoestab = array();
			foreach($this->arr_estabelecimento as $estabelecimento){
				$this->arr_produtoestab[] = objectbytable("produtoestab",array($estabelecimento->getcodestabelec(),$this->produto->getcodproduto()),$this->con);
			}
			$this->calcular();
		}
	}

	function setproduto($object){
		if(is_object($object)){
			$this->produto = $object;
		}
		$this->classfiscalnfe = objectbytable("classfiscal",$this->produto->getcodcfnfe(),$this->con);
		$this->classfiscalnfs = objectbytable("classfiscal",$this->produto->getcodcfnfs(),$this->con);
		$this->piscofins = objectbytable("piscofins",$this->produto->getcodpiscofinssai(),$this->con);
		$this->ipi = objectbytable("ipi", $this->produto->getcodipi(), $this->con);
		if(sizeof($this->arr_estabelecimento) > 0){
			$this->arr_produtoestab = array();
			foreach($this->arr_estabelecimento as $estabelecimento){
				$this->arr_produtoestab[] = objectbytable("produtoestab",array($estabelecimento->getcodestabelec(),$this->produto->getcodproduto()),$this->con);
			}
		}
		$this->calcular();
	}

	function setprecovrj($value){
		$this->precovrj = value_numeric($value);
	}

	function setprecoatc($value){
		$this->precoatc = value_numeric($value);
	}

	function setcodcfnfe($value){
		if(strlen($value) > 0){
			$this->classfiscalnfe = objectbytable("classfiscal",value_numeric($value),$this->con);
		}
	}

	function setcodcfnfs($value){
		if(strlen($value) > 0){
			$this->classfiscalnfs = objectbytable("classfiscal",value_numeric($value),$this->con);
		}
	}

	function setcodpiscofinssai($value){
		if(strlen($value) > 0){
			$this->piscofins = objectbytable("piscofins",$value,$this->con);
		}
	}

	function settipomargem($tipomargem){
		$this->tipomargem = $tipomargem;
	}
}