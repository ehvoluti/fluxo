<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamEcommerce extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "paramecommerce";
		$this->primarykey = array("codestabelec");
		$this->setcodestabelec($codestabelec);

		if($this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getsite(){
		return $this->fields["site"];
	}

	function getemail(){
		return $this->fields["email"];
	}

	function gettelefone(){
		return $this->fields["telefone"];
	}

	function getfacebook(){
		return $this->fields["facebook"];
	}

	function gettwiter(){
		return $this->fields["twiter"];
	}

	function getgoogleplus(){
		return $this->fields["googleplus"];
	}

	function getblog(){
		return $this->fields["blog"];
	}

	function getinstagram(){
		return $this->fields["instagram"];
	}

	function getwhatsapp(){
		return $this->fields["whatsapp"];
	}

	function gettemacor(){
		return $this->fields["temacor"];
	}

	function gettematipo(){
		return $this->fields["tematipo"];
	}

	function getnomeespectecnica(){
		return $this->fields["nomeespectecnica"];
	}

	function getnomeespecieresumida(){
		return $this->fields["nomeespecieresumida"];
	}

	function getpagina1(){
		return $this->fields["pagina1"];
	}

	function getpagina2(){
		return $this->fields["pagina2"];
	}

	function getpagina3(){
		return $this->fields["pagina3"];
	}

	function gettitpag1(){
		return $this->fields["titpag1"];
	}

	function gettitpag2(){
		return $this->fields["titpag2"];
	}

	function gettitpag3(){
		return $this->fields["titpag3"];
	}

	function gettitdepto(){
		return $this->fields["titdepto"];
	}

	function gettitmarca(){
		return $this->fields["titmarca"];
	}

	function getpagseguro_email(){
		return $this->fields["pagseguro_email"];
	}

	function getpagseguro_token(){
		return $this->fields["pagseguro_token"];
	}

	function getjavascript(){
		return $this->fields["javascript"];
	}

	function getanalytics_tag(){
		return $this->fields["analytics_tag"];
	}

	function getativapagseguro(){
		return $this->fields["ativapagseguro"];
	}

	function getativanaentrega(){
		return $this->fields["ativanaentrega"];
	}

	function getativaorcamento(){
		return $this->fields["ativaorcamento"];
	}

	function getstatuscliente(){
		return $this->fields["statuscliente"];
	}

	function getindisponivelsemestoque(){
		return $this->fields["indisponivelsemestoque"];
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getcodmodemailnovocli(){
		return $this->fields["codmodemailnovocli"];
	}

	function getcodmodemailesquecsenha(){
		return $this->fields["codmodemailesquecsenha"];
	}

	function getcodmodemailconfped(){
		return $this->fields["codmodemailconfped"];
	}

	function getqtdprodhome(){
		return $this->fields["qtdprodhome"];
	}

	function getordemprodhome(){
		return $this->fields["ordemprodhome"];
	}

	function getmsgcadastro(){
		return $this->fields["msgcadastro"];
	}

	function getcondpagto(){
		return $this->fields["condpagto"];
	}

	function getcondpagtotipo(){
		return $this->fields["condpagtotipo"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getdirimagem(){
		return $this->fields["dirimagem"];
	}	

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value);
	}

	function setcondpagtotipo($value){
		$this->fields["condpagtotipo"] = value_string($value);
	}

	function setcondpagto($value){
		$this->fields["condpagto"] = value_string($value);
	}

	function setmsgcadastro($value){
		$this->fields["msgcadastro"] = value_string($value);
	}

	function setordemprodhome($value){
		$this->fields["ordemprodhome"] = value_string($value);
	}

	function setqtdprodhome($value){
		$this->fields["qtdprodhome"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setsite($value){
		$this->fields["site"] = value_string($value,80);
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,80);
	}

	function settelefone($value){
		$this->fields["telefone"] = value_string($value,30);
	}

	function setfacebook($value){
		$this->fields["facebook"] = value_string($value,80);
	}

	function settwiter($value){
		$this->fields["twiter"] = value_string($value,80);
	}

	function setgoogleplus($value){
		$this->fields["googleplus"] = value_string($value,80);
	}

	function setblog($value){
		$this->fields["blog"] = value_string($value,80);
	}

	function setinstagram($value){
		$this->fields["instagram"] = value_string($value,80);
	}

	function setwhatsapp($value){
		$this->fields["whatsapp"] = value_string($value,80);
	}

	function settemacor($value){
		$this->fields["temacor"] = value_string($value,7);
	}

	function settematipo($value){
		$this->fields["tematipo"] = value_string($value,1);
	}

	function setnomeespectecnica($value){
		$this->fields["nomeespectecnica"] = value_string($value,80);
	}

	function setnomeespecieresumida($value){
		$this->fields["nomeespecieresumida"] = value_string($value,80);
	}

	function setpagina1($value){
		$this->fields["pagina1"] = value_string($value);
	}

	function setpagina2($value){
		$this->fields["pagina2"] = value_string($value);
	}

	function setpagina3($value){
		$this->fields["pagina3"] = value_string($value);
	}

	function settitpag1($value){
		$this->fields["titpag1"] = value_string($value,40);
	}

	function settitpag2($value){
		$this->fields["titpag2"] = value_string($value,40);
	}

	function settitpag3($value){
		$this->fields["titpag3"] = value_string($value,40);
	}

	function settitdepto($value){
		$this->fields["titdepto"] = value_string($value,40);
	}

	function settitmarca($value){
		$this->fields["titmarca"] = value_string($value,40);
	}

	function setpagseguro_email($value){
		$this->fields["pagseguro_email"] = value_string($value,200);
	}

	function setpagseguro_token($value){
		$this->fields["pagseguro_token"] = value_string($value,200);
	}

	function setjavascript($value){
		$this->fields["javascript"] = value_string($value);
	}

	function setanalytics_tag($value){
		$this->fields["analytics_tag"] = value_string($value);
	}

	function setativapagseguro($value){
		$this->fields["ativapagseguro"] = value_string($value,1);
	}

	function setativanaentrega($value){
		$this->fields["ativanaentrega"] = value_string($value,1);
	}

	function setativaorcamento($value){
		$this->fields["ativaorcamento"] = value_string($value,1);
	}

	function setstatuscliente($value){
		$this->fields["statuscliente"] = value_numeric($value);
	}

	function setindisponivelsemestoque($value){
		$this->fields["indisponivelsemestoque"] = value_string($value,1);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setcodmodemailnovocli($value){
		$this->fields["codmodemailnovocli"] = value_numeric($value);
	}

	function setcodmodemailesquecsenha($value){
		$this->fields["codmodemailesquecsenha"] = value_numeric($value);
	}

	function setcodmodemailconfped($value){
		$this->fields["codmodemailconfped"] = value_numeric($value);
	}

	function setdirimagem($values){
		$this->fields["dirimagem"] = value_string($value, 100);
	}
}