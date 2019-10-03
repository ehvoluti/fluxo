<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Administradora extends Cadastro{
	public $administestabelec;
	public $arr_administradorabin;

	protected $flag_administestabelec = FALSE;
	protected $flag_administradorabin = FALSE;

	function __construct($codadminist = NULL){
		parent::__construct();
		$this->table = "administradora";
		$this->primarykey = "codadminist";
		$this->newrelation("administradora","codcidade","cidade","codcidade");
		$this->newrelation("cidade","uf","estado","uf");
		$this->setcodadminist($codadminist);
		if($this->getcodadminist() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_administestabelec($value){
		if(is_bool($value)){
			$this->flag_administestabelec = $value;
		}
	}

	function flag_administradorabin($value){
		if(is_bool($value)){
			$this->flag_administradorabin = $value;
		}
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save($object)){
			if($this->flag_administestabelec){
				$administestabelec = objectbytable("administestabelec",NULL,$this->con);
				$administestabelec->setcodadminist($this->getcodadminist());
				$search = $administestabelec->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search[0])){
						$search = array($search);
					}
					foreach($search as $key){
						$administestabelec = objectbytable("administestabelec",$key,$this->con);
						if(!$administestabelec->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
				foreach($this->administestabelec as $administestabelec){
					$administestabelec->setcodadminist($this->getcodadminist());
					if(!$administestabelec->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			if($this->flag_administradorabin){
				$administradorabin = objectbytable("administradorabin",NULL,$this->con);
				$administradorabin->setcodadminist($this->getcodadminist());
				$search = $administradorabin->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search[0])){
						$search = array($search);
					}
					foreach($search as $key){
						$administradorabin = objectbytable("administradorabin",$key,$this->con);
						if(!$administradorabin->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
				foreach($this->arr_administradorabin as $administradorabin){
					$administradorabin->setcodadminist($this->getcodadminist());
					if(!$administradorabin->save()){
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
		if(!is_array($return) && $return !== FALSE){
			if($this->flag_administestabelec){
				$this->administestabelec = array();
				$administestabelec = objectbytable("administestabelec",NULL,$this->con);
				$administestabelec->setcodadminist($this->getcodadminist());
				$search = $administestabelec->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search[0])){
						$search = array($search);
					}
					foreach($search as $key){
						$this->administestabelec[] = objectbytable("administestabelec",$key,$this->con);
					}
				}
			}

			if($this->flag_administradorabin){
				$this->arr_administradorabin = array();
				$administradorabin = objectbytable("administradorabin",NULL,$this->con);
				$administradorabin->setcodadminist($this->getcodadminist());
				$search = $administradorabin->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search[0])){
						$search = array($search);
					}
					foreach($search as $key){
						$this->arr_administradorabin[] = objectbytable("administradorabin",$key,$this->con);
					}
				}
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$temporary = new Temporary("administradora_administestabelec",FALSE);
		$this->administestabelec = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$administestabelec = objectbytable("administestabelec",NULL,$this->con);
			$administestabelec->setcodadminist($this->getcodadminist());
			$administestabelec->setcodestabelec($temporary->getvalue($i,"codestabelec"));
			$administestabelec->setdiaenvio($temporary->getvalue($i,"diaenvio"));
			$administestabelec->setvaldescto($temporary->getvalue($i,"valdescto"));
			$administestabelec->setpercdescto($temporary->getvalue($i,"percdescto"));
			$administestabelec->settaxaenvio($temporary->getvalue($i,"taxaenvio"));
			$this->administestabelec[] = $administestabelec;
		}

		// BIN da administradora
		$temporary = new Temporary("administradora_administradorabin",FALSE);
		$this->arr_administradorabin = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$bin = $temporary->getvalue($i,"bin");
			$codadminist = $temporary->getvalue($i,"codadminist");
			$administradorabin = objectbytable("administradorabin",NULL,$this->con);
			$administradorabin->setcodadminist($codadminist);
			$administradorabin->setbin($bin);
			$this->arr_administradorabin[] = $administradorabin; // Objeto com todos os fornecedores do produto
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('uf','".$this->getcodcidade()."'); ";
		$temporary = new Temporary("administradora_administestabelec",TRUE);
		$temporary->setcolumns(array("codestabelec","diaenvio","valdescto","percdescto","taxadescto","taxaenvio"));
		foreach($this->administestabelec as $administestabelec){
			$temporary->append();
			$temporary->setvalue("last","codestabelec",$administestabelec->getcodestabelec());
			$temporary->setvalue("last","diaenvio",$administestabelec->getdiaenvio());
			$temporary->setvalue("last","valdescto",$administestabelec->getvaldescto(TRUE));
			$temporary->setvalue("last","percdescto",$administestabelec->getpercdescto(TRUE));
			$temporary->setvalue("last","taxaenvio",$administestabelec->gettaxaenvio(TRUE));
		}
		$temporary->save();

		// Lista bin
		$temporary = new Temporary("administradora_administradorabin",TRUE);
		$temporary->setcolumns(array("bin","codadminist"));
		foreach($this->arr_administradorabin as $administradorabin){
			$temporary->append();
			$temporary->setvalue("last","bin",$administradorabin->getbin());
			$temporary->setvalue("last","codadminist",$administradorabin->getcodadminist());
		}
		$temporary->save();

		return $html;
	}

	function getcodadminist(){
		return $this->fields["codadminist"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getcnpj(){
		return $this->fields["cnpj"];
	}

	function getfone(){
		return $this->fields["fone"];
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

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
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

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function gettipotransacao(){
		return $this->fields["tipotransacao"];
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setcodadminist($value){
		$this->fields["codadminist"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setcnpj($value){
		$this->fields["cnpj"] = value_string($value,20);
	}

	function setfone($value){
		$this->fields["fone"] = value_string($value,20);
	}

	function setendereco($value){
		$this->fields["endereco"] = value_string($value,40);
	}

	function setbairro($value){
		$this->fields["bairro"] = value_string($value,30);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value,9);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
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

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setnumero($value){
		$this->fields["numero"] = value_numeric($value);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value,40);
	}

	function settipotransacao($value){
		$this->fields["tipotransacao"] = value_string($value,1);
	}
}
?>