<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/temporary.class.php");

class Fornecedor extends Cadastro{

	public $arr_fornecedorcompl;
	public $arr_fornecestab;
	protected $flag_fornecedorcompl = FALSE;
	protected $flag_fornecestab = FALSE;

	function __construct($codfornec = NULL){
		parent::__construct();
		$this->table = "fornecedor";
		$this->primarykey = "codfornec";
		$this->newrelation("fornecedor", "codcidade", "cidade", "codcidade");
		$this->newrelation("cidade", "uf", "estado", "uf");
		$this->setcodfornec($codfornec);
		if($this->getcodfornec() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_fornecedorcompl($value){
		if(is_bool($value)){
			$this->flag_fornecedorcompl = $value;
		}
	}

	function flag_fornecestab($value){
		if(is_bool($value)){
			$this->flag_fornecestab = $value;
		}
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		// Complemento de cadastro
		$this->arr_fornecedorcompl = array();
		$temporary = new Temporary("fornecedor_fornecedorcompl", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$fornecedorcompl = objectbytable("fornecedorcompl", NULL, $this->con);
			$fornecedorcompl->setcodfornec($this->getcodfornec());
			$fornecedorcompl->setcodcomplcadastro($temporary->getvalue($i, "codcomplcadastro"));
			$fornecedorcompl->setvalor($temporary->getvalue($i, "valor"));
			$this->arr_fornecedorcompl[] = $fornecedorcompl;
		}

		// Fornecedor por estabelecimento
		$this->arr_fornecestab = array();
		$temporary = new Temporary("fornecedor_fornecestab", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$fornecestab = objectbytable("fornecestab", NULL, $this->con);
			$fornecestab->setcodestabelec($temporary->getvalue($i, "codestabelec"));
			$fornecestab->setcodfornec($this->getcodfornec());
			$fornecestab->setfreqvisita($temporary->getvalue($i, "freqvisita"));
			$fornecestab->setdiasentrega($temporary->getvalue($i, "diasentrega"));
			$fornecestab->setdisponivel($temporary->getvalue($i, "disponivel"));
			$this->arr_fornecestab[] = $fornecestab;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); "; // Filtra a cidade
		$html .= "filterchild('codcatlancto','".$this->getcodsubcatlancto()."'); "; // Filtra subcategoria de lancamento
		$html .= "settipopessoa(\"cpfcnpj\",$(\"#tppessoa\").val(),$(\"#cpfcnpj\")); "; // Aplica a mascara dependendo do tipo de pessoa
		$html .= "settipopessoa(\"rgie\",$(\"#tppessoa\").val(),$(\"#rgie\")); "; // Aplica a mascara dependendo do tipo de pessoa
		$html .= "$('#cpfcnpj').val('".$this->getcpfcnpj()."'); "; // Joga o valor do CPF/CNPJ
		$html .= "$('#rgie').val('".$this->getrgie()."'); "; // Joga o valor do RG/IE
		// Complemento de cadastro
		$temporary = new Temporary("fornecedor_fornecedorcompl", TRUE);
		$temporary->setcolumns(array("codcomplcadastro", "valor"));
		foreach($this->arr_fornecedorcompl as $fornecedorcompl){
			$temporary->append();
			$temporary->setvalue("last", "codcomplcadastro", $fornecedorcompl->getcodcomplcadastro());
			$temporary->setvalue("last", "valor", utf8_encode($fornecedorcompl->getvalor()));
		}
		$temporary->save();
		$html .= "fornecedorcompl_carregar(); ";

		// Fornecedor por estabelecimento
		$temporary = new Temporary("fornecedor_fornecestab", TRUE);
		$temporary->setcolumns(array("codestabelec", "freqvisita", "diasentrega", "disponivel"));
		foreach($this->arr_fornecestab as $fornecestab){
			$temporary->append();
			$temporary->setvalue("last", "codestabelec", $fornecestab->getcodestabelec());
			$temporary->setvalue("last", "freqvisita", $fornecestab->getfreqvisita());
			$temporary->setvalue("last", "diasentrega", $fornecestab->getdiasentrega());
			$temporary->setvalue("last", "disponivel", $fornecestab->getdisponivel());
		}
		$temporary->save();
		$html .= "fornecestab_desenhar(); ";

		return $html;
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();
		$object = objectbytable("fornecedor", $this->getcodfornec(), $this->con);
		if(parent::save($object)){
			// Gravar dados do complemento
			if($this->flag_fornecedorcompl){
				$fornecedorcompl = objectbytable("fornecedorcompl", NULL, $this->con);
				$fornecedorcompl->setcodfornec($this->getcodfornec());
				$arr_fornecedorcompl = object_array($fornecedorcompl);
				foreach($arr_fornecedorcompl as $fornecedorcompl){
					if(!$fornecedorcompl->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->arr_fornecedorcompl as $fornecedorcompl){
					$fornecedorcompl->setcodfornec($this->getcodfornec());
					if(!$fornecedorcompl->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			// Gravar dados do estabelecimento com o fornecedor
			if($this->flag_fornecestab){
				$fornecestab = objectbytable("fornecestab", NULL, $this->con);
				$fornecestab->setcodfornec($this->getcodfornec());
				$arr_fornecestab = object_array($fornecestab);
				foreach($arr_fornecestab as $fornecestab){
					if(!$fornecestab->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->arr_fornecestab as $fornecestab){
					$fornecestab->setcodfornec($this->getcodfornec());
					if(!$fornecestab->save()){
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
		if($return !== FALSE && !is_array($return)){
			// Complemento de cadastro
			if($this->flag_fornecedorcompl){
				$fornecedorcompl = objectbytable("fornecedorcompl", NULL, $this->con);
				$fornecedorcompl->setcodfornec($this->getcodfornec());
				$this->arr_fornecedorcompl = object_array($fornecedorcompl);
			}
			// Fornecedor por estabelecimento
			if($this->flag_fornecestab){
				$fornecestab = objectbytable("fornecestab", NULL, $this->con);
				$fornecestab->setcodfornec($this->getcodfornec());
				$this->arr_fornecestab = object_array($fornecestab);
			}
		}
		return $return;
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
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

	function getcodcidade(){
		return $this->fields["codcidade"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getcontato1(){
		return $this->fields["contato1"];
	}

	function getfone1(){
		return $this->fields["fone1"];
	}

	function getfone2(){
		return $this->fields["fone2"];
	}

	function getfone3(){
		return $this->fields["fone3"];
	}

	function getsite(){
		return $this->fields["site"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function gettppessoa(){
		return $this->fields["tppessoa"];
	}

	function getcpfcnpj(){
		return $this->fields["cpfcnpj"];
	}

	function getrgie(){
		return $this->fields["rgie"];
	}

	function getcodclassif(){
		return $this->fields["codclassif"];
	}

	function getcodatividade($format = FALSE){
		return ($format ? number_format($this->fields["codatividade"], 0, ",", "") : $this->fields["codatividade"]);
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getagencia(){
		return $this->fields["agencia"];
	}

	function getcontacorrente(){
		return $this->fields["contacorrente"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getcontato2(){
		return $this->fields["contato2"];
	}

	function getcontato3(){
		return $this->fields["contato3"];
	}

	function getcargo1(){
		return $this->fields["cargo1"];
	}

	function getemail1(){
		return $this->fields["email1"];
	}

	function getcargo2(){
		return $this->fields["cargo2"];
	}

	function getemail2(){
		return $this->fields["email2"];
	}

	function getcargo3(){
		return $this->fields["cargo3"];
	}

	function getemail3(){
		return $this->fields["email3"];
	}

	function getfone(){
		return $this->fields["fone"];
	}

	function getfax(){
		return $this->fields["fax"];
	}

	function getcodcomprador(){
		return $this->fields["codcomprador"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getcodtransp(){
		return $this->fields["codtransp"];
	}

	function getcodfamfornec(){
		return $this->fields["codfamfornec"];
	}

	function gettolera($format = FALSE){
		return ($format ? number_format($this->fields["tolera"], 2, ",", "") : $this->fields["tolera"]);
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getdesconto($format = FALSE){
		return ($format ? number_format($this->fields["desconto"], 2, ",", "") : $this->fields["desconto"]);
	}

	function getfabricante(){
		return $this->fields["fabricante"];
	}

	function getnumero(){
		return $this->fields["numero"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getmodosubsttrib(){
		return $this->fields["modosubsttrib"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getsuframa(){
		return $this->fields["suframa"];
	}

	function getdatainclusao($format = FALSE){
		return ($format ? convert_date($this->fields["datainclusao"], "Y-m-d", "d/m/Y") : $this->fields["datainclusao"]);
	}

	function gettipocompra(){
		return $this->fields["tipocompra"];
	}

	function getfaturamentominimo($format = FALSE){
		return ($format ? number_format($this->fields["faturamentominimo"], 2, ",", "") : $this->fields["faturamentominimo"]);
	}

	function getcodpais(){
		return $this->fields["codpais"];
	}

	function getsenhacotacao(){
		return $this->fields["senhacotacao"];
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getatualizatributacao(){
		return $this->fields["atualizatributacao"];
	}

	function gettipoqtdenfe(){
		return $this->fields["tipoqtdenfe"];
	}

	function getdestacaipisubst(){
		return $this->fields["destacaipisubst"];
	}

	function getcustobonificado(){
		return $this->fields["custobonificado"];
	}

	function getcontribuinteicms(){
		return $this->fields["contribuinteicms"];
	}

	function getinscmunicipal(){
		return $this->fields["inscmunicipal"];
	}

	function gettipodescdesoneracao(){
		return $this->fields["tipodescdesoneracao"];
	}

	function getdesoneracaocustoliquido(){
		return $this->fields["desoneracaocustoliquido"];
	}

	function getcnpjboleto(){
		return $this->fields["cnpjboleto"];
	}

	function getfornecprincipal(){
		return $this->fields["fornecprincipal"];
	}	

	function getcodfornec_vinculado(){
		return $this->fields["codfornec_vinculado"];
	}	
	
	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
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
		$this->fields["bairro"] = value_string($value, 30);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value, 9);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value, 2);
	}

	function setcontato1($value){
		$this->fields["contato1"] = value_string($value, 40);
	}

	function setfone1($value){
		$this->fields["fone1"] = value_string($value, 20);
	}

	function setfone2($value){
		$this->fields["fone2"] = value_string($value, 20);
	}

	function setfone3($value){
		$this->fields["fone3"] = value_string($value, 20);
	}

	function setsite($value){
		$this->fields["site"] = value_string($value, 60);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value, 80);
	}

	function settppessoa($value){
		$this->fields["tppessoa"] = value_string($value, 1);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value, 20);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value, 20);
	}

	function setcodclassif($value){
		$this->fields["codclassif"] = value_numeric($value);
	}

	function setcodatividade($value){
		$this->fields["codatividade"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setagencia($value){
		$this->fields["agencia"] = value_string($value, 20);
	}

	function setcontacorrente($value){
		$this->fields["contacorrente"] = value_string($value, 20);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 500);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setcontato2($value){
		$this->fields["contato2"] = value_string($value, 40);
	}

	function setcontato3($value){
		$this->fields["contato3"] = value_string($value, 40);
	}

	function setcargo1($value){
		$this->fields["cargo1"] = value_string($value, 30);
	}

	function setemail1($value){
		$this->fields["email1"] = value_string($value, 60);
	}

	function setcargo2($value){
		$this->fields["cargo2"] = value_string($value, 30);
	}

	function setemail2($value){
		$this->fields["email2"] = value_string($value, 60);
	}

	function setcargo3($value){
		$this->fields["cargo3"] = value_string($value, 30);
	}

	function setemail3($value){
		$this->fields["email3"] = value_string($value, 60);
	}

	function setfone($value){
		$this->fields["fone"] = value_string($value, 20);
	}

	function setfax($value){
		$this->fields["fax"] = value_string($value, 20);
	}

	function setcodcomprador($value){
		$this->fields["codcomprador"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setcodtransp($value){
		$this->fields["codtransp"] = value_numeric($value);
	}

	function setcodfamfornec($value){
		$this->fields["codfamfornec"] = value_string($value, 10);
	}

	function settolera($value){
		$this->fields["tolera"] = value_numeric($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setdesconto($value){
		$this->fields["desconto"] = value_numeric($value);
	}

	function setfabricante($value){
		$this->fields["fabricante"] = value_string($value, 1);
	}

	function setnumero($value){
		$this->fields["numero"] = value_string($value, 20);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 40);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setmodosubsttrib($value){
		$this->fields["modosubsttrib"] = value_string($value, 1);
	}

	function setsuframa($value){
		$this->fields["suframa"] = value_string($value, 9);
	}

	function setdatainclusao($value){
		$this->fields["datainclusao"] = value_date($value);
	}

	function settipocompra($value){
		$this->fields["tipocompra"] = value_string($value, 1);
	}

	function setfaturamentominimo($value){
		$this->fields["faturamentominimo"] = value_numeric($value);
	}

	function setcodpais($value){
		$this->fields["codpais"] = value_string($value, 5);
	}

	function setsenhacotacao($value){
		$this->fields["senhacotacao"] = value_string($value, 20);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setatualizatributacao($value){
		$this->fields["atualizatributacao"] = value_string($value, 1);
	}

	function settipoqtdenfe($value){
		$this->fields["tipoqtdenfe"] = value_string($value, 1);
	}

	function setdestacaipisubst($value){
		$this->fields["destacaipisubst"] = value_string($value);
	}

	function setcustobonificado($value){
		$this->fields["custobonificado"] = value_string($value);
	}
	function setcontribuinteicms($value){
		$this->fields["contribuinteicms"] = value_string($value, 1);
	}

	function setinscmunicipal($value){
		$this->fields["inscmunicipal"] = value_string($value, 30);
	}

	function settipodescdesoneracao($value){
		$this->fields["tipodescdesoneracao"] = value_string($value, 1);
	}

	function setdesoneracaocustoliquido($value){
		$this->fields["desoneracaocustoliquido"] = value_string($value, 1);
	}

	function setcnpjboleto($value){
		$this->fields["cnpjboleto"] = value_string($value, 20);
	}

	function setfornecprincipal($value){
		$this->fields["fornecprincipal"] = value_string($value, 1);
	}
	
	function setcodfornec_vinculado($value){
		$this->fields["codfornec_vinculado"] = value_numeric($value);
	}	
}


