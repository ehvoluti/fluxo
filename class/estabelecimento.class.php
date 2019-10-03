<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/temporary.class.php");

class Estabelecimento extends Cadastro{

	protected $flag_estabelecimentoiest = FALSE;
	private $arr_estabelecimentoiest;

	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "estabelecimento";
		$this->primarykey = array("codestabelec");
		$this->newrelation("estabelecimento", "codemitente", "emitente", "codemitente");
		$this->newrelation("estabelecimento", "codcidade", "cidade", "codcidade");
		$this->newrelation("cidade", "uf", "estado", "uf");
		$this->setcodestabelec($codestabelec);
		if($this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_estabelecimentoiest($value){
		if(is_bool($value)){
			$this->flag_estabelecimentoiest = $value;
		}
	}

	function regime_simples(){
		return in_array($this->getregimetributario(), array("1"));
	}

	function delete(){
		$this->connect();
		$this->con->start_transaction();
		if($this->exists()){
			$produtoestab = objectbytable("produtoestab", NULL, $this->con);
			$produtoestab->setcodestabelec($this->getcodestabelec());
			$arr_produtoestab = object_array($produtoestab);
			foreach($arr_produtoestab as $produtoestab){
				if($produtoestab->getsldatual() > 0){
					$produto = objectbytable("produto", $produtoestab->getcodproduto(), $this->con);
					$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel exclu&iacute;r o estabelecimento, pois existe estoque positivo no produto ".$produtoestab->getcodproduto()." (".$produto->getdescricaofiscal().").";
					$this->con->rollback();
					return FALSE;
				}elseif(!$produtoestab->delete()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}
		if(parent::delete()){
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function save($object = null){
		$param_cadastro_uniqcnpjestab = param("CADASTRO", "UNIQCNPJESTAB", $this->connect());
		$estabelecimento = objectbytable("estabelecimento", NULL, $this->connect());
		$estabelecimento->setcpfcnpj($this->getcpfcnpj());
		$estab = object_array($estabelecimento);

		foreach($estab as $e){
			if($e->getcodestabelec() == $this->getcodestabelec()){
				if(parent::save()){
					if(!$this->save_estabelecimentoiest()){
						$this->con->rollback();
						return FALSE;
					}
				}else{
					$this->con->rollback();
					return FALSE;
				}
				return TRUE;
			}
		}

		if(!empty($estab) && $param_cadastro_uniqcnpjestab == "S"){
			$_SESSION["ERROR"] = "O campo <b>cpfcnpj</b> não pode ter seu valor repetido na tabela <b>estabelecimento</b>.";
			return FALSE;
		}
		$this->con->start_transaction();
		if(parent::save($object)){
			if(!$this->save_estabelecimentoiest()){
				$this->con->rollback();
				return FALSE;
			}
		}else{
			$this->con->rollback();
			return FALSE;
		}

		$this->con->commit();
		return TRUE;

	}

	private function save_estabelecimentoiest(){
		if($this->flag_estabelecimentoiest){
			//carrega todas as inscricoes do banco e deleta todas
			$estabelecimentoiest = objectbytable("estabelecimentoiest", NULL, $this->con);
			$estabelecimentoiest->setcodestabelec($this->getcodestabelec());
			$arr_estabelecimentoiest = object_array($estabelecimentoiest);
			foreach($arr_estabelecimentoiest as $estabelecimentoiest){
				if(!$estabelecimentoiest->delete()){
					return FALSE;
				}
			}

			//salva no banco de dados as inscricoes validas para o estabelecimento
			foreach($this->arr_estabelecimentoiest as $estabelecimentoiest){
				$estabelecimentoiest->setcodestabelec($this->getcodestabelec());
				if(!$estabelecimentoiest->save()){
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && sizeof($return) == 1){
			if($this->flag_estabelecimentoiest){
				$estabelecimentoiest = objectbytable("estabelecimentoiest", NULL, $this->con);
				$estabelecimentoiest->setcodestabelec($this->getcodestabelec());
				$this->arr_estabelecimentoiest = object_array($estabelecimentoiest);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		if($this->flag_estabelecimentoiest){
			$temporary = new Temporary("estabelecimentoiest",FALSE);
			for($i = 0; $i < $temporary->length(); $i++){
				$estabelecimentoiest = objectbytable("estabelecimentoiest", NULL, $this->con);
				$estabelecimentoiest->setcodestabelec($temporary->getvalue($i, "codestabelec"));
				$estabelecimentoiest->setuf($temporary->getvalue($i, "uf"));
				$estabelecimentoiest->setiest($temporary->getvalue($i, "iest"));
				$this->arr_estabelecimentoiest[] = $estabelecimentoiest;
			}
		}
	}

	function setfieldvalues(){
		$html .= parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); "; // Filtra a cidade

		$temporary = new Temporary("estabelecimentoiest",TRUE);
		$temporary->setcolumns(array("codestabeleciest","codestabelec","uf","iest"));
		foreach($this->arr_estabelecimentoiest as $estabelecimentoiest){
			$temporary->append();
			$temporary->setvalue("last", "codestabelec", $estabelecimentoiest->getcodestabelec());
			$temporary->setvalue("last", "uf", $estabelecimentoiest->getuf());
			$temporary->setvalue("last", "iest", $estabelecimentoiest->getiest());
		}
		$temporary->save();

		return $html;
	}

	function getcodemitente(){
		return $this->fields["codemitente"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
	}

	function getendereco(){
		return $this->fields["endereco"];
	}

	function getbairro(){
		return $this->fields["bairro"];
	}

	function getcep(){
		return $this->fields["cep"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getcodcidade(){
		return $this->fields["codcidade"];
	}

	function getfone1(){
		return $this->fields["fone1"];
	}

	function getfone2(){
		return $this->fields["fone2"];
	}

	function getfax(){
		return $this->fields["fax"];
	}

	function getcpfcnpj(){
		return $this->fields["cpfcnpj"];
	}

	function getrgie(){
		return $this->fields["rgie"];
	}

	function gettppessoa(){
		return $this->fields["tppessoa"];
	}

	function getcontato(){
		return $this->fields["contato"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getpathedi(){
		return $this->fields["pathedi"];
	}

	function getdtultfecto($format = FALSE){
		return ($format ? convert_date($this->fields["dtultfecto"], "Y-m-d", "d/m/Y") : $this->fields["dtultfecto"]);
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getbalancadv(){
		return $this->fields["balancadv"];
	}

	function getfrentedv(){
		return $this->fields["frentedv"];
	}

	function getcodestabirma($format = FALSE){
		return ($format ? number_format($this->fields["codestabirma"], 2, ",", "") : $this->fields["codestabirma"]);
	}

	function getrecebecoletor(){
		return $this->fields["recebecoletor"];
	}

	function getcodbalanca(){
		return $this->fields["codbalanca"];
	}

	function getcodfrentecaixa(){
		return $this->fields["codfrentecaixa"];
	}

	function getenquadfiscal(){
		return $this->fields["enquadfiscal"];
	}

	function getaliqicmssimples($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmssimples"], 2, ",", "") : $this->fields["aliqicmssimples"]);
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"], 2, ",", "") : $this->fields["aliqpis"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"], 2, ",", "") : $this->fields["aliqcofins"]);
	}

	function getdirxmlnfe(){
		return $this->fields["dirxmlnfe"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getnumero(){
		return $this->fields["numero"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function getdespoperacional($format = FALSE){
		return ($format ? number_format($this->fields["despoperacional"], 2, ",", "") : $this->fields["despoperacional"]);
	}

	function getregimetributario(){
		return $this->fields["regimetributario"];
	}

	function getdirleituraonline(){
		return $this->fields["dirleituraonline"];
	}

	function getnatoperacaonfcupom(){
		return $this->fields["natoperacaonfcupom"];
	}

	function getcodcontabilidade(){
		return $this->fields["codcontabilidade"];
	}

	function getperfil(){
		return $this->fields["perfil"];
	}

	function getdirarqfiscal(){
		return $this->fields["dirarqfiscal"];
	}

	function getdircoletor(){
		return $this->fields["dircoletor"];
	}

	function getpermitecredicms(){
		return $this->fields["permitecredicms"];
	}

	function getdirremessabanco(){
		return $this->fields["dirremessabanco"];
	}

	function getdirretornobanco(){
		return $this->fields["dirretornobanco"];
	}

	function gettaxajuromensal($format = FALSE){
		return ($format ? number_format($this->fields["taxajuromensal"], 2, ",", "") : $this->fields["taxajuromensal"]);
	}

	function getdircontabil(){
		return $this->fields["dircontabil"];
	}

	function getdirimprecibo(){
		return $this->fields["dirimprecibo"];
	}

	function getdirpdvexp(){
		return $this->fields["dirpdvexp"];
	}

	function getdirpdvimp(){
		return $this->fields["dirpdvimp"];
	}

	function getleituraonline(){
		return $this->fields["leituraonline"];
	}

	function getnumrecibo(){
		return $this->fields["numrecibo"];
	}

	function getdirimportnfe(){
		return $this->fields["dirimportnfe"];
	}

	function getdirimpcarne(){
		return $this->fields["dirimpcarne"];
	}

	function getdirimpcheque(){
		return $this->fields["dirimpcheque"];
	}

	function gettipoatividade(){
		return $this->fields["tipoatividade"];
	}

	function gethost(){
		return $this->fields["host"];
	}

	function getport(){
		return $this->fields["port"];
	}

	function getambiente(){
		return $this->fields["ambiente"];
	}

	function geturlxmlnfe(){
		return $this->fields["urlxmlnfe"];
	}

	function getnomeschema(){
		return $this->fields["nomeschema"];
	}

	function getnomecertificado(){
		return $this->fields["nomecertificado"];
	}

	function getdircertificados(){
		return $this->fields["dircertificados"];
	}

	function getsenhachaveprivada(){
		return $this->fields["senhachaveprivada"];
	}

	function getsenhadescriptacao(){
		return $this->fields["senhadescriptacao"];
	}

	function getlocallogotipo(){
		return $this->fields["locallogotipo"];
	}

	function getcodmodeloemail(){
		return $this->fields["codmodeloemail"];
	}

	function gettimeoutconsulta(){
		return $this->fields["timeoutconsulta"];
	}

	function geturlbase(){
		return $this->fields["urlbase"];
	}

	function getarquivoxsd(){
		return $this->fields["arquivoxsd"];
	}

	function getbeservidor(){
		return $this->fields["beservidor"];
	}

	function getbeusuario(){
		return $this->fields["beusuario"];
	}

	function getbesenha(){
		return $this->fields["besenha"];
	}

	function getbeempresa(){
		return $this->fields["beempresa"];
	}

	function getbelocal(){
		return $this->fields["belocal"];
	}

	function getequipamentofiscal(){
		return $this->fields["equipamentofiscal"];
	}

	function getcodestabelecfiscal(){
		return $this->fields["codestabelecfiscal"];
	}

	function getcodestabelecfinan(){
		return $this->fields["codestabelecfinan"];
	}

	function getcodigocielo(){
		return $this->fields["codigocielo"];
	}

	function getambientenfe(){
		return $this->fields["ambientenfe"];
	}

	function getinscmunicipal(){
		return $this->fields["inscmunicipal"];
	}

	function getusuarionfse(){
		return $this->fields["usuarionfse"];
	}

	function getsenhanfse(){
		return $this->fields["senhanfse"];
	}

	function getambientenfse(){
		return $this->fields["ambientenfse"];
	}

	function getnomegrupo(){
		return $this->fields["nomegrupo"];
	}

	function getversaoapi(){
		return $this->fields["versaoapi"];
	}

	function getservidorsmtp(){
		return $this->fields["servidorsmtp"];
	}

	function getusuarioemail(){
		return $this->fields["usuarioemail"];
	}

	function getnomeremetente(){
		return $this->fields["nomeremetente"];
	}

	function getsenhaemail(){
		return $this->fields["senhaemail"];
	}

	function getporta(){
		return $this->fields["porta"];
	}

	function gettipoautenticacao(){
		return $this->fields["tipoautenticacao"];
	}

	function getultnsu(){
		return $this->fields["ultnsu"];
	}

	function getmaxnsu(){
		return $this->fields["maxnsu"];
	}

	function getdtultdistmdfe($format = FALSE){
		return ($format ? convert_date($this->fields["dtultdistmdfe"], "Y-m-d", "d/m/Y") : $this->fields["dtultdistmdfe"]);
	}

	function getcor(){
		return $this->fields["cor"];
	}

	function getnomereduz(){
		return $this->fields["nomereduz"];
	}

	function setcodemitente($value){
		$this->fields["codemitente"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value, 40);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value, 40);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value, 40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value, 40);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value, 9);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value, 2);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value, 20);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value, 20);
	}

	function setfax($value){
		$this->fields["fax"] = value_string($value, 20);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value, 18);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value, 20);
	}

	function settppessoa($value){
		$this->fields["tppessoa"] = value_string($value, 1);
	}

	function setcontato($value){
		$this->fields["contato"] = value_string($value, 60);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value, 60);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setpathedi($value){
		$this->fields["pathedi"] = value_string($value, 80);
	}

	function setdtultfecto($value){
		$this->fields["dtultfecto"] = value_date($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setbalancadv($value){
		$this->fields["balancadv"] = value_string($value, 1);
	}

	function setfrentedv($value){
		$this->fields["frentedv"] = value_string($value, 1);
	}

	function setcodestabirma($value){
		$this->fields["codestabirma"] = value_numeric($value);
	}

	function setrecebecoletor($value){
		$this->fields["recebecoletor"] = value_string($value, 1);
	}

	function setcodbalanca($value){
		$this->fields["codbalanca"] = value_numeric($value);
	}

	function setcodfrentecaixa($value){
		$this->fields["codfrentecaixa"] = value_numeric($value);
	}

	function setenquadfiscal($value){
		$this->fields["enquadfiscal"] = value_string($value, 1);
	}

	function setaliqicmssimples($value){
		$this->fields["aliqicmssimples"] = value_numeric($value);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
	}

	function setdirxmlnfe($value){
		$this->fields["dirxmlnfe"] = value_string($value, 200);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setnumero($value){
		$this->fields["numero"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 40);
	}

	function setdespoperacional($value){
		$this->fields["despoperacional"] = value_numeric($value);
	}

	function setregimetributario($value){
		$this->fields["regimetributario"] = value_string($value, 1);
	}

	function setdirleituraonline($value){
		$this->fields["dirleituraonline"] = value_string($value, 200);
	}

	function setnatoperacaonfcupom($value){
		$this->fields["natoperacaonfcupom"] = value_string($value, 9);
	}

	function setcodcontabilidade($value){
		$this->fields["codcontabilidade"] = value_numeric($value);
	}

	function setperfil($value){
		$this->fields["perfil"] = value_string($value, 1);
	}

	function setdirarqfiscal($value){
		$this->fields["dirarqfiscal"] = value_string($value, 200);
	}

	function setdircoletor($value){
		$this->fields["dircoletor"] = value_string($value, 200);
	}

	function setpermitecredicms($value){
		$this->fields["permitecredicms"] = value_string($value, 1);
	}

	function setdirremessabanco($value){
		$this->fields["dirremessabanco"] = value_string($value, 250);
	}

	function setdirretornobanco($value){
		$this->fields["dirretornobanco"] = value_string($value, 250);
	}

	function settaxajuromensal($value){
		$this->fields["taxajuromensal"] = value_numeric($value);
	}

	function setdircontabil($value){
		$this->fields["dircontabil"] = value_string($value, 200);
	}

	function setdirpdvexp($value){
		$this->fields["dirpdvexp"] = value_string($value, 200);
	}

	function setdirimprecibo($value){
		$this->fields["dirimprecibo"] = value_string($value, 200);
	}

	function setdirpdvimp($value){
		$this->fields["dirpdvimp"] = value_string($value, 200);
	}

	function setleituraonline($value){
		$this->fields["leituraonline"] = value_string($value, 1);
	}

	function setnumrecibo($value){
		$this->fields["numrecibo"] = value_numeric($value);
	}

	function setdirimportnfe($value){
		$this->fields["dirimportnfe"] = value_string($value, 200);
	}

	function setdirimpcarne($value){
		$this->fields["dirimpcarne"] = value_string($value, 200);
	}

	function setdirimpcheque($value){
		$this->fields["dirimpcheque"] = value_string($value, 200);
	}

	function settipoatividade($value){
		$this->fields["tipoatividade"] = value_string($value, 1);
	}

	function sethost($value){
		$this->fields["host"] = value_string($value);
	}

	function setport($value){
		$this->fields["port"] = value_numeric($value);
	}

	function setambiente($value){
		$this->fields["ambiente"] = value_string($value, 1);
	}

	function seturlxmlnfe($value){
		$this->fields["urlxmlnfe"] = value_string($value, 100);
	}

	function setnomeschema($value){
		$this->fields["nomeschema"] = value_string($value, 30);
	}

	function setnomecertificado($value){
		$this->fields["nomecertificado"] = value_string($value, 100);
	}

	function setdircertificados($value){
		$this->fields["dircertificados"] = value_string($value,200);
	}

	function setsenhachaveprivada($value){
		$this->fields["senhachaveprivada"] = value_string($value, 100);
	}

	function setsenhadescriptacao($value){
		$this->fields["senhadescriptacao"] = value_string($value, 100);
	}

	function setlocallogotipo($value){
		$this->fields["locallogotipo"] = value_string($value, 100);
	}

	function setcodmodeloemail($value){
		$this->fields["codmodeloemail"] = value_numeric($value);
	}

	function seturlbase($value){
		$this->fields["urlbase"] = value_string($value);
	}

	function settimeoutconsulta($value){
		$this->fields["timeoutconsulta"] = value_numeric($value);
	}

	function setarquivoxsd($value){
		$this->fields["arquivoxsd"] = value_string($value);
	}

	function setbeservidor($value){
		$this->fields["beservidor"] = value_string($value, 100);
	}

	function setbeusuario($value){
		$this->fields["beusuario"] = value_string($value, 100);
	}

	function setbesenha($value){
		$this->fields["besenha"] = value_string($value, 100);
	}

	function setbeempresa($value){
		$this->fields["beempresa"] = value_numeric($value);
	}

	function setbelocal($value){
		$this->fields["belocal"] = value_numeric($value);
	}

	function setequipamentofiscal($value){
		$this->fields["equipamentofiscal"] = value_string($value, 3);
	}

	function setcodestabelecfiscal($value){
		$this->fields["codestabelecfiscal"] = value_numeric($value);
	}

	function setcodestabelecfinan($value){
		$this->fields["codestabelecfinan"] = value_numeric($value);
	}

	function setcodigocielo($value){
		$this->fields["codigocielo"] = value_string($value, 1);
	}

	function setambientenfe($value){
		$this->fields["ambientenfe"] = value_string($value, 1);
	}

	function setinscmunicipal($value){
		$this->fields["inscmunicipal"] = value_string($value, 30);
	}

	function setusuarionfse($value){
		$this->fields["usuarionfse"] = value_string($value, 60);
	}

	function setsenhanfse($value){
		$this->fields["senhanfse"] = value_string($value, 20);
	}

	function setambientenfse($value){
		$this->fields["ambientenfse"] = value_string($value, 1);
	}

	function setnomegrupo($value){
		$this->fields["nomegrupo"] = value_string($value, 40);
	}

	function setversaoapi($value){
		$this->fields["versaoapi"] = value_string($value, 10);
	}

	function setservidorsmtp($value){
		$this->fields["servidorsmtp"] = value_string($value, 100);
	}

	function setusuarioemail($value){
		$this->fields["usuarioemail"] = value_string($value, 100);
	}

	function setnomeremetente($value){
		$this->fields["nomeremetente"] = value_string($value, 100);
	}

	function setsenhaemail($value){
		$this->fields["senhaemail"] = value_string($value, 50);
	}

	function setporta($value){
		$this->fields["porta"] = value_string($value, 10);
	}

	function settipoautenticacao($value){
		$this->fields["tipoautenticacao"] = value_string($value, 20);
	}

	function setultnsu($value){
		$this->fields["ultnsu"] = value_numeric($value);
	}

	function setmaxnsu($value){
		$this->fields["maxnsu"] = value_numeric($value);
	}

	function setdtultdistmdfe($value){
		$this->fields["dtultdistmdfe"] = value_date($value);
	}

	function setcor($value){
		$this->fields["cor"] = value_string($value, 20);
	}

	function setnomereduz($value){
		$this->fields["nomereduz"] = value_string($value, 10);
	}
}
