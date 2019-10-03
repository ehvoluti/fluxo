<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NatOperacao extends Cadastro{

	public $natoperacaoestab;

	function __construct($natoperacao = NULL){
		parent::__construct();
		$this->table = "natoperacao";
		$this->primarykey = "natoperacao";
		$this->setnatoperacao($natoperacao);
		if($this->getnatoperacao() != NULL){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('codcatlancto','".$this->getcodsubcatlancto()."'); ";

		$temporary = new Temporary("natoperacaoestab", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$temporary->setvalue($i, "codconta", "");
			foreach($this->arr_natoperacaoestab as $natoperacaoestab){
				if($temporary->getvalue($i, "codestabelec") == $natoperacaoestab->getcodestabelec()){
					$temporary->setvalue($i, "codconta", $natoperacaoestab->getcodconta());
				}
			}
		}
		$temporary->save();
		$html .= "natoperacaoestab_desenhar(); ";

		return $html;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		$this->arr_natoperacaoestab = array();
		$temporary = new Temporary("natoperacaoestab", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$natoperacaoestab = objectbytable("natoperacaoestab", NULL, $this->con);
			$natoperacaoestab->setcodestabelec($temporary->getvalue($i, "codestabelec"));
			$natoperacaoestab->setnatoperacao($this->getnatoperacao());
			$natoperacaoestab->setcodconta($temporary->getvalue($i, "codconta"));
			$this->arr_natoperacaoestab[] = $natoperacaoestab;
		}
	}

	function save($object = null){
		$operacaonota = objectbytable("operacaonota", $this->getoperacao(), $this->con);

		if($operacaonota->gettipo() == "E" && !in_array(substr($this->getnatoperacao(),0,1), array("1","2","3"))){
			echo messagebox("error","","Não é possivel criar a natureza de operação para a operação <b>{$operacaonota->getdescricao()}</b> com o código <b>{$this->getnatoperacao()}</b> de saida.");
			return false;
		}elseif($operacaonota->gettipo() == "S" && !in_array(substr($this->getnatoperacao(),0,1), array("5","6","7"))){
			echo messagebox("error","","Não é possivel criar a natureza de operação para a operação <b>{$operacaonota->getdescricao()}</b> com o código <b>{$this->getnatoperacao()}</b> de entrada.");
			return false;
		}

		$this->connect();
		$this->con->start_transaction();
		if(is_null($object)){
			$object = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
		}
		if(parent::save()){
			$natoperacaoestab = objectbytable("natoperacaoestab", NULL, $this->con);
			$natoperacaoestab->setnatoperacao($this->getnatoperacao());
			$arr_natoperacaoestab = object_array($natoperacaoestab);
			foreach($arr_natoperacaoestab as $natoperacaoestab){
				if(!$natoperacaoestab->delete()){
					$this->con->rollback();
					return FALSE;
				}
			}
			foreach($this->arr_natoperacaoestab as $natoperacaoestab){
				$natoperacaoestab->setnatoperacao($this->getnatoperacao());
				if(strlen($natoperacaoestab->getcodconta()) > 0){
					if(!$natoperacaoestab->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}else{
			$this->con->rollback();
			return false;
		}
		$this->con->commit();
		return TRUE;
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return)){
			$natoperacaoestab = objectbytable("natoperacaoestab", NULL, $this->con);
			$natoperacaoestab->setnatoperacao($this->getnatoperacao());
			$this->arr_natoperacaoestab = object_array($natoperacaoestab);
		}
		return $return;
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getgeraestoque(){
		return $this->fields["geraestoque"];
	}

	function getgerafinanceiro(){
		return $this->fields["gerafinanceiro"];
	}

	function getgeraliquidado(){
		return $this->fields["geraliquidado"];
	}

	function getgerafiscal(){
		return $this->fields["gerafiscal"];
	}

	function getgeraicms(){
		return $this->fields["geraicms"];
	}

	function getgeraipi(){
		return $this->fields["geraipi"];
	}

	function getgerapiscofins(){
		return $this->fields["gerapiscofins"];
	}

	function getnatoperacaocp(){
		return $this->fields["natoperacaocp"];
	}

	function getcodccusto(){
		return $this->fields["codccusto"];
	}

	function getcodhistorico(){
		return $this->fields["codhistorico"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getcodcf(){
		return $this->fields["codcf"];
	}

	function getalteracfisento(){
		return $this->fields["alteracfisento"];
	}

	function getalteracficmssubst(){
		return $this->fields["alteracficmssubst"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getobservacaosimples(){
		return $this->fields["observacaosimples"];
	}

	function getnatoperacaosubst(){
		return $this->fields["natoperacaosubst"];
	}

	function getcodestabelecestoque(){
		return $this->fields["codestabelecestoque"];
	}

	function gettotnfigualbcicms(){
		return $this->fields["totnfigualbcicms"];
	}

	function getnatoperacaointer(){
		return $this->fields["natoperacaointer"];
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getsumicmstotalnf(){
		return $this->fields["sumicmstotalnf"];
	}

	function getatualizacustorep(){
		return $this->fields["atualizacustorep"];
	}

	function getatualizacustotab(){
		return $this->fields["atualizacustotab"];
	}

	function getatualizacustomedio(){
		return $this->fields["atualizacustomedio"];
	}

	function getvendaagenciada(){
		return $this->fields["vendaagenciada"];
	}

	function getcalcfretebaseicms(){
		return $this->fields["calcfretebaseicms"];
	}

	function getgeracsticms090(){
		return $this->fields["geracsticms090"];
	}

	function getcalcipibaseicms(){
		return $this->fields["calcipibaseicms"];
	}

	function getcalculaipipiscofins(){
		return $this->fields["calculaipipiscofins"];
	}

	function getgeraspedpiscofins(){
		return $this->fields["geraspedpiscofins"];
	}

	function getgeradtentrega(){
		return $this->fields["geradtentrega"];
	}

	function getcalculastvalorbruto(){
		return $this->fields["calculastvalorbruto"];
	}

	function getcalcicmsvalorbruto(){
		return $this->fields["calcicmsvalorbruto"];
	}

	function getimprimpostoibpt(){
		return $this->fields["imprimpostoibpt"];
	}

	function getexplodetransf(){
		return $this->fields["explodetransf"];
	}

	function getgerareal(){
		return $this->fields["gerareal"];
	}

	function getusartributacaoncm(){
		return $this->fields["usartributacaoncm"];
	}

	function getgeraspedipi(){
		return $this->fields["geraspedipi"];
	}

	function getcalcdifaloperinter(){
		return $this->fields["calcdifaloperinter"];
	}

	function getdestacaricmsretido(){
		return $this->fields["destacaricmsretido"];
	}

	function getaprovicmsprop(){
		return $this->fields["aprovicmsprop"];
	}

	function getdestacaricmsp(){
		return $this->fields["destacaricmsp"];
	}

	function getcstpiscofins(){
		return $this->fields["cstpiscofins"];
	}

	function getredbcsticmsprop(){
		return $this->fields["redbcsticmsprop"];
	}

	function getcalcipibasesticms(){
		return $this->fields["calcipibasesticms"];
	}

	function getdescicmspbasepiscofins(){
		return $this->fields["descicmspbasepiscofins"];
	}

	function getacrescimocomooutrasdespesas(){
		return $this->fields["acrescimocomooutrasdespesas"];
	}

	function getconsiderardesonerbasesticmsp(){
		return $this->fields["considerardesonerbasesticmsp"];
	}

	function getaproveitarcreditodebitoicms(){
		return $this->fields["aproveitarcreditodebitoicms"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function gethoralog(){
		return $this->fields["horalog"];
	}

	function getpiscofdesconto(){
		return $this->fields["piscofdesconto"];
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 120);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setgeraestoque($value){
		$this->fields["geraestoque"] = value_string($value, 1);
	}

	function setgerafinanceiro($value){
		$this->fields["gerafinanceiro"] = value_string($value, 1);
	}

	function setgeraliquidado($value){
		$this->fields["geraliquidado"] = value_string($value, 1);
	}

	function setgerafiscal($value){
		$this->fields["gerafiscal"] = value_string($value, 1);
	}

	function setgeraicms($value){
		$this->fields["geraicms"] = value_string($value, 1);
	}

	function setgeraipi($value){
		$this->fields["geraipi"] = value_string($value, 1);
	}

	function setgerapiscofins($value){
		$this->fields["gerapiscofins"] = value_string($value, 1);
	}

	function setnatoperacaocp($value){
		$this->fields["natoperacaocp"] = value_string($value, 9);
	}

	function setcodccusto($value){
		$this->fields["codccusto"] = value_numeric($value);
	}

	function setcodhistorico($value){
		$this->fields["codhistorico"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setcodcf($value){
		$this->fields["codcf"] = value_numeric($value);
	}

	function setalteracfisento($value){
		$this->fields["alteracfisento"] = value_string($value, 1);
	}

	function setalteracficmssubst($value){
		$this->fields["alteracficmssubst"] = value_string($value, 1);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 10000);
	}

	function setobservacaosimples($value){
		$this->fields["observacaosimples"] = value_string($value, 10000);
	}

	function setnatoperacaosubst($value){
		$this->fields["natoperacaosubst"] = value_string($value, 9);
	}

	function setcodestabelecestoque($value){
		$this->fields["codestabelecestoque"] = value_numeric($value);
	}

	function settotnfigualbcicms($value){
		$this->fields["totnfigualbcicms"] = value_string($value, 1);
	}

	function setnatoperacaointer($value){
		$this->fields["natoperacaointer"] = value_string($value, 9);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setsumicmstotalnf($value){
		$this->fields["sumicmstotalnf"] = value_string($value, 1);
	}

	function setatualizacustorep($value){
		$this->fields["atualizacustorep"] = value_string($value, 1);
	}

	function setatualizacustotab($value){
		$this->fields["atualizacustotab"] = value_string($value, 1);
	}

	function setatualizacustomedio($value){
		$this->fields["atualizacustomedio"] = value_string($value, 1);
	}

	function setatualizatributacao($value){
		$this->fields["atualizatributacao"] = value_string($value, 1);
	}

	function setvendaagenciada($value){
		$this->fields["vendaagenciada"] = value_string($value, 1);
	}

	function setcalcfretebaseicms($value){
		$this->fields["calcfretebaseicms"] = value_string($value, 1);
	}

	function setgeracsticms090($value){
		$this->fields["geracsticms090"] = value_string($value, 1);
	}

	function setcalcipibaseicms($value){
		$this->fields["calcipibaseicms"] = value_string($value, 1);
	}

	function setcalculaipipiscofins($value){
		$this->fields["calculaipipiscofins"] = value_string($value, 1);
	}

	function setgeraspedpiscofins($value){
		$this->fields["geraspedpiscofins"] = value_string($value, 1);
	}

	function setgeradtentrega($value){
		$this->fields["geradtentrega"] = value_string($value, 1);
	}

	function setcalculastvalorbruto($value){
		$this->fields["calculastvalorbruto"] = value_string($value, 1);
	}

	function setcalcicmsvalorbruto($value){
		$this->fields["calcicmsvalorbruto"] = value_string($value, 1);
	}

	function setimprimpostoibpt($value){
		$this->fields["imprimpostoibpt"] = value_string($value, 1);
	}

	function setexplodetransf($value){
		$this->fields["explodetransf"] = value_string($value, 1);
	}

	function setgerareal($value){
		$this->fields["gerareal"] = value_string($value, 1);
	}

	function setusartributacaoncm($value){
		$this->fields["usartributacaoncm"] = value_string($value, 1);
	}

	function setgeraspedipi($value){
		$this->fields["geraspedipi"] = value_string($value, 1);
	}

	function setcalcdifaloperinter($value){
		$this->fields["calcdifaloperinter"] = value_string($value, 1);
	}

	function setdestacaricmsretido($value){
		$this->fields["destacaricmsretido"] = value_string($value, 1);
	}

	function setaprovicmsprop($value){
		$this->fields["aprovicmsprop"] = value_string($value, 1);
	}

	function setdestacaricmsp($value){
		$this->fields["destacaricmsp"] = value_string($value, 1);
	}

	function setcstpiscofins($value){
		$this->fields["cstpiscofins"] = value_string($value, 2);
	}

	function setredbcsticmsprop($value){
		$this->fields["redbcsticmsprop"] = value_string($value, 1);
	}

	function setcalcipibasesticms($value){
		$this->fields["calcipibasesticms"] = value_string($value, 1);
	}

	function setdescicmspbasepiscofins($value){
		$this->fields["descicmspbasepiscofins"] = $value;
	}

	function setacrescimocomooutrasdespesas($value){
		$this->fields["acrescimocomooutrasdespesas"] = value_string($value, 1);
	}

	function setconsiderardesonerbasesticmsp($value){
		$this->fields["considerardesonerbasesticmsp"] = value_string($value, 1);

	}

	function setaproveitarcreditodebitoicms($value){
		$this->fields["aproveitarcreditodebitoicms"] = value_string($value, 1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}

	function setpiscofdesconto($value){
		$this->fields["piscofdesconto"] = value_string($value);
	}
}