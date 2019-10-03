<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class CondPagto extends Cadastro{
	function __construct($codcondpagto = NULL){
		parent::__construct();
		$this->table = "condpagto";
		$this->primarykey = array("codcondpagto");
		$this->setcodcondpagto($codcondpagto);
		if(!is_null($this->getcodcondpagto())){
			$this->searchbyobject();
		}
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdia1(){
		return $this->fields["dia1"];
	}

	function getpercdia1($format = FALSE){
		return ($format ? number_format($this->fields["percdia1"],4,",","") : $this->fields["percdia1"]);
	}

	function getdia2(){
		return $this->fields["dia2"];
	}

	function getpercdia2($format = FALSE){
		return ($format ? number_format($this->fields["percdia2"],4,",","") : $this->fields["percdia2"]);
	}

	function getdia3(){
		return $this->fields["dia3"];
	}

	function getpercdia3($format = FALSE){
		return ($format ? number_format($this->fields["percdia3"],4,",","") : $this->fields["percdia3"]);
	}

	function getdia4(){
		return $this->fields["dia4"];
	}

	function getpercdia4($format = FALSE){
		return ($format ? number_format($this->fields["percdia4"],4,",","") : $this->fields["percdia4"]);
	}

	function getdia5(){
		return $this->fields["dia5"];
	}

	function getpercdia5($format = FALSE){
		return ($format ? number_format($this->fields["percdia5"],4,",","") : $this->fields["percdia5"]);
	}

	function getdia6(){
		return $this->fields["dia6"];
	}

	function getpercdia6($format = FALSE){
		return ($format ? number_format($this->fields["percdia6"],4,",","") : $this->fields["percdia6"]);
	}

	function getdia7(){
		return $this->fields["dia7"];
	}

	function getpercdia7($format = FALSE){
		return ($format ? number_format($this->fields["percdia7"],4,",","") : $this->fields["percdia7"]);
	}

	function getdia8(){
		return $this->fields["dia8"];
	}

	function getpercdia8($format = FALSE){
		return ($format ? number_format($this->fields["percdia8"],4,",","") : $this->fields["percdia8"]);
	}

	function getdia9(){
		return $this->fields["dia9"];
	}

	function getpercdia9($format = FALSE){
		return ($format ? number_format($this->fields["percdia9"],4,",","") : $this->fields["percdia9"]);
	}

	function getdia10(){
		return $this->fields["dia10"];
	}

	function getpercdia10($format = FALSE){
		return ($format ? number_format($this->fields["percdia10"],4,",","") : $this->fields["percdia10"]);
	}

	function getdia11(){
		return $this->fields["dia11"];
	}

	function getpercdia11($format = FALSE){
		return ($format ? number_format($this->fields["percdia11"],4,",","") : $this->fields["percdia11"]);
	}

	function getdia12(){
		return $this->fields["dia12"];
	}

	function getpercdia12($format = FALSE){
		return ($format ? number_format($this->fields["percdia12"],4,",","") : $this->fields["percdia12"]);
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getqtdeparcelas(){
		return $this->fields["qtdeparcelas"];
	}

	function getcomentrada(){
		return $this->fields["comentrada"];
	}

	function getdiavencimento(){
		return $this->fields["diavencimento"];
	}

	function getdiascarencia(){
		return $this->fields["diascarencia"];
	}

	function getdiaprimeiraentrada(){
		return $this->fields["diaprimeiraentrada"];
	}

	function getrecorrencia(){
		return $this->fields["recorrencia"];
	}

	function getexibepontovenda(){
		return $this->fields["exibepontovenda"];
	}

	function getfinancpercentual($format = FALSE){
		return ($format ? number_format($this->fields["financpercentual"], 2, ",", "") : $this->fields["financpercentual"]);
	}

	function getfinanctppessoa(){
		return $this->fields["financtppessoa"];
	}

	function getexibepontooperacao(){
		return $this->fields["exibepontooperacao"];
	}

	/*OS 5240 - campos para controlar se usa preço de oferta quando produto estiver em oferta e se usar a oferta se pode calcular desconto sobr eestes produto em oferta*/
	function getpermiteprecooferta(){
		return $this->fields["permiteprecooferta"];
	}

	function getpermdescprecooferta(){
		return $this->fields["permdescprecooferta"];
	}
	/*OS 5240*/
	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setdia1($value){
		$this->fields["dia1"] = value_numeric($value);
	}

	function setpercdia1($value){
		$this->fields["percdia1"] = value_numeric($value);
	}

	function setdia2($value){
		$this->fields["dia2"] = value_numeric($value);
	}

	function setpercdia2($value){
		$this->fields["percdia2"] = value_numeric($value);
	}

	function setdia3($value){
		$this->fields["dia3"] = value_numeric($value);
	}

	function setpercdia3($value){
		$this->fields["percdia3"] = value_numeric($value);
	}

	function setdia4($value){
		$this->fields["dia4"] = value_numeric($value);
	}

	function setpercdia4($value){
		$this->fields["percdia4"] = value_numeric($value);
	}

	function setdia5($value){
		$this->fields["dia5"] = value_numeric($value);
	}

	function setpercdia5($value){
		$this->fields["percdia5"] = value_numeric($value);
	}

	function setdia6($value){
		$this->fields["dia6"] = value_numeric($value);
	}

	function setpercdia6($value){
		$this->fields["percdia6"] = value_numeric($value);
	}

	function setdia7($value){
		$this->fields["dia7"] = value_numeric($value);
	}

	function setpercdia7($value){
		$this->fields["percdia7"] = value_numeric($value);
	}

	function setdia8($value){
		$this->fields["dia8"] = value_numeric($value);
	}

	function setpercdia8($value){
		$this->fields["percdia8"] = value_numeric($value);
	}

	function setdia9($value){
		$this->fields["dia9"] = value_numeric($value);
	}

	function setpercdia9($value){
		$this->fields["percdia9"] = value_numeric($value);
	}

	function setdia10($value){
		$this->fields["dia10"] = value_numeric($value);
	}

	function setpercdia10($value){
		$this->fields["percdia10"] = value_numeric($value);
	}

	function setdia11($value){
		$this->fields["dia11"] = value_numeric($value);
	}

	function setpercdia11($value){
		$this->fields["percdia11"] = value_numeric($value);
	}

	function setdia12($value){
		$this->fields["dia12"] = value_numeric($value);
	}

	function setpercdia12($value){
		$this->fields["percdia12"] = value_numeric($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,3);
	}

	function setqtdeparcelas($value){
		$this->fields["qtdeparcelas"] = value_numeric($value);
	}

	function setcomentrada($value){
		$this->fields["comentrada"] = value_string($value,1);
	}

	function setdiavencimento($value){
		$this->fields["diavencimento"] = value_numeric($value);
	}

	function setdiascarencia($value){
		$this->fields["diascarencia"] = value_numeric($value);
	}

	function setdiaprimeiraentrada($value){
		$this->fields["diaprimeiraentrada"] = value_numeric($value);
	}

	function setrecorrencia($value){
		$this->fields["recorrencia"] = value_numeric($value);
	}

	function setexibepontovenda($value){
		$this->fields["exibepontovenda"] = value_string($value, 1);
	}

	function setfinancpercentual($value){
		$this->fields["financpercentual"] = value_numeric($value);
	}

	function setfinanctppessoa($value){
		$this->fields["financtppessoa"] = value_string($value, 1);
	}

	function setexibepontooperacao($value){
		$this->fields["exibepontooperacao"] = value_string($value, 1);
	}
	/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
	function setpermiteprecooferta($value){
		$this->fields["permiteprecooferta"] = value_string($value, 1);
	}

	function setpermdescprecooferta($value){
		$this->fields["permdescprecooferta"] = value_string($value, 1);
	}
	/*OS 5240*/
}