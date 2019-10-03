<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class EtiqCliente extends Cadastro{
	function __construct($codetiqcliente = NULL){
		parent::__construct();
		$this->table = "etiqcliente";
		$this->primarykey = array("codetiqcliente");
		$this->setcodetiqcliente($codetiqcliente);
		if(!is_null($this->getcodetiqcliente())){
			$this->searchbyobject();
		}
	}

	function getcodetiqcliente(){
		return $this->fields["codetiqcliente"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getaltura(){
		return $this->fields["altura"];
	}

	function getlargura(){
		return $this->fields["largura"];
	}

	function gettemperatura(){
		return $this->fields["temperatura"];
	}

	function gettipodescricao(){
		return $this->fields["tipodescricao"];
	}

	function getshow_codconvenio(){
		return $this->fields["show_codconvenio"];
	}

	function getorie_codconvenio(){
		return $this->fields["orie_codconvenio"];
	}

	function getposx_codconvenio(){
		return $this->fields["posx_codconvenio"];
	}

	function getposy_codconvenio(){
		return $this->fields["posy_codconvenio"];
	}

	function getfont_codconvenio(){
		return $this->fields["font_codconvenio"];
	}

	function getlarg_codconvenio(){
		return $this->fields["larg_codconvenio"];
	}

	function getaltu_codconvenio(){
		return $this->fields["altu_codconvenio"];
	}

	function getshow_nome(){
		return $this->fields["show_nome"];
	}

	function getorie_nome(){
		return $this->fields["orie_nome"];
	}

	function getposx_nome(){
		return $this->fields["posx_nome"];
	}

	function getposy_nome(){
		return $this->fields["posy_nome"];
	}

	function getfont_nome(){
		return $this->fields["font_nome"];
	}

	function getlarg_nome(){
		return $this->fields["larg_nome"];
	}

	function getaltu_nome(){
		return $this->fields["altu_nome"];
	}

	function getshow_convenio(){
		return $this->fields["show_convenio"];
	}

	function getorie_convenio(){
		return $this->fields["orie_convenio"];
	}

	function getposx_convenio(){
		return $this->fields["posx_convenio"];
	}

	function getposy_convenio(){
		return $this->fields["posy_convenio"];
	}

	function getfont_convenio(){
		return $this->fields["font_convenio"];
	}

	function getlarg_convenio(){
		return $this->fields["larg_convenio"];
	}

	function getaltu_convenio(){
		return $this->fields["altu_convenio"];
	}

	function getshow_dtconvenio(){
		return $this->fields["show_dtconvenio"];
	}

	function getorie_dtconvenio(){
		return $this->fields["orie_dtconvenio"];
	}

	function getposx_dtconvenio(){
		return $this->fields["posx_dtconvenio"];
	}

	function getposy_dtconvenio(){
		return $this->fields["posy_dtconvenio"];
	}

	function getfont_dtconvenio(){
		return $this->fields["font_dtconvenio"];
	}

	function getlarg_dtconvenio(){
		return $this->fields["larg_dtconvenio"];
	}

	function getaltu_dtconvenio(){
		return $this->fields["altu_dtconvenio"];
	}

	function getshow_codbarracpfcnpj(){
		return $this->fields["show_codbarracpfcnpj"];
	}

	function getorie_codbarracpfcnpj(){
		return $this->fields["orie_codbarracpfcnpj"];
	}

	function getposx_codbarracpfcnpj(){
		return $this->fields["posx_codbarracpfcnpj"];
	}

	function getposy_codbarracpfcnpj(){
		return $this->fields["posy_codbarracpfcnpj"];
	}

	function getfont_codbarracpfcnpj(){
		return $this->fields["font_codbarracpfcnpj"];
	}

	function getlarg_codbarracpfcnpj(){
		return $this->fields["larg_codbarracpfcnpj"];
	}

	function getaltu_codbarracpfcnpj(){
		return $this->fields["altu_codbarracpfcnpj"];
	}

	function getshow_codbarramatricula(){
		return $this->fields["show_codbarramatricula"];
	}

	function getorie_codbarramatricula(){
		return $this->fields["orie_codbarramatricula"];
	}

	function getposx_codbarramatricula(){
		return $this->fields["posx_codbarramatricula"];
	}

	function getposy_codbarramatricula(){
		return $this->fields["posy_codbarramatricula"];
	}

	function getfont_codbarramatricula(){
		return $this->fields["font_codbarramatricula"];
	}

	function getlarg_codbarramatricula(){
		return $this->fields["larg_codbarramatricula"];
	}

	function getaltu_codbarramatricula(){
		return $this->fields["altu_codbarramatricula"];
	}

	function getshow_codcliente(){
		return $this->fields["show_codcliente"];
	}

	function getorie_codcliente(){
		return $this->fields["orie_codcliente"];
	}

	function getposx_codcliente(){
		return $this->fields["posx_codcliente"];
	}

	function getposy_codcliente(){
		return $this->fields["posy_codcliente"];
	}

	function getfont_codcliente(){
		return $this->fields["font_codcliente"];
	}

	function getlarg_codcliente(){
		return $this->fields["larg_codcliente"];
	}

	function getaltu_codcliente(){
		return $this->fields["altu_codcliente"];
	}

	function getshow_codigo(){
		return $this->fields["show_codigo"];
	}

	function getorie_codigo(){
		return $this->fields["orie_codigo"];
	}

	function getposx_codigo(){
		return $this->fields["posx_codigo"];
	}

	function getposy_codigo(){
		return $this->fields["posy_codigo"];
	}

	function getfont_codigo(){
		return $this->fields["font_codigo"];
	}

	function getlarg_codigo(){
		return $this->fields["larg_codigo"];
	}

	function getaltu_codigo(){
		return $this->fields["altu_codigo"];
	}

	function getnumcarreiras(){
		return $this->fields["numcarreiras"];
	}

	function gettipoconvenio(){
		return $this->fields["tipoconvenio"];
	}

	function getshow_qtdecodigo(){
		return $this->fields["show_qtdecodigo"];
	}

	function getorie_qtdecodigo(){
		return $this->fields["orie_qtdecodigo"];
	}

	function getposx_qtdecodigo(){
		return $this->fields["posx_qtdecodigo"];
	}

	function getposy_qtdecodigo(){
		return $this->fields["posy_qtdecodigo"];
	}

	function getfont_qtdecodigo(){
		return $this->fields["font_qtdecodigo"];
	}

	function getlarg_qtdecodigo(){
		return $this->fields["larg_qtdecodigo"];
	}

	function getaltu_qtdecodigo(){
		return $this->fields["altu_qtdecodigo"];
	}

	function getcpfcnpjerta(){
		return $this->fields["cpfcnpjerta"];
	}

	function getalturafolha(){
		return $this->fields["alturafolha"];
	}

	function getlargurafolha(){
		return $this->fields["largurafolha"];
	}

	function getbordahorizontal(){
		return $this->fields["bordahorizontal"];
	}

	function getbordavertical(){
		return $this->fields["bordavertical"];
	}

	function getcodimpressora(){
		return $this->fields["codimpressora"];
	}

	function getlocalimpressora(){
		return $this->fields["localimpressora"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getshow_cpfcnpj(){
		return $this->fields["show_cpfcnpj"];
	}

	function getorie_cpfcnpj(){
		return $this->fields["orie_cpfcnpj"];
	}

	function getposx_cpfcnpj(){
		return $this->fields["posx_cpfcnpj"];
	}

	function getposy_cpfcnpj(){
		return $this->fields["posy_cpfcnpj"];
	}

	function getfont_cpfcnpj(){
		return $this->fields["font_cpfcnpj"];
	}

	function getlarg_cpfcnpj(){
		return $this->fields["larg_cpfcnpj"];
	}

	function getaltu_cpfcnpj(){
		return $this->fields["altu_cpfcnpj"];
	}

	function getshow_dtatual(){
		return $this->fields["show_dtatual"];
	}

	function getorie_dtatual(){
		return $this->fields["orie_dtatual"];
	}

	function getposx_dtatual(){
		return $this->fields["posx_dtatual"];
	}

	function getposy_dtatual(){
		return $this->fields["posy_dtatual"];
	}

	function getfont_dtatual(){
		return $this->fields["font_dtatual"];
	}

	function getlarg_dtatual(){
		return $this->fields["larg_dtatual"];
	}

	function getaltu_dtatual(){
		return $this->fields["altu_dtatual"];
	}

	function gettext_dtatual(){
		return $this->fields["text_dtatual"];
	}

	function getshow_fornecedor(){
		return $this->fields["show_fornecedor"];
	}

	function getorie_fornecedor(){
		return $this->fields["orie_fornecedor"];
	}

	function getposx_fornecedor(){
		return $this->fields["posx_fornecedor"];
	}

	function getposy_fornecedor(){
		return $this->fields["posy_fornecedor"];
	}

	function getfont_fornecedor(){
		return $this->fields["font_fornecedor"];
	}

	function getlarg_fornecedor(){
		return $this->fields["larg_fornecedor"];
	}

	function getaltu_fornecedor(){
		return $this->fields["altu_fornecedor"];
	}

	function gettext_fornecedor(){
		return $this->fields["text_fornecedor"];
	}

	function getshow_reffornec(){
		return $this->fields["show_reffornec"];
	}

	function getorie_reffornec(){
		return $this->fields["orie_reffornec"];
	}

	function getposx_reffornec(){
		return $this->fields["posx_reffornec"];
	}

	function getposy_reffornec(){
		return $this->fields["posy_reffornec"];
	}

	function getfont_reffornec(){
		return $this->fields["font_reffornec"];
	}

	function getlarg_reffornec(){
		return $this->fields["larg_reffornec"];
	}

	function getaltu_reffornec(){
		return $this->fields["altu_reffornec"];
	}

	function gettext_reffornec(){
		return $this->fields["text_reffornec"];
	}

	function getrecuo(){
		return $this->fields["recuo"];
	}

	function gettiposervidor(){
		return $this->fields["tiposervidor"];
	}

	function getshow_texto1(){
		return $this->fields["show_texto1"];
	}

	function getorie_texto1(){
		return $this->fields["orie_texto1"];
	}

	function getposx_texto1(){
		return $this->fields["posx_texto1"];
	}

	function getposy_texto1(){
		return $this->fields["posy_texto1"];
	}

	function getfont_texto1(){
		return $this->fields["font_texto1"];
	}

	function getlarg_texto1(){
		return $this->fields["larg_texto1"];
	}

	function getaltu_texto1(){
		return $this->fields["altu_texto1"];
	}

	function gettext_texto1(){
		return $this->fields["text_texto1"];
	}

	function getshow_uf(){
		return $this->fields["show_uf"];
	}

	function getorie_uf(){
		return $this->fields["orie_uf"];
	}

	function getposx_uf(){
		return $this->fields["posx_uf"];
	}

	function getposy_uf(){
		return $this->fields["posy_uf"];
	}

	function getfont_uf(){
		return $this->fields["font_uf"];
	}

	function getlarg_uf(){
		return $this->fields["larg_uf"];
	}

	function getaltu_uf(){
		return $this->fields["altu_uf"];
	}

	function getshow_cidade(){
		return $this->fields["show_cidade"];
	}

	function getorie_cidade(){
		return $this->fields["orie_cidade"];
	}

	function getposx_cidade(){
		return $this->fields["posx_cidade"];
	}

	function getposy_cidade(){
		return $this->fields["posy_cidade"];
	}

	function getfont_cidade(){
		return $this->fields["font_cidade"];
	}

	function getlarg_cidade(){
		return $this->fields["larg_cidade"];
	}

	function getaltu_cidade(){
		return $this->fields["altu_cidade"];
	}

	function getshow_cepent(){
		return $this->fields["show_cepent"];
	}

	function getorie_cepent(){
		return $this->fields["orie_cepent"];
	}

	function getposx_cepent(){
		return $this->fields["posx_cepent"];
	}

	function getposy_cepent(){
		return $this->fields["posy_cepent"];
	}

	function getfont_cepent(){
		return $this->fields["font_cepent"];
	}

	function getlarg_cepent(){
		return $this->fields["larg_cepent"];
	}

	function getaltu_cepent(){
		return $this->fields["altu_cepent"];
	}

	function getshow_enderecoent(){
		return $this->fields["show_enderecoent"];
	}

	function getorie_enderecoent(){
		return $this->fields["orie_enderecoent"];
	}

	function getposx_enderecoent(){
		return $this->fields["posx_enderecoent"];
	}

	function getposy_enderecoent(){
		return $this->fields["posy_enderecoent"];
	}

	function getfont_enderecoent(){
		return $this->fields["font_enderecoent"];
	}

	function getlarg_enderecoent(){
		return $this->fields["larg_enderecoent"];
	}

	function getaltu_enderecoent(){
		return $this->fields["altu_enderecoent"];
	}

	function getshow_bairroent(){
		return $this->fields["show_bairroent"];
	}

	function getorie_bairroent(){
		return $this->fields["orie_bairroent"];
	}

	function getposx_bairroent(){
		return $this->fields["posx_bairroent"];
	}

	function getposy_bairroent(){
		return $this->fields["posy_bairroent"];
	}

	function getfont_bairroent(){
		return $this->fields["font_bairroent"];
	}

	function getlarg_bairroent(){
		return $this->fields["larg_bairroent"];
	}

	function getaltu_bairroent(){
		return $this->fields["altu_bairroent"];
	}

	function setcodetiqcliente($value){
		$this->fields["codetiqcliente"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setaltura($value){
		$this->fields["altura"] = value_numeric($value);
	}

	function setlargura($value){
		$this->fields["largura"] = value_numeric($value);
	}

	function settemperatura($value){
		$this->fields["temperatura"] = value_numeric($value);
	}

	function settipodescricao($value){
		$this->fields["tipodescricao"] = value_string($value,1);
	}

	function setshow_codconvenio($value){
		$this->fields["show_codconvenio"] = value_string($value,1);
	}

	function setorie_codconvenio($value){
		$this->fields["orie_codconvenio"] = value_numeric($value);
	}

	function setposx_codconvenio($value){
		$this->fields["posx_codconvenio"] = value_numeric($value);
	}

	function setposy_codconvenio($value){
		$this->fields["posy_codconvenio"] = value_numeric($value);
	}

	function setfont_codconvenio($value){
		$this->fields["font_codconvenio"] = value_numeric($value);
	}

	function setlarg_codconvenio($value){
		$this->fields["larg_codconvenio"] = value_numeric($value);
	}

	function setaltu_codconvenio($value){
		$this->fields["altu_codconvenio"] = value_numeric($value);
	}

	function setshow_nome($value){
		$this->fields["show_nome"] = value_string($value,1);
	}

	function setorie_nome($value){
		$this->fields["orie_nome"] = value_numeric($value);
	}

	function setposx_nome($value){
		$this->fields["posx_nome"] = value_numeric($value);
	}

	function setposy_nome($value){
		$this->fields["posy_nome"] = value_numeric($value);
	}

	function setfont_nome($value){
		$this->fields["font_nome"] = value_numeric($value);
	}

	function setlarg_nome($value){
		$this->fields["larg_nome"] = value_numeric($value);
	}

	function setaltu_nome($value){
		$this->fields["altu_nome"] = value_numeric($value);
	}

	function setshow_convenio($value){
		$this->fields["show_convenio"] = value_string($value,1);
	}

	function setorie_convenio($value){
		$this->fields["orie_convenio"] = value_numeric($value);
	}

	function setposx_convenio($value){
		$this->fields["posx_convenio"] = value_numeric($value);
	}

	function setposy_convenio($value){
		$this->fields["posy_convenio"] = value_numeric($value);
	}

	function setfont_convenio($value){
		$this->fields["font_convenio"] = value_numeric($value);
	}

	function setlarg_convenio($value){
		$this->fields["larg_convenio"] = value_numeric($value);
	}

	function setaltu_convenio($value){
		$this->fields["altu_convenio"] = value_numeric($value);
	}

	function setshow_dtconvenio($value){
		$this->fields["show_dtconvenio"] = value_string($value,1);
	}

	function setorie_dtconvenio($value){
		$this->fields["orie_dtconvenio"] = value_numeric($value);
	}

	function setposx_dtconvenio($value){
		$this->fields["posx_dtconvenio"] = value_numeric($value);
	}

	function setposy_dtconvenio($value){
		$this->fields["posy_dtconvenio"] = value_numeric($value);
	}

	function setfont_dtconvenio($value){
		$this->fields["font_dtconvenio"] = value_numeric($value);
	}

	function setlarg_dtconvenio($value){
		$this->fields["larg_dtconvenio"] = value_numeric($value);
	}

	function setaltu_dtconvenio($value){
		$this->fields["altu_dtconvenio"] = value_numeric($value);
	}

	function setshow_codbarracpfcnpj($value){
		$this->fields["show_codbarracpfcnpj"] = value_string($value,1);
	}

	function setorie_codbarracpfcnpj($value){
		$this->fields["orie_codbarracpfcnpj"] = value_numeric($value);
	}

	function setposx_codbarracpfcnpj($value){
		$this->fields["posx_codbarracpfcnpj"] = value_numeric($value);
	}

	function setposy_codbarracpfcnpj($value){
		$this->fields["posy_codbarracpfcnpj"] = value_numeric($value);
	}

	function setfont_codbarracpfcnpj($value){
		$this->fields["font_codbarracpfcnpj"] = value_numeric($value);
	}

	function setlarg_codbarracpfcnpj($value){
		$this->fields["larg_codbarracpfcnpj"] = value_numeric($value);
	}

	function setaltu_codbarracpfcnpj($value){
		$this->fields["altu_codbarracpfcnpj"] = value_numeric($value);
	}

	function setshow_codbarramatricula($value){
		$this->fields["show_codbarramatricula"] = value_string($value,1);
	}

	function setorie_codbarramatricula($value){
		$this->fields["orie_codbarramatricula"] = value_numeric($value);
	}

	function setposx_codbarramatricula($value){
		$this->fields["posx_codbarramatricula"] = value_numeric($value);
	}

	function setposy_codbarramatricula($value){
		$this->fields["posy_codbarramatricula"] = value_numeric($value);
	}

	function setfont_codbarramatricula($value){
		$this->fields["font_codbarramatricula"] = value_numeric($value);
	}

	function setlarg_codbarramatricula($value){
		$this->fields["larg_codbarramatricula"] = value_numeric($value);
	}

	function setaltu_codbarramatricula($value){
		$this->fields["altu_codbarramatricula"] = value_numeric($value);
	}

	function setshow_codcliente($value){
		$this->fields["show_codcliente"] = value_string($value,1);
	}

	function setorie_codcliente($value){
		$this->fields["orie_codcliente"] = value_numeric($value);
	}

	function setposx_codcliente($value){
		$this->fields["posx_codcliente"] = value_numeric($value);
	}

	function setposy_codcliente($value){
		$this->fields["posy_codcliente"] = value_numeric($value);
	}

	function setfont_codcliente($value){
		$this->fields["font_codcliente"] = value_numeric($value);
	}

	function setlarg_codcliente($value){
		$this->fields["larg_codcliente"] = value_numeric($value);
	}

	function setaltu_codcliente($value){
		$this->fields["altu_codcliente"] = value_numeric($value);
	}

	function setshow_codigo($value){
		$this->fields["show_codigo"] = value_string($value,1);
	}

	function setorie_codigo($value){
		$this->fields["orie_codigo"] = value_numeric($value);
	}

	function setposx_codigo($value){
		$this->fields["posx_codigo"] = value_numeric($value);
	}

	function setposy_codigo($value){
		$this->fields["posy_codigo"] = value_numeric($value);
	}

	function setfont_codigo($value){
		$this->fields["font_codigo"] = value_numeric($value);
	}

	function setlarg_codigo($value){
		$this->fields["larg_codigo"] = value_numeric($value);
	}

	function setaltu_codigo($value){
		$this->fields["altu_codigo"] = value_numeric($value);
	}

	function setnumcarreiras($value){
		$this->fields["numcarreiras"] = value_numeric($value);
	}

	function settipoconvenio($value){
		$this->fields["tipoconvenio"] = value_string($value,1);
	}

	function setshow_qtdecodigo($value){
		$this->fields["show_qtdecodigo"] = value_string($value,1);
	}

	function setorie_qtdecodigo($value){
		$this->fields["orie_qtdecodigo"] = value_numeric($value);
	}

	function setposx_qtdecodigo($value){
		$this->fields["posx_qtdecodigo"] = value_numeric($value);
	}

	function setposy_qtdecodigo($value){
		$this->fields["posy_qtdecodigo"] = value_numeric($value);
	}

	function setfont_qtdecodigo($value){
		$this->fields["font_qtdecodigo"] = value_numeric($value);
	}

	function setlarg_qtdecodigo($value){
		$this->fields["larg_qtdecodigo"] = value_numeric($value);
	}

	function setaltu_qtdecodigo($value){
		$this->fields["altu_qtdecodigo"] = value_numeric($value);
	}

	function setcpfcnpjerta($value){
		$this->fields["cpfcnpjerta"] = value_string($value,1);
	}

	function setalturafolha($value){
		$this->fields["alturafolha"] = value_numeric($value);
	}

	function setlargurafolha($value){
		$this->fields["largurafolha"] = value_numeric($value);
	}

	function setbordahorizontal($value){
		$this->fields["bordahorizontal"] = value_numeric($value);
	}

	function setbordavertical($value){
		$this->fields["bordavertical"] = value_numeric($value);
	}

	function setcodimpressora($value){
		$this->fields["codimpressora"] = value_numeric($value);
	}

	function setlocalimpressora($value){
		$this->fields["localimpressora"] = value_string($value,200);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setshow_cpfcnpj($value){
		$this->fields["show_cpfcnpj"] = value_string($value,1);
	}

	function setorie_cpfcnpj($value){
		$this->fields["orie_cpfcnpj"] = value_numeric($value);
	}

	function setposx_cpfcnpj($value){
		$this->fields["posx_cpfcnpj"] = value_numeric($value);
	}

	function setposy_cpfcnpj($value){
		$this->fields["posy_cpfcnpj"] = value_numeric($value);
	}

	function setfont_cpfcnpj($value){
		$this->fields["font_cpfcnpj"] = value_numeric($value);
	}

	function setlarg_cpfcnpj($value){
		$this->fields["larg_cpfcnpj"] = value_numeric($value);
	}

	function setaltu_cpfcnpj($value){
		$this->fields["altu_cpfcnpj"] = value_numeric($value);
	}

	function setshow_dtatual($value){
		$this->fields["show_dtatual"] = value_string($value,1);
	}

	function setorie_dtatual($value){
		$this->fields["orie_dtatual"] = value_numeric($value);
	}

	function setposx_dtatual($value){
		$this->fields["posx_dtatual"] = value_numeric($value);
	}

	function setposy_dtatual($value){
		$this->fields["posy_dtatual"] = value_numeric($value);
	}

	function setfont_dtatual($value){
		$this->fields["font_dtatual"] = value_numeric($value);
	}

	function setlarg_dtatual($value){
		$this->fields["larg_dtatual"] = value_numeric($value);
	}

	function setaltu_dtatual($value){
		$this->fields["altu_dtatual"] = value_numeric($value);
	}

	function settext_dtatual($value){
		$this->fields["text_dtatual"] = value_string($value,300);
	}

	function setshow_fornecedor($value){
		$this->fields["show_fornecedor"] = value_string($value,1);
	}

	function setorie_fornecedor($value){
		$this->fields["orie_fornecedor"] = value_numeric($value);
	}

	function setposx_fornecedor($value){
		$this->fields["posx_fornecedor"] = value_numeric($value);
	}

	function setposy_fornecedor($value){
		$this->fields["posy_fornecedor"] = value_numeric($value);
	}

	function setfont_fornecedor($value){
		$this->fields["font_fornecedor"] = value_numeric($value);
	}

	function setlarg_fornecedor($value){
		$this->fields["larg_fornecedor"] = value_numeric($value);
	}

	function setaltu_fornecedor($value){
		$this->fields["altu_fornecedor"] = value_numeric($value);
	}

	function settext_fornecedor($value){
		$this->fields["text_fornecedor"] = value_string($value,300);
	}

	function setshow_reffornec($value){
		$this->fields["show_reffornec"] = value_string($value,1);
	}

	function setorie_reffornec($value){
		$this->fields["orie_reffornec"] = value_numeric($value);
	}

	function setposx_reffornec($value){
		$this->fields["posx_reffornec"] = value_numeric($value);
	}

	function setposy_reffornec($value){
		$this->fields["posy_reffornec"] = value_numeric($value);
	}

	function setfont_reffornec($value){
		$this->fields["font_reffornec"] = value_numeric($value);
	}

	function setlarg_reffornec($value){
		$this->fields["larg_reffornec"] = value_numeric($value);
	}

	function setaltu_reffornec($value){
		$this->fields["altu_reffornec"] = value_numeric($value);
	}

	function settext_reffornec($value){
		$this->fields["text_reffornec"] = value_string($value,300);
	}

	function setrecuo($value){
		$this->fields["recuo"] = value_numeric($value);
	}

	function settiposervidor($value){
		$this->fields["tiposervidor"] = value_string($value,1);
	}

	function setshow_texto1($value){
		$this->fields["show_texto1"] = value_string($value,1);
	}

	function setorie_texto1($value){
		$this->fields["orie_texto1"] = value_numeric($value);
	}

	function setposx_texto1($value){
		$this->fields["posx_texto1"] = value_numeric($value);
	}

	function setposy_texto1($value){
		$this->fields["posy_texto1"] = value_numeric($value);
	}

	function setfont_texto1($value){
		$this->fields["font_texto1"] = value_numeric($value);
	}

	function setlarg_texto1($value){
		$this->fields["larg_texto1"] = value_numeric($value);
	}

	function setaltu_texto1($value){
		$this->fields["altu_texto1"] = value_numeric($value);
	}

	function settext_texto1($value){
		$this->fields["text_texto1"] = value_string($value,300);
	}

	function setshow_uf($value){
		$this->fields["show_uf"] = value_string($value,1);
	}

	function setorie_uf($value){
		$this->fields["orie_uf"] = value_numeric($value);
	}

	function setposx_uf($value){
		$this->fields["posx_uf"] = value_numeric($value);
	}

	function setposy_uf($value){
		$this->fields["posy_uf"] = value_numeric($value);
	}

	function setfont_uf($value){
		$this->fields["font_uf"] = value_numeric($value);
	}

	function setlarg_uf($value){
		$this->fields["larg_uf"] = value_numeric($value);
	}

	function setaltu_uf($value){
		$this->fields["altu_uf"] = value_numeric($value);
	}

	function setshow_cidade($value){
		$this->fields["show_cidade"] = value_string($value,1);
	}

	function setorie_cidade($value){
		$this->fields["orie_cidade"] = value_numeric($value);
	}

	function setposx_cidade($value){
		$this->fields["posx_cidade"] = value_numeric($value);
	}

	function setposy_cidade($value){
		$this->fields["posy_cidade"] = value_numeric($value);
	}

	function setfont_cidade($value){
		$this->fields["font_cidade"] = value_numeric($value);
	}

	function setlarg_cidade($value){
		$this->fields["larg_cidade"] = value_numeric($value);
	}

	function setaltu_cidade($value){
		$this->fields["altu_cidade"] = value_numeric($value);
	}

	function setshow_cepent($value){
		$this->fields["show_cepent"] = value_string($value,1);
	}

	function setorie_cepent($value){
		$this->fields["orie_cepent"] = value_numeric($value);
	}

	function setposx_cepent($value){
		$this->fields["posx_cepent"] = value_numeric($value);
	}

	function setposy_cepent($value){
		$this->fields["posy_cepent"] = value_numeric($value);
	}

	function setfont_cepent($value){
		$this->fields["font_cepent"] = value_numeric($value);
	}

	function setlarg_cepent($value){
		$this->fields["larg_cepent"] = value_numeric($value);
	}

	function setaltu_cepent($value){
		$this->fields["altu_cepent"] = value_numeric($value);
	}

	function setshow_enderecoent($value){
		$this->fields["show_enderecoent"] = value_string($value,1);
	}

	function setorie_enderecoent($value){
		$this->fields["orie_enderecoent"] = value_numeric($value);
	}

	function setposx_enderecoent($value){
		$this->fields["posx_enderecoent"] = value_numeric($value);
	}

	function setposy_enderecoent($value){
		$this->fields["posy_enderecoent"] = value_numeric($value);
	}

	function setfont_enderecoent($value){
		$this->fields["font_enderecoent"] = value_numeric($value);
	}

	function setlarg_enderecoent($value){
		$this->fields["larg_enderecoent"] = value_numeric($value);
	}

	function setaltu_enderecoent($value){
		$this->fields["altu_enderecoent"] = value_numeric($value);
	}

	function setshow_bairroent($value){
		$this->fields["show_bairroent"] = value_string($value,1);
	}

	function setorie_bairroent($value){
		$this->fields["orie_bairroent"] = value_numeric($value);
	}

	function setposx_bairroent($value){
		$this->fields["posx_bairroent"] = value_numeric($value);
	}

	function setposy_bairroent($value){
		$this->fields["posy_bairroent"] = value_numeric($value);
	}

	function setfont_bairroent($value){
		$this->fields["font_bairroent"] = value_numeric($value);
	}

	function setlarg_bairroent($value){
		$this->fields["larg_bairroent"] = value_numeric($value);
	}

	function setaltu_bairroent($value){
		$this->fields["altu_bairroent"] = value_numeric($value);
	}
}
?>