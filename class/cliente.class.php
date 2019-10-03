<?php
require_file("class/cadastro.class.php");
require_file("class/temporary.class.php");

class Cliente extends Cadastro{
    public $arr_clientecompl;
	public $arr_clienteestab;
	public $arr_clienteinteresse;
    protected $flag_clientecompl = FALSE;
	protected $flag_clienteestab = FALSE;
	protected $flag_clienteinteresse = FALSE;

	function __construct($codcliente = NULL){
		parent::__construct();
		$this->table = "cliente";
		$this->primarykey = "codcliente";
		$this->newrelation("cliente","codcliente","clienteestab","codcliente");
		$this->setcodcliente($codcliente);
		if($this->getcodcliente() != NULL){
				$this->searchbyobject();
		}
	}

    function flag_clientecompl($value){
		if(is_bool($value)){
			$this->flag_clientecompl = $value;
		}
	}

	function flag_clienteestab($value){
		if(is_bool($value)){
			$this->flag_clienteestab = $value;
		}
	}

	function flag_clienteinteresse($value){
		if(is_bool($value)){
			$this->flag_clienteinteresse = $value;
		}
	}

	function getfieldvalues(){
		parent::getfieldvalues();

        // Complemento de cadastro
        $this->arr_clientecompl = array();
        $temporary = new Temporary("cliente_clientecompl",FALSE);
        for($i = 0; $i < $temporary->length(); $i++){
            $clientecompl = objectbytable("clientecompl",NULL,$this->con);
            $clientecompl->setcodcliente($this->getcodcliente());
            $clientecompl->setcodcomplcadastro($temporary->getvalue($i,"codcomplcadastro"));
            $clientecompl->setvalor($temporary->getvalue($i,"valor"));
            $this->arr_clientecompl[] = $clientecompl;
        }

		// Mix por estabelecimento
		$this->arr_clienteestab = array();
		$temporary = new Temporary("cliente_clienteestab",FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$clienteestab = objectbytable("clienteestab",NULL,$this->con);
			$clienteestab->setcodcliente($this->getcodcliente());
			$clienteestab->setcodestabelec($temporary->getvalue($i,"codestabelec"));
			$this->arr_clienteestab[] = $clienteestab;
		}

		// Interesses
		$this->arr_clienteinteresse = array();
		$temporary = new Temporary("cliente_clienteinteresse",FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$clienteinteresse = objectbytable("clienteinteresse",NULL,$this->con);
			$clienteinteresse->setcodcliente($this->getcodcliente());
			$clienteinteresse->setcodinteresse($temporary->getvalue($i,"codinteresse"));
			$this->arr_clienteinteresse[] = $clienteinteresse;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('ufres','".$this->getcodcidaderes()."'); "; // Filtra a cidade principal
		$html .= "filterchild('uffat','".$this->getcodcidadefat()."'); "; // Filtra a cidade de faturamento
		$html .= "filterchild('ufent','".$this->getcodcidadeent()."'); "; // Filtra a cidade de entrega
		$html .= "changetppessoa(); "; // Muda algumas coias da tela dependendo do tipo de pessoa
		$html .= "$('#cpfcnpj').val('".$this->getcpfcnpj()."'); "; // Joga o valor do CPF/CNPJ
		$html .= "$('#rgie').val('".$this->getrgie()."'); "; // Joga o valor do RG/IE

        // Complemento de cadastro
        $temporary = new Temporary("cliente_clientecompl",TRUE);
		$temporary->setcolumns(array("codcomplcadastro","valor"));
		foreach($this->arr_clientecompl as $clientecompl){
			$temporary->append();
			$temporary->setvalue("last","codcomplcadastro",$clientecompl->getcodcomplcadastro());
            $temporary->setvalue("last","valor",utf8_encode($clientecompl->getvalor()));
		}
		$temporary->save();
        $html .= "clientecompl_carregar(); ";

		// Mix por estabelecimento
		$temporary = new Temporary("cliente_clienteestab",TRUE);
		$temporary->setcolumns(array("codestabelec"));
		foreach($this->arr_clienteestab as $clienteestab){
			$temporary->append();
			$temporary->setvalue("last","codestabelec",$clienteestab->getcodestabelec());
		}
		$temporary->save();
		$html .= "clienteestab_desenhar(); ";

		// Lista interesses
		$temporary = new Temporary("cliente_clienteinteresse",TRUE);
		$temporary->setcolumns(array("codinteresse"));
		foreach($this->arr_clienteinteresse as $clienteinteresse){
			$temporary->append();
			$temporary->setvalue("last","codinteresse",$clienteinteresse->getcodinteresse());
		}
		$temporary->save();
		$html .= "interesse_buscar(); ";

		return $html;
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();
		if($this->gettppessoa() == "F"){
			$this->setconvenio("N");
		}elseif($this->gettppessoa() == "J"){
			$this->setcodempresa(NULL);
		}
		$object = objectbytable("cliente",$this->getcodcliente(),$this->con);
		if(parent::save($object)){

			// Complemento de cadastro
            if($this->flag_clientecompl){
                $clientecompl = objectbytable("clientecompl",NULL,$this->con);
                $clientecompl->setcodcliente($this->getcodcliente());
                $arr_clientecompl = object_array($clientecompl);
                foreach($arr_clientecompl as $clientecompl){
                    if(!$clientecompl->delete()){
                        $this->con->rollback();
                        return FALSE;
                    }
                }
                foreach($this->arr_clientecompl as $clientecompl){
                    $clientecompl->setcodcliente($this->getcodcliente());
                    if(!$clientecompl->save()){
                        $this->con->rollback();
                        return FALSE;
                    }
                }
            }

			// Mix por estabelecimento
			if($this->flag_clienteestab){
				$usuaestabel = objectbytable("usuaestabel",NULL,$this->con);
				$usuaestabel->setlogin($_SESSION["WUser"]);
				$arr_usuaestabel = object_array($usuaestabel);
				foreach($arr_usuaestabel as $usuaestabel){
					$clienteestab = objectbytable("clienteestab",NULL,$this->con);
					$clienteestab->setcodcliente($this->getcodcliente());
					$clienteestab->setcodestabelec($usuaestabel->getcodestabelec());
					if(!$clienteestab->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->arr_clienteestab as $clienteestab){
					$clienteestab->setcodcliente($this->getcodcliente());
					if(!$clienteestab->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}

            // Interesse
			if($this->flag_clienteinteresse){
				$clienteinteresse = objectbytable("clienteinteresse",NULL,$this->con);
				$clienteinteresse->setcodcliente($this->getcodcliente());
                $arr_clienteinteresse = object_array($clienteinteresse);
				foreach($arr_clienteinteresse as $clienteinteresse){
                    if(!$clienteinteresse->delete()){
                        $this->con->rollback();
                        return FALSE;
                    }
                }
				foreach($this->arr_clienteinteresse as $clienteinteresse){
					$clienteinteresse->setcodcliente($this->getcodcliente());
					if(!$clienteinteresse->save()){
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

	function searchatdatabase($query,$fetchAll=FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && !is_array($return)){
            // Complemento de cadastro
            if($this->flag_clientecompl){
                $clientecompl = objectbytable("clientecompl",NULL,$this->con);
                $clientecompl->setcodcliente($this->getcodcliente());
                $this->arr_clientecompl = object_array($clientecompl);
            }

			// Mix por estabelecimento
			if($this->flag_clienteestab){
				$clienteestab = objectbytable("clienteestab",NULL,$this->con);
				$clienteestab->setcodcliente($this->getcodcliente());
                $this->arr_clienteestab = object_array($clienteestab);
			}

            // Interesse
			if($this->flag_clienteinteresse){
                $clienteinteresse = objectbytable("clienteinteresse",NULL,$this->con);
				$clienteinteresse->setcodcliente($this->getcodcliente());
                $this->arr_clienteinteresse = object_array($clienteinteresse);
			}
		}
		return $return;
	}

	function searchbyobject($limit = NULL, $offset = NULL, $fetchAll = FALSE, $parcialString = FALSE){
		// Tratamento para carregar apenas os clientes disponiveis para o estabelecimento
		$mix = true;
		$estabelecimento = objectbytable("estabelecimento",null, $this->con);
		$arr_estabelecimento = object_array($estabelecimento);
		if(sizeof($arr_estabelecimento)==1){
			$mix = false;
		}
		if($this->flag_clienteestab && param("CADASTRO","MIXCLIENTE",$this->con) == "S" && $mix){
			$arr_codestabelec = array();
			$usuaestabel = objectbytable("usuaestabel",NULL,$this->con);
			$usuaestabel->setlogin($_SESSION["WUser"]);
			$arr_usuaestabel = object_array($usuaestabel);
			foreach($arr_usuaestabel as $usuaestabel){
				$arr_codestabelec[] = $usuaestabel->getcodestabelec();
			}
			$this->setcodestabelec($arr_codestabelec);
		}
		return parent::searchbyobject($limit,$offset,$fetchAll,$parcialString);
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getrazaosocial(){
		return $this->fields["razaosocial"];
	}

	function getenderfat(){
		return $this->fields["enderfat"];
	}

	function getbairrofat(){
		return $this->fields["bairrofat"];
	}

	function getcepfat(){
		return $this->fields["cepfat"];
	}

	function getcodcidadefat(){
		return $this->fields["codcidadefat"];
	}

	function getuffat(){
		return $this->fields["uffat"];
	}

	function getenderent(){
		return $this->fields["enderent"];
	}

	function getbairroent(){
		return $this->fields["bairroent"];
	}

	function getcepent(){
		return $this->fields["cepent"];
	}

	function getcodcidadeent(){
		return $this->fields["codcidadeent"];
	}

	function getufent(){
		return $this->fields["ufent"];
	}

	function getcontato(){
		return $this->fields["contato"];
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

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function getdtnascto($format = FALSE){
		return ($format ? convert_date($this->fields["dtnascto"],"Y-m-d","d/m/Y") : $this->fields["dtnascto"]);
	}

	function getsexo(){
		return $this->fields["sexo"];
	}

	function getestcivil(){
		return $this->fields["estcivil"];
	}

	function gettipomoradia(){
		return $this->fields["tipomoradia"];
	}

	function getdtmoradia($format = FALSE){
		return ($format ? convert_date($this->fields["dtmoradia"],"Y-m-d","d/m/Y") : $this->fields["dtmoradia"]);
	}

	function getdtadmissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtadmissao"],"Y-m-d","d/m/Y") : $this->fields["dtadmissao"]);
	}

	function getnummatricula(){
		return $this->fields["nummatricula"];
	}

	function getenderres(){
		return $this->fields["enderres"];
	}

	function getbairrores(){
		return $this->fields["bairrores"];
	}

	function getcepres(){
		return $this->fields["cepres"];
	}

	function getcodcidaderes(){
		return $this->fields["codcidaderes"];
	}

	function getufres(){
		return $this->fields["ufres"];
	}

	function getnomecartao(){
		return $this->fields["nomecartao"];
	}

	function getnomeconj(){
		return $this->fields["nomeconj"];
	}

	function getcpfconj(){
		return $this->fields["cpfconj"];
	}

	function getdtnasctoconj($format = FALSE){
		return ($format ? convert_date($this->fields["dtnasctoconj"],"Y-m-d","d/m/Y") : $this->fields["dtnasctoconj"]);
	}

	function getrgconj(){
		return $this->fields["rgconj"];
	}

	function getsalarioconj($format = FALSE){
		return ($format ? number_format($this->fields["salarioconj"],2,",","") : $this->fields["salarioconj"]);
	}

	function getcompoerenda(){
		return $this->fields["compoerenda"];
	}

	function getfoneres(){
		return $this->fields["foneres"];
	}

	function getcelular(){
		return $this->fields["celular"];
	}

	function getfonefat(){
		return $this->fields["fonefat"];
	}

	function getfaxfat(){
		return $this->fields["faxfat"];
	}

	function getfoneent(){
		return $this->fields["foneent"];
	}

	function getfaxent(){
		return $this->fields["faxent"];
	}

	function getgrauescolaridade(){
		return $this->fields["grauescolaridade"];
	}

	function getdtinclusao($format = FALSE){
		return ($format ? convert_date($this->fields["dtinclusao"],"Y-m-d","d/m/Y") : $this->fields["dtinclusao"]);
	}

	function getrbcodbanco1($format = FALSE){
		return ($format ? number_format($this->fields["rbcodbanco1"],2,",","") : $this->fields["rbcodbanco1"]);
	}

	function getrbagencia1(){
		return $this->fields["rbagencia1"];
	}

	function getrbcontacor1(){
		return $this->fields["rbcontacor1"];
	}

	function getrbdtconta1($format = FALSE){
		return ($format ? convert_date($this->fields["rbdtconta1"],"Y-m-d","d/m/Y") : $this->fields["rbdtconta1"]);
	}

	function getrbcodbanco2($format = FALSE){
		return ($format ? number_format($this->fields["rbcodbanco2"],2,",","") : $this->fields["rbcodbanco2"]);
	}

	function getrbagencia2(){
		return $this->fields["rbagencia2"];
	}

	function getrbcontacor2(){
		return $this->fields["rbcontacor2"];
	}

	function getrbdtconta2($format = FALSE){
		return ($format ? convert_date($this->fields["rbdtconta2"],"Y-m-d","d/m/Y") : $this->fields["rbdtconta2"]);
	}

	function getrbcodbanco3($format = FALSE){
		return ($format ? number_format($this->fields["rbcodbanco3"],2,",","") : $this->fields["rbcodbanco3"]);
	}

	function getrbagencia3(){
		return $this->fields["rbagencia3"];
	}

	function getrbcontacor3(){
		return $this->fields["rbcontacor3"];
	}

	function getrbdtconta3($format = FALSE){
		return ($format ? convert_date($this->fields["rbdtconta3"],"Y-m-d","d/m/Y") : $this->fields["rbdtconta3"]);
	}

	function getrcnomeemp1(){
		return $this->fields["rcnomeemp1"];
	}

	function getrccontato1(){
		return $this->fields["rccontato1"];
	}

	function getrcfone1(){
		return $this->fields["rcfone1"];
	}

	function getrcnomeemp2(){
		return $this->fields["rcnomeemp2"];
	}

	function getrccontato2(){
		return $this->fields["rccontato2"];
	}

	function getrcfone2(){
		return $this->fields["rcfone2"];
	}

	function getrcnomeemp3(){
		return $this->fields["rcnomeemp3"];
	}

	function getrccontato3(){
		return $this->fields["rccontato3"];
	}

	function getrcfone3(){
		return $this->fields["rcfone3"];
	}

	function getrpnome1(){
		return $this->fields["rpnome1"];
	}

	function getrpgrau1(){
		return $this->fields["rpgrau1"];
	}

	function getrpfone1(){
		return $this->fields["rpfone1"];
	}

	function getrpnome2(){
		return $this->fields["rpnome2"];
	}

	function getrpgrau2(){
		return $this->fields["rpgrau2"];
	}

	function getrpfone2(){
		return $this->fields["rpfone2"];
	}

	function getrpnome3(){
		return $this->fields["rpnome3"];
	}

	function getrpgrau3(){
		return $this->fields["rpgrau3"];
	}

	function getrpfone3(){
		return $this->fields["rpfone3"];
	}

	function getsalario($format = FALSE){
		return ($format ? number_format($this->fields["salario"],2,",","") : $this->fields["salario"]);
	}

	function getrespnome1(){
		return $this->fields["respnome1"];
	}

	function getrespcargo1(){
		return $this->fields["respcargo1"];
	}

	function getrespfone1(){
		return $this->fields["respfone1"];
	}

	function getrespramal1(){
		return $this->fields["respramal1"];
	}

	function getrespemail1(){
		return $this->fields["respemail1"];
	}

	function getrespnome2(){
		return $this->fields["respnome2"];
	}

	function getrespcargo2(){
		return $this->fields["respcargo2"];
	}

	function getrespfone2(){
		return $this->fields["respfone2"];
	}

	function getrespramal2(){
		return $this->fields["respramal2"];
	}

	function getrespemail2(){
		return $this->fields["respemail2"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getnaturalidade(){
		return $this->fields["naturalidade"];
	}

	function getnacionalidade(){
		return $this->fields["nacionalidade"];
	}

	function getrbchequeesp1(){
		return $this->fields["rbchequeesp1"];
	}

	function getrbchequeesp2(){
		return $this->fields["rbchequeesp2"];
	}

	function getrbchequeesp3(){
		return $this->fields["rbchequeesp3"];
	}

	function getmarcamodcarro1(){
		return $this->fields["marcamodcarro1"];
	}

	function getanocarro1($format = FALSE){
		return ($format ? number_format($this->fields["anocarro1"],2,",","") : $this->fields["anocarro1"]);
	}

	function getmodelocarro1($format = FALSE){
		return ($format ? number_format($this->fields["modelocarro1"],2,",","") : $this->fields["modelocarro1"]);
	}

	function getstatuscarro1(){
		return $this->fields["statuscarro1"];
	}

	function getmarcamodcarro2(){
		return $this->fields["marcamodcarro2"];
	}

	function getanocarro2($format = FALSE){
		return ($format ? number_format($this->fields["anocarro2"],2,",","") : $this->fields["anocarro2"]);
	}

	function getmodelocarro2($format = FALSE){
		return ($format ? number_format($this->fields["modelocarro2"],2,",","") : $this->fields["modelocarro2"]);
	}

	function getstatuscarro2(){
		return $this->fields["statuscarro2"];
	}

	function getmarcamodcarro3(){
		return $this->fields["marcamodcarro3"];
	}

	function getanocarro3($format = FALSE){
		return ($format ? number_format($this->fields["anocarro3"],2,",","") : $this->fields["anocarro3"]);
	}

	function getmodelocarro3($format = FALSE){
		return ($format ? number_format($this->fields["modelocarro3"],2,",","") : $this->fields["modelocarro3"]);
	}

	function getstatuscarro3(){
		return $this->fields["statuscarro3"];
	}

	function getcodempresa(){
		return $this->fields["codempresa"];
	}

	function getconvenio(){
		return $this->fields["convenio"];
	}

	function getsenha(){
		return $this->fields["senha"];
	}

	function getcodemitepref(){
		return $this->fields["codemitepref"];
	}

	function getcodestabpref(){
		return $this->fields["codestabpref"];
	}

	function getcodstatus(){
		return $this->fields["codstatus"];
	}

	function getcodatividade(){
		return $this->fields["codatividade"];
	}

	function getcodprofissao(){
		return $this->fields["codprofissao"];
	}

	function getcodprofissaoconj(){
		return $this->fields["codprofissaoconj"];
	}

	function getnumerofat(){
		return $this->fields["numerofat"];
	}

	function getcomplementofat(){
		return $this->fields["complementofat"];
	}

	function getnumeroent(){
		return $this->fields["numeroent"];
	}

	function getcomplementoent(){
		return $this->fields["complementoent"];
	}

	function getnumerores(){
		return $this->fields["numerores"];
	}

	function getcomplementores(){
		return $this->fields["complementores"];
	}

	function getcodvendedor(){
		return $this->fields["codvendedor"];
	}

	function getcodtransp(){
		return $this->fields["codtransp"];
	}

	function getlimite1($format = FALSE){
		return ($format ? number_format($this->fields["limite1"],2,",","") : $this->fields["limite1"]);
	}

	function getdebito1($format = FALSE){
		return ($format ? number_format($this->fields["debito1"],2,",","") : $this->fields["debito1"]);
	}

	function getlimite2($format = FALSE){
		return ($format ? number_format($this->fields["limite2"],2,",","") : $this->fields["limite2"]);
	}

	function getdebito2($format = FALSE){
		return ($format ? number_format($this->fields["debito2"],2,",","") : $this->fields["debito2"]);
	}

	function getsuframa(){
		return $this->fields["suframa"];
	}

	function getdigitoagencia(){
		return $this->fields["digitoagencia"];
	}

	function getdigitoconta(){
		return $this->fields["digitoconta"];
	}

	function getpermitedebauto(){
		return $this->fields["permitedebauto"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getagencia(){
		return $this->fields["agencia"];
	}

	function getconta(){
		return $this->fields["conta"];
	}

	function getcontadesde($format = FALSE){
		return ($format ? convert_date($this->fields["contadesde"],"Y-m-d","d/m/Y") : $this->fields["contadesde"]);
	}

	function getorgaopublico(){
		return $this->fields["orgaopublico"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getcodrepresentante(){
		return $this->fields["codrepresentante"];
	}

	function getenviaemailmkt(){
		return $this->fields["enviaemailmkt"];
	}

	function getemailnfe(){
		return $this->fields["emailnfe"];
	}

	function getcodpaisfat(){
		return $this->fields["codpaisfat"];
	}

	function getcodpaisent(){
		return $this->fields["codpaisent"];
	}

	function getcodpaisres(){
		return $this->fields["codpaisres"];
	}

	function getcontribuinteicms(){
		return $this->fields["contribuinteicms"];
	}

	function getrgemissor(){
		return $this->fields["rgemissor"];
	}

	function getrgorgao(){
		return $this->fields["rgorgao"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getdescfixo($format = FALSE){
		return ($format ? number_format($this->fields["descfixo"],2,",","") : $this->fields["descfixo"]);
	}

	function getsincpdv(){
		return $this->fields["sincpdv"];
	}

	function getidestrangeiro(){
		return $this->fields["idestrangeiro"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getinscmunicipal(){
		return $this->fields["inscmunicipal"];
	}

	function getorigemreg(){
		return $this->fields["origemreg"];
	}

	function getparticipafidelizacao(){
		return $this->fields["participafidelizacao"];
	}

	function setcontribuinteicms($value){
		$this->fields["contribuinteicms"] = value_string($value,1);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,60);
	}

	function setrazaosocial($value){
		$this->fields["razaosocial"] = value_string($value,60);
	}

	function setenderfat($value){
		$this->fields["enderfat"] = value_string($value,40);
	}

	function setbairrofat($value){
		$this->fields["bairrofat"] = value_string($value,30);
	}

	function setcepfat($value){
		$this->fields["cepfat"] = value_string($value,9);
	}

	function setcodcidadefat($value){
		$this->fields["codcidadefat"] = value_numeric($value);
	}

	function setuffat($value){
		$this->fields["uffat"] = value_string($value,2);
	}

	function setenderent($value){
		$this->fields["enderent"] = value_string($value,40);
	}

	function setbairroent($value){
		$this->fields["bairroent"] = value_string($value,30);
	}

	function setcepent($value){
		$this->fields["cepent"] = value_string($value,9);
	}

	function setcodcidadeent($value){
		$this->fields["codcidadeent"] = value_numeric($value);
	}

	function setufent($value){
		$this->fields["ufent"] = value_string($value,2);
	}

	function setcontato($value){
		$this->fields["contato"] = value_string($value,40);
	}

	function setsite($value){
		$this->fields["site"] = value_string($value,60);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,80);
	}

	function settppessoa($value){
		$this->fields["tppessoa"] = value_string($value,1);
	}

	function setcpfcnpj($value){
		$this->fields["cpfcnpj"] = value_string($value,20);
	}

	function setrgie($value){
		$this->fields["rgie"] = value_string($value,20);
	}

	function setcodclassif($value){
		$this->fields["codclassif"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,500);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setdtnascto($value){
		$this->fields["dtnascto"] = value_date($value);
	}

	function setsexo($value){
		$this->fields["sexo"] = value_string($value,1);
	}

	function setestcivil($value){
		$this->fields["estcivil"] = value_string($value,1);
	}

	function settipomoradia($value){
		$this->fields["tipomoradia"] = value_string($value,1);
	}

	function setdtmoradia($value){
		$this->fields["dtmoradia"] = value_date($value);
	}

	function setdtadmissao($value){
		$this->fields["dtadmissao"] = value_date($value);
	}

	function setnummatricula($value){
		$this->fields["nummatricula"] = value_string($value,20);
	}

	function setenderres($value){
		$this->fields["enderres"] = value_string($value,40);
	}

	function setbairrores($value){
		$this->fields["bairrores"] = value_string($value,30);
	}

	function setcepres($value){
		$this->fields["cepres"] = value_string($value,9);
	}

	function setcodcidaderes($value){
		$this->fields["codcidaderes"] = value_numeric($value);
	}

	function setufres($value){
		$this->fields["ufres"] = value_string($value,2);
	}

	function setnomecartao($value){
		$this->fields["nomecartao"] = value_string($value,40);
	}

	function setnomeconj($value){
		$this->fields["nomeconj"] = value_string($value,40);
	}

	function setcpfconj($value){
		$this->fields["cpfconj"] = value_string($value,20);
	}

	function setdtnasctoconj($value){
		$this->fields["dtnasctoconj"] = value_date($value);
	}

	function setrgconj($value){
		$this->fields["rgconj"] = value_string($value,20);
	}

	function setsalarioconj($value){
		$this->fields["salarioconj"] = value_numeric($value);
	}

	function setcompoerenda($value){
		$this->fields["compoerenda"] = value_string($value,1);
	}

	function setfoneres($value){
		$this->fields["foneres"] = value_string($value,20);
	}

	function setcelular($value){
		$this->fields["celular"] = value_string($value,20);
	}

	function setfonefat($value){
		$this->fields["fonefat"] = value_string($value,20);
	}

	function setfaxfat($value){
		$this->fields["faxfat"] = value_string($value,20);
	}

	function setfoneent($value){
		$this->fields["foneent"] = value_string($value,20);
	}

	function setfaxent($value){
		$this->fields["faxent"] = value_string($value,20);
	}

	function setgrauescolaridade($value){
		$this->fields["grauescolaridade"] = value_string($value,1);
	}

	function setdtinclusao($value){
		$this->fields["dtinclusao"] = value_date($value);
	}

	function setrbcodbanco1($value){
		$this->fields["rbcodbanco1"] = value_numeric($value);
	}

	function setrbagencia1($value){
		$this->fields["rbagencia1"] = value_string($value,20);
	}

	function setrbcontacor1($value){
		$this->fields["rbcontacor1"] = value_string($value,20);
	}

	function setrbdtconta1($value){
		$this->fields["rbdtconta1"] = value_date($value);
	}

	function setrbcodbanco2($value){
		$this->fields["rbcodbanco2"] = value_numeric($value);
	}

	function setrbagencia2($value){
		$this->fields["rbagencia2"] = value_string($value,20);
	}

	function setrbcontacor2($value){
		$this->fields["rbcontacor2"] = value_string($value,20);
	}

	function setrbdtconta2($value){
		$this->fields["rbdtconta2"] = value_date($value);
	}

	function setrbcodbanco3($value){
		$this->fields["rbcodbanco3"] = value_numeric($value);
	}

	function setrbagencia3($value){
		$this->fields["rbagencia3"] = value_string($value,20);
	}

	function setrbcontacor3($value){
		$this->fields["rbcontacor3"] = value_string($value,20);
	}

	function setrbdtconta3($value){
		$this->fields["rbdtconta3"] = value_date($value);
	}

	function setrcnomeemp1($value){
		$this->fields["rcnomeemp1"] = value_string($value,30);
	}

	function setrccontato1($value){
		$this->fields["rccontato1"] = value_string($value,30);
	}

	function setrcfone1($value){
		$this->fields["rcfone1"] = value_string($value,20);
	}

	function setrcnomeemp2($value){
		$this->fields["rcnomeemp2"] = value_string($value,30);
	}

	function setrccontato2($value){
		$this->fields["rccontato2"] = value_string($value,30);
	}

	function setrcfone2($value){
		$this->fields["rcfone2"] = value_string($value,20);
	}

	function setrcnomeemp3($value){
		$this->fields["rcnomeemp3"] = value_string($value,30);
	}

	function setrccontato3($value){
		$this->fields["rccontato3"] = value_string($value,30);
	}

	function setrcfone3($value){
		$this->fields["rcfone3"] = value_string($value,20);
	}

	function setrpnome1($value){
		$this->fields["rpnome1"] = value_string($value,30);
	}

	function setrpgrau1($value){
		$this->fields["rpgrau1"] = value_string($value,20);
	}

	function setrpfone1($value){
		$this->fields["rpfone1"] = value_string($value,20);
	}

	function setrpnome2($value){
		$this->fields["rpnome2"] = value_string($value,30);
	}

	function setrpgrau2($value){
		$this->fields["rpgrau2"] = value_string($value,20);
	}

	function setrpfone2($value){
		$this->fields["rpfone2"] = value_string($value,20);
	}

	function setrpnome3($value){
		$this->fields["rpnome3"] = value_string($value,30);
	}

	function setrpgrau3($value){
		$this->fields["rpgrau3"] = value_string($value,20);
	}

	function setrpfone3($value){
		$this->fields["rpfone3"] = value_string($value,20);
	}

	function setsalario($value){
		$this->fields["salario"] = value_numeric($value);
	}

	function setrespnome1($value){
		$this->fields["respnome1"] = value_string($value,40);
	}

	function setrespcargo1($value){
		$this->fields["respcargo1"] = value_string($value,30);
	}

	function setrespfone1($value){
		$this->fields["respfone1"] = value_string($value,20);
	}

	function setrespramal1($value){
		$this->fields["respramal1"] = value_string($value,5);
	}

	function setrespemail1($value){
		$this->fields["respemail1"] = value_string($value,60);
	}

	function setrespnome2($value){
		$this->fields["respnome2"] = value_string($value,40);
	}

	function setrespcargo2($value){
		$this->fields["respcargo2"] = value_string($value,30);
	}

	function setrespfone2($value){
		$this->fields["respfone2"] = value_string($value,20);
	}

	function setrespramal2($value){
		$this->fields["respramal2"] = value_string($value,5);
	}

	function setrespemail2($value){
		$this->fields["respemail2"] = value_string($value,60);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setnaturalidade($value){
		$this->fields["naturalidade"] = value_string($value,30);
	}

	function setnacionalidade($value){
		$this->fields["nacionalidade"] = value_string($value,30);
	}

	function setrbchequeesp1($value){
		$this->fields["rbchequeesp1"] = value_string($value,1);
	}

	function setrbchequeesp2($value){
		$this->fields["rbchequeesp2"] = value_string($value,1);
	}

	function setrbchequeesp3($value){
		$this->fields["rbchequeesp3"] = value_string($value,1);
	}

	function setmarcamodcarro1($value){
		$this->fields["marcamodcarro1"] = value_string($value,30);
	}

	function setanocarro1($value){
		$this->fields["anocarro1"] = value_numeric($value);
	}

	function setmodelocarro1($value){
		$this->fields["modelocarro1"] = value_numeric($value);
	}

	function setstatuscarro1($value){
		$this->fields["statuscarro1"] = value_string($value,1);
	}

	function setmarcamodcarro2($value){
		$this->fields["marcamodcarro2"] = value_string($value,30);
	}

	function setanocarro2($value){
		$this->fields["anocarro2"] = value_numeric($value);
	}

	function setmodelocarro2($value){
		$this->fields["modelocarro2"] = value_numeric($value);
	}

	function setstatuscarro2($value){
		$this->fields["statuscarro2"] = value_string($value,1);
	}

	function setmarcamodcarro3($value){
		$this->fields["marcamodcarro3"] = value_string($value,30);
	}

	function setanocarro3($value){
		$this->fields["anocarro3"] = value_numeric($value);
	}

	function setmodelocarro3($value){
		$this->fields["modelocarro3"] = value_numeric($value);
	}

	function setstatuscarro3($value){
		$this->fields["statuscarro3"] = value_string($value,1);
	}

	function setcodempresa($value){
		$this->fields["codempresa"] = value_numeric($value);
	}

	function setconvenio($value){
		$this->fields["convenio"] = value_string($value,1);
	}

	function setsenha($value){
		$this->fields["senha"] = value_string($value,20);
	}

	function setcodemitepref($value){
		$this->fields["codemitepref"] = value_numeric($value);
	}

	function setcodestabpref($value){
		$this->fields["codestabpref"] = value_numeric($value);
	}

	function setcodstatus($value){
		$this->fields["codstatus"] = value_numeric($value);
	}

	function setcodatividade($value){
		$this->fields["codatividade"] = value_numeric($value);
	}

	function setcodprofissao($value){
		$this->fields["codprofissao"] = value_numeric($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setcodprofissaoconj($value){
		$this->fields["codprofissaoconj"] = value_numeric($value);
	}

	function setnumerofat($value){
		$this->fields["numerofat"] = value_string($value,20);
	}

	function setcomplementofat($value){
		$this->fields["complementofat"] = value_string($value,40);
	}

	function setnumeroent($value){
		$this->fields["numeroent"] = value_string($value,20);
	}

	function setcomplementoent($value){
		$this->fields["complementoent"] = value_string($value,40);
	}

	function setnumerores($value){
		$this->fields["numerores"] = value_string($value,20);
	}

	function setcomplementores($value){
		$this->fields["complementores"] = value_string($value,40);
	}

	function setcodvendedor($value){
		$this->fields["codvendedor"] = value_numeric($value);
	}

	function setcodtransp($value){
		$this->fields["codtransp"] = value_numeric($value);
	}

	function setlimite1($value){
		$this->fields["limite1"] = value_numeric($value);
	}

	function setdebito1($value){
		$this->fields["debito1"] = value_numeric($value);
	}

	function setlimite2($value){
		$this->fields["limite2"] = value_numeric($value);
	}

	function setdebito2($value){
		$this->fields["debito2"] = value_numeric($value);
	}


	function setsuframa($value){
		$this->fields["suframa"] = value_string($value,9);
	}

	function setdigitoagencia($value){
		$this->fields["digitoagencia"] = value_string($value,1);
	}

	function setdigitoconta($value){
		$this->fields["digitoconta"] = value_string($value,1);
	}

	function setpermitedebauto($value){
		$this->fields["permitedebauto"] = value_string($value,1);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setagencia($value){
		$this->fields["agencia"] = value_string($value,20);
	}

	function setconta($value){
		$this->fields["conta"] = value_string($value,20);
	}

	function setcontadesde($value){
		$this->fields["contadesde"] = value_date($value);
	}

	function setorgaopublico($value){
		$this->fields["orgaopublico"] = value_string($value,1);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->setrelationvalue("clienteestab","codestabelec",$value);
	}

	function setcodrepresentante($value){
		$this->fields["codrepresentante"] = value_numeric($value);
	}

	function setenviaemailmkt($value){
		$this->fields["enviaemailmkt"] = value_string($value,1);
	}

	function setemailnfe($value){
		$this->fields["emailnfe"] = value_string($value,120);
	}

	function setcodpaisfat($value){
		$this->fields["codpaisfat"] = value_string($value,5);
	}

	function setcodpaisent($value){
		$this->fields["codpaisent"] = value_string($value,5);
	}

	function setcodpaisres($value){
		$this->fields["codpaisres"] = value_string($value,5);
	}

	function setrgemissor($value){
		$this->fields["rgemissor"] = value_string($value,5);
	}

	function setrgorgao($value){
		$this->fields["rgorgao"] = value_string($value,5);
	}

	function setidestrangeiro($value){
		$this->fields["idestrangeiro"] = value_string($value,50);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value,1);
	}

	function setdescfixo($value){
		$this->fields["descfixo"] = value_numeric($value);
	}

	function setsincpdv($value){
		$this->fields["sincpdv"] = value_numeric($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value,9);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setinscmunicipal($value){
		$this->fields["inscmunicipal"] = value_string($value, 30);
	}

	function setorigemreg($value){
		$this->fields["origemreg"] = value_string($value, 1);
	}

	function setparticipafidelizacao($value){
		$this->fields["participafidelizacao"] = value_string($value, 1);
	}
}