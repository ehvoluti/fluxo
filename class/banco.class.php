<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_once("../class/temporary.class.php");

class Banco extends Cadastro{
	function __construct($codbanco = NULL){
		parent::__construct();
		$this->table = "banco";
		$this->primarykey = array("codbanco");
		$this->setcodbanco($codbanco);
		if(!is_null($this->getcodbanco())){
			$this->searchbyobject();
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$html .= "filterchild('codoficial'); ";
		$html .= setvalue("codpinstrucao",$this->getcodpinstrucao(),FALSE);
		$html .= setvalue("codsinstrucao",$this->getcodsinstrucao(),FALSE);

		$bancoestab = objectbytable("bancoestab", NULL, $this->con);
		$bancoestab->setcodbanco($this->getcodbanco());
		$arr_bancoestab = object_array($bancoestab);

		$temporary = new Temporary("banco_bancoestab", TRUE);
		$temporary->setcolumns(array("codestabelec", "disponivel"));
		foreach($arr_bancoestab as $bancoestab){
			$temporary->append();
			$temporary->setvalue("last", "codestabelec", $bancoestab->getcodestabelec());
			$temporary->setvalue("last", "disponivel", $bancoestab->getdisponivel());
		}
		$temporary->save();
		return $html;
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();

		if(!parent::save($object)){
			$this->con->rollback();
			return FALSE;
		}


		$temporary = new Temporary("banco_bancoestab", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$bancoestab = objectbytable("bancoestab",null, $this->con);
			$bancoestab->setcodbanco($this->getcodbanco());
			$bancoestab->setcodestabelec($temporary->getvalue($i, "codestabelec"));
			$bancoestab->setdisponivel($temporary->getvalue($i, "disponivel"));

			if(!$bancoestab->save()){
				$this->con->rollback();
				return FALSE;
			}
		}
		$this->con->commit();
		return TRUE;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		if($this->getcodbanco() == 1){
			return false;
		}
		return true;
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getagencia(){
		return $this->fields["agencia"];
	}

	function getconta(){
		return $this->fields["conta"];
	}

	function getcodoficial(){
		return $this->fields["codoficial"];
	}

	function getsaldoinicial($format = FALSE){
		return ($format ? number_format($this->fields["saldoinicial"],2,",","") : $this->fields["saldoinicial"]);
	}

	function getcredito($format = FALSE){
		return ($format ? number_format($this->fields["credito"],2,",","") : $this->fields["credito"]);
	}

	function getdebito($format = FALSE){
		return ($format ? number_format($this->fields["debito"],2,",","") : $this->fields["debito"]);
	}

	function getdtultfecto($format = FALSE){
		return ($format ? convert_date($this->fields["dtultfecto"],"Y-m-d","d/m/Y") : $this->fields["dtultfecto"]);
	}

	function getcarteira(){
		return $this->fields["carteira"];
	}

	function getvarcarteira(){
		return $this->fields["varcarteira"];
	}

	function getnossonumero(){
		return $this->fields["nossonumero"];
	}

	function getnumconvenio($format = FALSE){
		return ($format ? number_format($this->fields["numconvenio"],0,",","") : $this->fields["numconvenio"]);
	}

	function getdigito(){
		return $this->fields["digito"];
	}

	function gettaxaboleto($format = FALSE){
		return ($format ? number_format($this->fields["taxaboleto"],2,",","") : $this->fields["taxaboleto"]);
	}

	function getcodigoempresa(){
		return $this->fields["codigoempresa"];
	}

	function getseqremessa(){
		return $this->fields["seqremessa"];
	}

	function getdtultremessa($format = FALSE){
		return ($format ? convert_date($this->fields["dtultremessa"],"Y-m-d","d/m/Y") : $this->fields["dtultremessa"]);
	}

	function getnrultremessa(){
		return $this->fields["nrultremessa"];
	}

	function getboletodebauto(){
		return $this->fields["boletodebauto"];
	}

	function getdigitoagencia(){
		return $this->fields["digitoagencia"];
	}

	function getpercmulta($format = FALSE){
		return ($format ? number_format($this->fields["percmulta"],2,",","") : $this->fields["percmulta"]);
	}

	function getbancoemiteboleto(){
		return $this->fields["bancoemiteboleto"];
	}

	function getvalormoradiaria($format = FALSE){
		return ($format ? number_format($this->fields["valormoradiaria"],2,",","") : $this->fields["valormoradiaria"]);
	}

	function getinstrucao(){
		return $this->fields["instrucao"];
	}

	function getdiasprotesto(){
		return $this->fields["diasprotesto"];
	}

	function getnomecedente(){
		return $this->fields["nomecedente"];
	}

	function getcnpjcedente(){
		return $this->fields["cnpjcedente"];
	}

	function getsacadoravalista(){
		return $this->fields["sacadoravalista"];
	}

	function getcodpinstrucao(){
		return $this->fields["codpinstrucao"];
	}

	function getcodsinstrucao(){
		return $this->fields["codsinstrucao"];
	}

	function gettiporemessa(){
		return $this->fields["tiporemessa"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getaceitetitulo(){
		return $this->fields["aceitetitulo"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function gettipocadcobranca(){
		return $this->fields["tipocadcobranca"];
	}

	function getcodcarteira(){
		return $this->fields["codcarteira"];
	}

	function gettipodistribuicao(){
		return $this->fields["tipodistribuicao"];
	}

	function getcodigocedente(){
		return $this->fields["codigocedente"];
	}

	function getseqremessadia(){
		return $this->fields["seqremessadia"];
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setagencia($value){
		$this->fields["agencia"] = value_string($value,20);
	}

	function setconta($value){
		$this->fields["conta"] = value_string($value,20);
	}

	function setcodoficial($value){
		$this->fields["codoficial"] = value_string($value,5);
	}

	function setsaldoinicial($value){
		$this->fields["saldoinicial"] = value_numeric($value);
	}

	function setcredito($value){
		$this->fields["credito"] = value_numeric($value);
	}

	function setdebito($value){
		$this->fields["debito"] = value_numeric($value);
	}

	function setdtultfecto($value){
		$this->fields["dtultfecto"] = value_date($value);
	}

	function setcarteira($value){
		$this->fields["carteira"] = value_string($value,3);
	}

	function setvarcarteira($value){
		$this->fields["varcarteira"] = value_string($value,3);
	}

	function setnossonumero($value){
		$this->fields["nossonumero"] = value_string($value,20);
	}

	function setnumconvenio($value){
		$this->fields["numconvenio"] = value_numeric($value);
	}

	function setdigito($value){
		$this->fields["digito"] = value_string($value,2);
	}

	function settaxaboleto($value){
		$this->fields["taxaboleto"] = value_numeric($value);
	}

	function setcodigoempresa($value){
		$this->fields["codigoempresa"] = value_string($value,20);
	}

	function setseqremessa($value){
		$this->fields["seqremessa"] = value_numeric($value);
	}

	function setdtultremessa($value){
		$this->fields["dtultremessa"] = value_date($value);
	}

	function setnrultremessa($value){
		$this->fields["nrultremessa"] = value_numeric($value);
	}

	function setboletodebauto($value){
		$this->fields["boletodebauto"] = value_string($value,1);
	}

	function setdigitoagencia($value){
		$this->fields["digitoagencia"] = value_string($value,1);
	}

	function setpercmulta($value){
		$this->fields["percmulta"] = value_numeric($value);
	}

	function setbancoemiteboleto($value){
		$this->fields["bancoemiteboleto"] = value_string($value,1);
	}

	function setvalormoradiaria($value){
		$this->fields["valormoradiaria"] = value_numeric($value);
	}

	function setinstrucao($value){
		$this->fields["instrucao"] = value_string($value);
	}

	function setdiasprotesto($value){
		$this->fields["diasprotesto"] = value_numeric($value);
	}

	function setnomecedente($value){
		$this->fields["nomecedente"] = value_string($value,60);
	}

	function setcnpjcedente($value){
		$this->fields["cnpjcedente"] = value_string($value,20);
	}

	function setsacadoravalista($value){
		$this->fields["sacadoravalista"] = value_string($value,60);
	}

	function setcodpinstrucao($value){
		$this->fields["codpinstrucao"] = value_numeric($value);
	}

	function setcodsinstrucao($value){
		$this->fields["codsinstrucao"] = value_numeric($value);
	}

	function settiporemessa($value){
		$this->fields["tiporemessa"] = value_string($value, 2);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setaceitetitulo($value){
		$this->fields["aceitetitulo"] = value_string($value,1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function settipocadcobranca($value){
		$this->fields["tipocadcobranca"] = value_string($value, 1);
	}

	function setcodcarteira($value){
		$this->fields["codcarteira"] = value_string($value, 1);
	}

	function settipodistribuicao($value){
		$this->fields["tipodistribuicao"] = value_string($value, 1);
	}

	function setcodigocedente($value){
		$this->fields["codigocedente"] = value_string($value, 10);
	}

	function setseqremessadia($value){
		$this->fields["seqremessadia"] = value_numeric($value);
	}
}