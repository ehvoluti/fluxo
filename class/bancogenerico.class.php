<?php
require_once("websac/require_file.php");
require_file("class/interfacebancaria.class.php");

class BancoGenerico extends InterfaceBancaria{
	protected $banco;
	protected $estabelecimento;
	protected $arr_codlancto;
	protected $numcheque;

	function gerar_remessa(){

		$arr_remessa = array();

		$this->con->start_transaction();

		$res = $this->con->query("SELECT SUM(valorliquido) FROM lancamento WHERE codlancto IN (".implode(",",$this->arr_codlancto).")");
		$totalparcela = $res->fetchColumn();

		if(strlen($this->numcheque) > 0){
			$cheque = objectbytable("cheque",null,$this->con);
			$cheque->setnumcheque($this->numcheque);
			$cheque->setcodbanco($this->banco->getcodbanco());
			$cheque->setcodestabelec($this->estabelecimento->getcodestabelec());
			$cheque->setvalorcheque(value_numeric($totalparcela));
			$cheque->setnominal($this->estabelecimento->getrazaosocial());
			$cheque->setdtemissao(date("Y-m-d"));
			$cheque->setdtpagto(date("Y-m-d"));

			if(!$cheque->save()){
				return FALSE;
			}
		}


		foreach($this->arr_codlancto as $codlancto){
			$lancamento = objectbytable("lancamento",$codlancto,$this->con);

			$arr_remessa[] = array(
				"chave" => $lancamento->getcodlancto(),
				"favorecido" => $lancamento->getfavorecido(),
				"dtemissao" => $lancamento->getdtemissao(),
				"dtvencto" => $lancamento->getdtvencto(),
				"valor" => $lancamento->getvalorparcela(),
				"numnotafis" => $lancamento->getnumnotafis(),
				"numcheque" => $this->numcheque
			);

			$controleprocfinan = objectbytable("controleprocfinan",NULL,$this->con);
			$controleprocfinan->settipoprocesso("GR"); // Geracao Remessa
			$controleprocfinan->setstatus("N"); // Normal
			$controleprocfinan->setpagrec($lancamento->getpagrec());

			if(!$controleprocfinan->save()){
				$this->con->rollback();
				return FALSE;
			}

			$lancamento->setseqremessa($this->banco->getseqremessa());
			$lancamento->setprocessogr($controleprocfinan->getcodcontrprocfinan());
			$lancamento->setcodocorrencia("GR");
			$lancamento->setocorrencia("00");
			$lancamento->setmotivoocorrencia("Sem Layout de Banco");
			$lancamento->setdtremessa(date("Y-m-d"));
			$lancamento->setcodbanco($this->banco->getcodbanco());
			if(strlen($this->numcheque) > 0){
				$lancamento->setcodcheque($cheque->getcodcheque());
			}
			$this->banco->setseqremessa($this->banco->getseqremessa() + 1);

			if(!$this->banco->save()){
				return FALSE;
			}

			if(!$lancamento->save()){
				return FALSE;
			}
		}
		$this->con->commit();
		echo script("filtrar()");
		echo script("$(\"#codcontrprocfinan\").refreshComboBox().trigger(\"change\")");

		$controleprocfinan = is_object($controleprocfinan) ? $controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
		$paramarqremessa = array("numremessa" => $controleprocfinan,"banco" => $this->banco->getcodoficial(),"nomebanco" => $this->banco->getnome());

		parent::relatorio_remessa($arr_remessa,$paramarqremessa);
	}

	public function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	public function setbanco($banco){
		$this->banco = $banco;
	}

	public function setnumcheque($numcheque){
		$this->numcheque = $numcheque;
	}

	public function setarr_codlancto($arr_codlancto){
		$this->arr_codlancto = $arr_codlancto;
	}

}
?>
