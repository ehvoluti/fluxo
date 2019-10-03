<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FidelidadeResgate extends Cadastro{
	public $arr_fidelidaderesgatepremio;
	protected $flag_fidelidaderesgatepremio = FALSE;

	function __construct($codfidelidaderesgate = NULL){
		parent::__construct();
		$this->table = "fidelidaderesgate";
		$this->primarykey = array("codfidelidaderesgate");
		$this->setcodfidelidaderesgate($codfidelidaderesgate);

		if(!is_null($this->getcodfidelidaderesgate())){
			$this->searchbyobject();
		}
	}

	function flag_fidelidaderesgatepremio($value){
		if(is_bool($value)){
			$this->flag_fidelidaderesgatepremio = $value;
		}
	}

	function save(){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save()){

			/*marcar os lancamentos na tabela de pontos, como pontos utilizados*/
			$fidelidadepontos = objectbytable("fidelidadepontos", NULL, $this->con);
			$fidelidadepontos->setcodcliente($this->getcodcliente());
			$fidelidadepontos->setstatus("A");
			$arr_fidelidadepontos = object_array($fidelidadepontos);
			$pontos = 0;
			$pontosutilizados = $this->getpontosresgatados();
			$difpontos = 0;
			foreach($arr_fidelidadepontos as $fidelidadepontos){
				$dataexpiracao = strtotime($fidelidadepontos->getdataexpiracao());
				$dataresgate = strtotime($this->getdataresgate());
				$dif = floor(($dataexpiracao - $dataresgate) / 86400);
				if($dif > 0){
					$pontos += $fidelidadepontos->getpontosgerados();
					/*
						se os pontos acumulados ja ultrapassou o pontos referente ao(s) premio(s) que o cliente resgatou
						alterar o lancamento de ponto, fazendo uma parcialidade necessária para os pontos  fecharem
					 */
					if($pontos > $pontosutilizados){
						$difpontos = $pontos - $pontosutilizados;
						$fidelidadepontos->setpontosgerados($fidelidadepontos->getpontosgerados() - $difpontos);
						$fidelidadepontos->setvalorcompra($fidelidadepontos->getpontosgerados() / $fidelidadepontos->getfatorconversao());
					}

					$fidelidadepontos->setcodfidelidaderesgate($this->getcodfidelidaderesgate());
					$fidelidadepontos->setdataresgate($this->getdataresgate());
					$fidelidadepontos->setstatus("U");
				}else{
					$fidelidadepontos->setstatus("E");
					$fidelidadepontos->setdataresgate($this->getdataresgate());
					$fidelidadepontos->setcodfidelidaderesgate($this->getcodfidelidaderesgate());
				}
				if(!$fidelidadepontos->save()){
					$this->con->rollback();
					return FALSE;
				}
				/*
					se houve alteração de utilização parcial de pontos de um lançamento, deve gerar um lançamento
					com a difereça restante dos pontos para ser utilizado em uma próxio resgate
				 * 				 */
				if($difpontos > 0){
					$fidelidadepontosdif = new FidelidadePontos();
					$fidelidadepontosdif->setcodcliente($fidelidadepontos->getcodcliente());
					$fidelidadepontosdif->setcodestabelec($fidelidadepontos->getcodestabelec());
					$fidelidadepontosdif->setdatamovimento($fidelidadepontos->getdatamovimento());
					$fidelidadepontosdif->setdataexpiracao($fidelidadepontos->getdataexpiracao());
					$fidelidadepontosdif->setfatorconversao($fidelidadepontos->getfatorconversao());
					$fidelidadepontosdif->setvalorcompra($difpontos / $fidelidadepontos->getfatorconversao());
					$fidelidadepontosdif->setpontosgerados($difpontos);
					$fidelidadepontosdif->setcodlancto($fidelidadepontos->getcodlancto());
					$fidelidadepontosdif->setstatus("A");
					if(!$fidelidadepontosdif->save()){
						$this->con->rollback();
						return FALSE;
					}
					break;
				}

				if($pontos == $pontosutilizados){
					break;
				}
			}

			if($this->flag_fidelidaderesgatepremio){
				$fidelidaderesgatepremio = objectbytable("fidelidaderesgatepremio", NULL, $this->con);
				$fidelidaderesgatepremio->setcodfidelidaderesgate($this->getcodfidelidaderesgate());
				$arr_fidelidaderesgatepremio = object_array($fidelidaderesgatepremio);
				foreach($arr_fidelidaderesgatepremio as $fidelidaderesgatepremio){
					if(!$fidelidaderesgatepremio->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}

				foreach($this->arr_fidelidaderesgatepremio as $fidelidaderesgatepremio){
					if(strlen($fidelidaderesgatepremio->getquantidadepremio() == 0) || $fidelidaderesgatepremio->getquantidadepremio() <= 0 ){
						continue;
					}
					$fidelidaderesgatepremio->setcodfidelidaderesgate($this->getcodfidelidaderesgate());
					if(!$fidelidaderesgatepremio->save()){
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

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && $this->flag_fidelidaderesgatepremio){
			$fidelidaderesgatepremio = objectbytable("fidelidaderesgatepremio", NULL, $this->con);
			$fidelidaderesgatepremio->setcodfidelidaderesgate($this->getcodfidelidaderesgate());
			$this->arr_fidelidaderesgatepremio = object_array($fidelidaderesgatepremio);
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$this->arr_fidelidaderesgatepremio = array();
		$temporary = new Temporary("fidelidaderesgate_fidelidaderesgatepremio", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$fidelidaderesgatepremio = objectbytable("fidelidaderesgatepremio", NULL, $this->con);
			$fidelidaderesgatepremio->setcodfidelidaderesgate($this->getcodfidelidaderesgate());
			$fidelidaderesgatepremio->setcodfidelidadepremio($temporary->getvalue($i, "codfidelidadepremio"));
			$fidelidaderesgatepremio->setcodproduto($temporary->getvalue($i, "codproduto"));
			$fidelidaderesgatepremio->setpontosresgatados($temporary->getvalue($i, "pontosresgatados"));
			$fidelidaderesgatepremio->setquantidadepremio($temporary->getvalue($i, "quantidadepremio"));
			$this->arr_fidelidaderesgatepremio[] = $fidelidaderesgatepremio;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$temporary = new Temporary("fidelidaderesgate_fidelidaderesgatepremio", TRUE);
		$temporary->setcolumns(array("codfidelidadepremio","codproduto","pontosresgatados","quantidadepremio"));
		foreach($this->arr_fidelidaderesgatepremio as $fidelidaderesgatepremio){
			$temporary->append();
			$temporary->setvalue("last", "codfidelidadepremio", $fidelidaderesgatepremio->getcodfidelidadepremio());
			$temporary->setvalue("last", "codproduto", $fidelidaderesgatepremio->getcodproduto());
			$temporary->setvalue("last", "pontosresgatados", $fidelidaderesgatepremio->getpontosresgatados());
			$temporary->setvalue("last", "quantidadepremio", $fidelidaderesgatepremio->getquantidadepremio());
		}
		$temporary->save();
		return $html;
	}

	function getcodfidelidaderesgate(){
		return $this->fields["codfidelidaderesgate"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getdataresgate($format = FALSE){
		return ($format ? convert_date($this->fields["dataresgate"], "Y-m-d", "d/m/Y") : $this->fields["dataresgate"]);
	}

	function getpontosresgatados(){
		return $this->fields["pontosresgatados"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getpontosdisponiveis(){
		return $this->fields["pontosdisponiveis"];
	}

	function setcodfidelidaderesgate($value){
		$this->fields["codfidelidaderesgate"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setdataresgate($value){
		$this->fields["dataresgate"] = value_date($value);
	}

	function setpontosresgatados($value){
		$this->fields["pontosresgatados"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 400);
	}

	function setpontosdisponiveis($value){
		$this->fields["pontosdisponiveis"] = value_numeric($value);
	}
}


