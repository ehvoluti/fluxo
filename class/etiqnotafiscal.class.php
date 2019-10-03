<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class EtiqNotaFiscal extends Cadastro{
	function __construct($codetiqnotafiscal = NULL){
		parent::__construct();
		$this->table = "etiqnotafiscal";
		$this->primarykey = array("codetiqnotafiscal");
		$this->setcodetiqnotafiscal($codetiqnotafiscal);
		if(!is_null($this->getcodetiqnotafiscal())){
			$this->searchbyobject();
		}
	}

	function getcodetiqnotafiscal(){
		return $this->fields["codetiqnotafiscal"];
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

	function getnumcarreiras(){
		return $this->fields["numcarreiras"];
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

	function getbox(){
		return $this->fields["box"];
	}

	function gettiposervidor(){
		return $this->fields["tiposervidor"];
	}

	function getrecuo(){
		return $this->fields["recuo"];
	}

	function getshow_nomeestabelec(){
		return $this->fields["show_nomeestabelec"];
	}

	function getorie_nomeestabelec(){
		return $this->fields["orie_nomeestabelec"];
	}

	function getposx_nomeestabelec(){
		return $this->fields["posx_nomeestabelec"];
	}

	function getposy_nomeestabelec(){
		return $this->fields["posy_nomeestabelec"];
	}

	function getfont_nomeestabelec(){
		return $this->fields["font_nomeestabelec"];
	}

	function getlarg_nomeestabelec(){
		return $this->fields["larg_nomeestabelec"];
	}

	function getaltu_nomeestabelec(){
		return $this->fields["altu_nomeestabelec"];
	}

	function getshow_origemendereco(){
		return $this->fields["show_origemendereco"];
	}

	function getorie_origemendereco(){
		return $this->fields["orie_origemendereco"];
	}

	function getposx_origemendereco(){
		return $this->fields["posx_origemendereco"];
	}

	function getposy_origemendereco(){
		return $this->fields["posy_origemendereco"];
	}

	function getfont_origemendereco(){
		return $this->fields["font_origemendereco"];
	}

	function getlarg_origemendereco(){
		return $this->fields["larg_origemendereco"];
	}

	function getaltu_origemendereco(){
		return $this->fields["altu_origemendereco"];
	}

	function getshow_origembairro(){
		return $this->fields["show_origembairro"];
	}

	function getorie_origembairro(){
		return $this->fields["orie_origembairro"];
	}

	function getposx_origembairro(){
		return $this->fields["posx_origembairro"];
	}

	function getposy_origembairro(){
		return $this->fields["posy_origembairro"];
	}

	function getfont_origembairro(){
		return $this->fields["font_origembairro"];
	}

	function getlarg_origembairro(){
		return $this->fields["larg_origembairro"];
	}

	function getaltu_origembairro(){
		return $this->fields["altu_origembairro"];
	}

	function getshow_origemcep(){
		return $this->fields["show_origemcep"];
	}

	function getorie_origemcep(){
		return $this->fields["orie_origemcep"];
	}

	function getposx_origemcep(){
		return $this->fields["posx_origemcep"];
	}

	function getposy_origemcep(){
		return $this->fields["posy_origemcep"];
	}

	function getfont_origemcep(){
		return $this->fields["font_origemcep"];
	}

	function getlarg_origemcep(){
		return $this->fields["larg_origemcep"];
	}

	function getaltu_origemcep(){
		return $this->fields["altu_origemcep"];
	}

	function getshow_origemcidadeuf(){
		return $this->fields["show_origemcidadeuf"];
	}

	function getorie_origemcidadeuf(){
		return $this->fields["orie_origemcidadeuf"];
	}

	function getposx_origemcidadeuf(){
		return $this->fields["posx_origemcidadeuf"];
	}

	function getposy_origemcidadeuf(){
		return $this->fields["posy_origemcidadeuf"];
	}

	function getfont_origemcidadeuf(){
		return $this->fields["font_origemcidadeuf"];
	}

	function getlarg_origemcidadeuf(){
		return $this->fields["larg_origemcidadeuf"];
	}

	function getaltu_origemcidadeuf(){
		return $this->fields["altu_origemcidadeuf"];
	}

	function getshow_parceironome(){
		return $this->fields["show_parceironome"];
	}

	function getorie_parceironome(){
		return $this->fields["orie_parceironome"];
	}

	function getposx_parceironome(){
		return $this->fields["posx_parceironome"];
	}

	function getposy_parceironome(){
		return $this->fields["posy_parceironome"];
	}

	function getfont_parceironome(){
		return $this->fields["font_parceironome"];
	}

	function getlarg_parceironome(){
		return $this->fields["larg_parceironome"];
	}

	function getaltu_parceironome(){
		return $this->fields["altu_parceironome"];
	}

	function getshow_destinoendereco(){
		return $this->fields["show_destinoendereco"];
	}

	function getorie_destinoendereco(){
		return $this->fields["orie_destinoendereco"];
	}

	function getposx_destinoendereco(){
		return $this->fields["posx_destinoendereco"];
	}

	function getposy_destinoendereco(){
		return $this->fields["posy_destinoendereco"];
	}

	function getfont_destinoendereco(){
		return $this->fields["font_destinoendereco"];
	}

	function getlarg_destinoendereco(){
		return $this->fields["larg_destinoendereco"];
	}

	function getaltu_destinoendereco(){
		return $this->fields["altu_destinoendereco"];
	}

	function getshow_destinobairro(){
		return $this->fields["show_destinobairro"];
	}

	function getorie_destinobairro(){
		return $this->fields["orie_destinobairro"];
	}

	function getposx_destinobairro(){
		return $this->fields["posx_destinobairro"];
	}

	function getposy_destinobairro(){
		return $this->fields["posy_destinobairro"];
	}

	function getfont_destinobairro(){
		return $this->fields["font_destinobairro"];
	}

	function getlarg_destinobairro(){
		return $this->fields["larg_destinobairro"];
	}

	function getaltu_destinobairro(){
		return $this->fields["altu_destinobairro"];
	}

	function getshow_destinocep(){
		return $this->fields["show_destinocep"];
	}

	function getorie_destinocep(){
		return $this->fields["orie_destinocep"];
	}

	function getposx_destinocep(){
		return $this->fields["posx_destinocep"];
	}

	function getposy_destinocep(){
		return $this->fields["posy_destinocep"];
	}

	function getfont_destinocep(){
		return $this->fields["font_destinocep"];
	}

	function getlarg_destinocep(){
		return $this->fields["larg_destinocep"];
	}

	function getaltu_destinocep(){
		return $this->fields["altu_destinocep"];
	}

	function getshow_destinocidadeuf(){
		return $this->fields["show_destinocidadeuf"];
	}

	function getorie_destinocidadeuf(){
		return $this->fields["orie_destinocidadeuf"];
	}

	function getposx_destinocidadeuf(){
		return $this->fields["posx_destinocidadeuf"];
	}

	function getposy_destinocidadeuf(){
		return $this->fields["posy_destinocidadeuf"];
	}

	function getfont_destinocidadeuf(){
		return $this->fields["font_destinocidadeuf"];
	}

	function getlarg_destinocidadeuf(){
		return $this->fields["larg_destinocidadeuf"];
	}

	function getaltu_destinocidadeuf(){
		return $this->fields["altu_destinocidadeuf"];
	}

	function getshow_numnotafis(){
		return $this->fields["show_numnotafis"];
	}

	function getorie_numnotafis(){
		return $this->fields["orie_numnotafis"];
	}

	function getposx_numnotafis(){
		return $this->fields["posx_numnotafis"];
	}

	function getposy_numnotafis(){
		return $this->fields["posy_numnotafis"];
	}

	function getfont_numnotafis(){
		return $this->fields["font_numnotafis"];
	}

	function getlarg_numnotafis(){
		return $this->fields["larg_numnotafis"];
	}

	function getaltu_numnotafis(){
		return $this->fields["altu_numnotafis"];
	}

	function getshow_numpedido(){
		return $this->fields["show_numpedido"];
	}

	function getorie_numpedido(){
		return $this->fields["orie_numpedido"];
	}

	function getposx_numpedido(){
		return $this->fields["posx_numpedido"];
	}

	function getposy_numpedido(){
		return $this->fields["posy_numpedido"];
	}

	function getfont_numpedido(){
		return $this->fields["font_numpedido"];
	}

	function getlarg_numpedido(){
		return $this->fields["larg_numpedido"];
	}

	function getaltu_numpedido(){
		return $this->fields["altu_numpedido"];
	}

	function getshow_transportadoranome(){
		return $this->fields["show_transportadoranome"];
	}

	function getorie_transportadoranome(){
		return $this->fields["orie_transportadoranome"];
	}

	function getposx_transportadoranome(){
		return $this->fields["posx_transportadoranome"];
	}

	function getposy_transportadoranome(){
		return $this->fields["posy_transportadoranome"];
	}

	function getfont_transportadoranome(){
		return $this->fields["font_transportadoranome"];
	}

	function getlarg_transportadoranome(){
		return $this->fields["larg_transportadoranome"];
	}

	function getaltu_transportadoranome(){
		return $this->fields["altu_transportadoranome"];
	}

	function getshow_transpvolume(){
		return $this->fields["show_transpvolume"];
	}

	function getorie_transpvolume(){
		return $this->fields["orie_transpvolume"];
	}

	function getposx_transpvolume(){
		return $this->fields["posx_transpvolume"];
	}

	function getposy_transpvolume(){
		return $this->fields["posy_transpvolume"];
	}

	function getfont_transpvolume(){
		return $this->fields["font_transpvolume"];
	}

	function getlarg_transpvolume(){
		return $this->fields["larg_transpvolume"];
	}

	function getaltu_transpvolume(){
		return $this->fields["altu_transpvolume"];
	}

	function getshow_transppeso(){
		return $this->fields["show_transppeso"];
	}

	function getorie_transppeso(){
		return $this->fields["orie_transppeso"];
	}

	function getposx_transppeso(){
		return $this->fields["posx_transppeso"];
	}

	function getposy_transppeso(){
		return $this->fields["posy_transppeso"];
	}

	function getfont_transppeso(){
		return $this->fields["font_transppeso"];
	}

	function getlarg_transppeso(){
		return $this->fields["larg_transppeso"];
	}

	function getaltu_transppeso(){
		return $this->fields["altu_transppeso"];
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

	function getshow_texto2(){
		return $this->fields["show_texto2"];
	}

	function getorie_texto2(){
		return $this->fields["orie_texto2"];
	}

	function getposx_texto2(){
		return $this->fields["posx_texto2"];
	}

	function getposy_texto2(){
		return $this->fields["posy_texto2"];
	}

	function getfont_texto2(){
		return $this->fields["font_texto2"];
	}

	function getlarg_texto2(){
		return $this->fields["larg_texto2"];
	}

	function getaltu_texto2(){
		return $this->fields["altu_texto2"];
	}

	function gettext_texto2(){
		return $this->fields["text_texto2"];
	}

	function getshow_texto3(){
		return $this->fields["show_texto3"];
	}

	function getorie_texto3(){
		return $this->fields["orie_texto3"];
	}

	function getposx_texto3(){
		return $this->fields["posx_texto3"];
	}

	function getposy_texto3(){
		return $this->fields["posy_texto3"];
	}

	function getfont_texto3(){
		return $this->fields["font_texto3"];
	}

	function getlarg_texto3(){
		return $this->fields["larg_texto3"];
	}

	function getaltu_texto3(){
		return $this->fields["altu_texto3"];
	}

	function gettext_texto3(){
		return $this->fields["text_texto3"];
	}

	function getshow_texto4(){
		return $this->fields["show_texto4"];
	}

	function getorie_texto4(){
		return $this->fields["orie_texto4"];
	}

	function getposx_texto4(){
		return $this->fields["posx_texto4"];
	}

	function getposy_texto4(){
		return $this->fields["posy_texto4"];
	}

	function getfont_texto4(){
		return $this->fields["font_texto4"];
	}

	function getlarg_texto4(){
		return $this->fields["larg_texto4"];
	}

	function getaltu_texto4(){
		return $this->fields["altu_texto4"];
	}

	function gettext_texto4(){
		return $this->fields["text_texto4"];
	}

	function getshow_texto5(){
		return $this->fields["show_texto5"];
	}

	function getorie_texto5(){
		return $this->fields["orie_texto5"];
	}

	function getposx_texto5(){
		return $this->fields["posx_texto5"];
	}

	function getposy_texto5(){
		return $this->fields["posy_texto5"];
	}

	function getfont_texto5(){
		return $this->fields["font_texto5"];
	}

	function getlarg_texto5(){
		return $this->fields["larg_texto5"];
	}

	function getaltu_texto5(){
		return $this->fields["altu_texto5"];
	}

	function gettext_texto5(){
		return $this->fields["text_texto5"];
	}

	function setcodetiqnotafiscal($value){
		$this->fields["codetiqnotafiscal"] = value_numeric($value);
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

	function setnumcarreiras($value){
		$this->fields["numcarreiras"] = value_numeric($value);
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

	function setbox($value){
		$this->fields["box"] = value_string($value,1);
	}

	function settiposervidor($value){
		$this->fields["tiposervidor"] = value_string($value,1);
	}

	function setrecuo($value){
		$this->fields["recuo"] = value_numeric($value);
	}

	function setshow_nomeestabelec($value){
		$this->fields["show_nomeestabelec"] = value_string($value,1);
	}

	function setorie_nomeestabelec($value){
		$this->fields["orie_nomeestabelec"] = value_numeric($value);
	}

	function setposx_nomeestabelec($value){
		$this->fields["posx_nomeestabelec"] = value_numeric($value);
	}

	function setposy_nomeestabelec($value){
		$this->fields["posy_nomeestabelec"] = value_numeric($value);
	}

	function setfont_nomeestabelec($value){
		$this->fields["font_nomeestabelec"] = value_numeric($value);
	}

	function setlarg_nomeestabelec($value){
		$this->fields["larg_nomeestabelec"] = value_numeric($value);
	}

	function setaltu_nomeestabelec($value){
		$this->fields["altu_nomeestabelec"] = value_numeric($value);
	}

	function setshow_origemendereco($value){
		$this->fields["show_origemendereco"] = value_string($value,1);
	}

	function setorie_origemendereco($value){
		$this->fields["orie_origemendereco"] = value_numeric($value);
	}

	function setposx_origemendereco($value){
		$this->fields["posx_origemendereco"] = value_numeric($value);
	}

	function setposy_origemendereco($value){
		$this->fields["posy_origemendereco"] = value_numeric($value);
	}

	function setfont_origemendereco($value){
		$this->fields["font_origemendereco"] = value_numeric($value);
	}

	function setlarg_origemendereco($value){
		$this->fields["larg_origemendereco"] = value_numeric($value);
	}

	function setaltu_origemendereco($value){
		$this->fields["altu_origemendereco"] = value_numeric($value);
	}

	function setshow_origembairro($value){
		$this->fields["show_origembairro"] = value_string($value,1);
	}

	function setorie_origembairro($value){
		$this->fields["orie_origembairro"] = value_numeric($value);
	}

	function setposx_origembairro($value){
		$this->fields["posx_origembairro"] = value_numeric($value);
	}

	function setposy_origembairro($value){
		$this->fields["posy_origembairro"] = value_numeric($value);
	}

	function setfont_origembairro($value){
		$this->fields["font_origembairro"] = value_numeric($value);
	}

	function setlarg_origembairro($value){
		$this->fields["larg_origembairro"] = value_numeric($value);
	}

	function setaltu_origembairro($value){
		$this->fields["altu_origembairro"] = value_numeric($value);
	}

	function setshow_origemcep($value){
		$this->fields["show_origemcep"] = value_string($value,1);
	}

	function setorie_origemcep($value){
		$this->fields["orie_origemcep"] = value_numeric($value);
	}

	function setposx_origemcep($value){
		$this->fields["posx_origemcep"] = value_numeric($value);
	}

	function setposy_origemcep($value){
		$this->fields["posy_origemcep"] = value_numeric($value);
	}

	function setfont_origemcep($value){
		$this->fields["font_origemcep"] = value_numeric($value);
	}

	function setlarg_origemcep($value){
		$this->fields["larg_origemcep"] = value_numeric($value);
	}

	function setaltu_origemcep($value){
		$this->fields["altu_origemcep"] = value_numeric($value);
	}

	function setshow_origemcidadeuf($value){
		$this->fields["show_origemcidadeuf"] = value_string($value,1);
	}

	function setorie_origemcidadeuf($value){
		$this->fields["orie_origemcidadeuf"] = value_numeric($value);
	}

	function setposx_origemcidadeuf($value){
		$this->fields["posx_origemcidadeuf"] = value_numeric($value);
	}

	function setposy_origemcidadeuf($value){
		$this->fields["posy_origemcidadeuf"] = value_numeric($value);
	}

	function setfont_origemcidadeuf($value){
		$this->fields["font_origemcidadeuf"] = value_numeric($value);
	}

	function setlarg_origemcidadeuf($value){
		$this->fields["larg_origemcidadeuf"] = value_numeric($value);
	}

	function setaltu_origemcidadeuf($value){
		$this->fields["altu_origemcidadeuf"] = value_numeric($value);
	}

	function setshow_parceironome($value){
		$this->fields["show_parceironome"] = value_string($value,1);
	}

	function setorie_parceironome($value){
		$this->fields["orie_parceironome"] = value_numeric($value);
	}

	function setposx_parceironome($value){
		$this->fields["posx_parceironome"] = value_numeric($value);
	}

	function setposy_parceironome($value){
		$this->fields["posy_parceironome"] = value_numeric($value);
	}

	function setfont_parceironome($value){
		$this->fields["font_parceironome"] = value_numeric($value);
	}

	function setlarg_parceironome($value){
		$this->fields["larg_parceironome"] = value_numeric($value);
	}

	function setaltu_parceironome($value){
		$this->fields["altu_parceironome"] = value_numeric($value);
	}

	function setshow_destinoendereco($value){
		$this->fields["show_destinoendereco"] = value_string($value,1);
	}

	function setorie_destinoendereco($value){
		$this->fields["orie_destinoendereco"] = value_numeric($value);
	}

	function setposx_destinoendereco($value){
		$this->fields["posx_destinoendereco"] = value_numeric($value);
	}

	function setposy_destinoendereco($value){
		$this->fields["posy_destinoendereco"] = value_numeric($value);
	}

	function setfont_destinoendereco($value){
		$this->fields["font_destinoendereco"] = value_numeric($value);
	}

	function setlarg_destinoendereco($value){
		$this->fields["larg_destinoendereco"] = value_numeric($value);
	}

	function setaltu_destinoendereco($value){
		$this->fields["altu_destinoendereco"] = value_numeric($value);
	}

	function setshow_destinobairro($value){
		$this->fields["show_destinobairro"] = value_string($value,1);
	}

	function setorie_destinobairro($value){
		$this->fields["orie_destinobairro"] = value_numeric($value);
	}

	function setposx_destinobairro($value){
		$this->fields["posx_destinobairro"] = value_numeric($value);
	}

	function setposy_destinobairro($value){
		$this->fields["posy_destinobairro"] = value_numeric($value);
	}

	function setfont_destinobairro($value){
		$this->fields["font_destinobairro"] = value_numeric($value);
	}

	function setlarg_destinobairro($value){
		$this->fields["larg_destinobairro"] = value_numeric($value);
	}

	function setaltu_destinobairro($value){
		$this->fields["altu_destinobairro"] = value_numeric($value);
	}

	function setshow_destinocep($value){
		$this->fields["show_destinocep"] = value_string($value,1);
	}

	function setorie_destinocep($value){
		$this->fields["orie_destinocep"] = value_numeric($value);
	}

	function setposx_destinocep($value){
		$this->fields["posx_destinocep"] = value_numeric($value);
	}

	function setposy_destinocep($value){
		$this->fields["posy_destinocep"] = value_numeric($value);
	}

	function setfont_destinocep($value){
		$this->fields["font_destinocep"] = value_numeric($value);
	}

	function setlarg_destinocep($value){
		$this->fields["larg_destinocep"] = value_numeric($value);
	}

	function setaltu_destinocep($value){
		$this->fields["altu_destinocep"] = value_numeric($value);
	}

	function setshow_destinocidadeuf($value){
		$this->fields["show_destinocidadeuf"] = value_string($value,1);
	}

	function setorie_destinocidadeuf($value){
		$this->fields["orie_destinocidadeuf"] = value_numeric($value);
	}

	function setposx_destinocidadeuf($value){
		$this->fields["posx_destinocidadeuf"] = value_numeric($value);
	}

	function setposy_destinocidadeuf($value){
		$this->fields["posy_destinocidadeuf"] = value_numeric($value);
	}

	function setfont_destinocidadeuf($value){
		$this->fields["font_destinocidadeuf"] = value_numeric($value);
	}

	function setlarg_destinocidadeuf($value){
		$this->fields["larg_destinocidadeuf"] = value_numeric($value);
	}

	function setaltu_destinocidadeuf($value){
		$this->fields["altu_destinocidadeuf"] = value_numeric($value);
	}

	function setshow_numnotafis($value){
		$this->fields["show_numnotafis"] = value_string($value,1);
	}

	function setorie_numnotafis($value){
		$this->fields["orie_numnotafis"] = value_numeric($value);
	}

	function setposx_numnotafis($value){
		$this->fields["posx_numnotafis"] = value_numeric($value);
	}

	function setposy_numnotafis($value){
		$this->fields["posy_numnotafis"] = value_numeric($value);
	}

	function setfont_numnotafis($value){
		$this->fields["font_numnotafis"] = value_numeric($value);
	}

	function setlarg_numnotafis($value){
		$this->fields["larg_numnotafis"] = value_numeric($value);
	}

	function setaltu_numnotafis($value){
		$this->fields["altu_numnotafis"] = value_numeric($value);
	}

	function setshow_numpedido($value){
		$this->fields["show_numpedido"] = value_string($value,1);
	}

	function setorie_numpedido($value){
		$this->fields["orie_numpedido"] = value_numeric($value);
	}

	function setposx_numpedido($value){
		$this->fields["posx_numpedido"] = value_numeric($value);
	}

	function setposy_numpedido($value){
		$this->fields["posy_numpedido"] = value_numeric($value);
	}

	function setfont_numpedido($value){
		$this->fields["font_numpedido"] = value_numeric($value);
	}

	function setlarg_numpedido($value){
		$this->fields["larg_numpedido"] = value_numeric($value);
	}

	function setaltu_numpedido($value){
		$this->fields["altu_numpedido"] = value_numeric($value);
	}

	function setshow_transportadoranome($value){
		$this->fields["show_transportadoranome"] = value_string($value,1);
	}

	function setorie_transportadoranome($value){
		$this->fields["orie_transportadoranome"] = value_numeric($value);
	}

	function setposx_transportadoranome($value){
		$this->fields["posx_transportadoranome"] = value_numeric($value);
	}

	function setposy_transportadoranome($value){
		$this->fields["posy_transportadoranome"] = value_numeric($value);
	}

	function setfont_transportadoranome($value){
		$this->fields["font_transportadoranome"] = value_numeric($value);
	}

	function setlarg_transportadoranome($value){
		$this->fields["larg_transportadoranome"] = value_numeric($value);
	}

	function setaltu_transportadoranome($value){
		$this->fields["altu_transportadoranome"] = value_numeric($value);
	}

	function setshow_transpvolume($value){
		$this->fields["show_transpvolume"] = value_string($value,1);
	}

	function setorie_transpvolume($value){
		$this->fields["orie_transpvolume"] = value_numeric($value);
	}

	function setposx_transpvolume($value){
		$this->fields["posx_transpvolume"] = value_numeric($value);
	}

	function setposy_transpvolume($value){
		$this->fields["posy_transpvolume"] = value_numeric($value);
	}

	function setfont_transpvolume($value){
		$this->fields["font_transpvolume"] = value_numeric($value);
	}

	function setlarg_transpvolume($value){
		$this->fields["larg_transpvolume"] = value_numeric($value);
	}

	function setaltu_transpvolume($value){
		$this->fields["altu_transpvolume"] = value_numeric($value);
	}

	function setshow_transppeso($value){
		$this->fields["show_transppeso"] = value_string($value,1);
	}

	function setorie_transppeso($value){
		$this->fields["orie_transppeso"] = value_numeric($value);
	}

	function setposx_transppeso($value){
		$this->fields["posx_transppeso"] = value_numeric($value);
	}

	function setposy_transppeso($value){
		$this->fields["posy_transppeso"] = value_numeric($value);
	}

	function setfont_transppeso($value){
		$this->fields["font_transppeso"] = value_numeric($value);
	}

	function setlarg_transppeso($value){
		$this->fields["larg_transppeso"] = value_numeric($value);
	}

	function setaltu_transppeso($value){
		$this->fields["altu_transppeso"] = value_numeric($value);
	}

	function setshow_texto1($value){
		$this->fields["show_texto1"] = value_string($value, 1);
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
		$this->fields["text_texto1"] = value_string($value, 300);
	}

	function setshow_texto2($value){
		$this->fields["show_texto2"] = value_string($value, 1);
	}

	function setorie_texto2($value){
		$this->fields["orie_texto2"] = value_numeric($value);
	}

	function setposx_texto2($value){
		$this->fields["posx_texto2"] = value_numeric($value);
	}

	function setposy_texto2($value){
		$this->fields["posy_texto2"] = value_numeric($value);
	}

	function setfont_texto2($value){
		$this->fields["font_texto2"] = value_numeric($value);
	}

	function setlarg_texto2($value){
		$this->fields["larg_texto2"] = value_numeric($value);
	}

	function setaltu_texto2($value){
		$this->fields["altu_texto2"] = value_numeric($value);
	}

	function settext_texto2($value){
		$this->fields["text_texto2"] = value_string($value, 300);
	}

	function setshow_texto3($value){
		$this->fields["show_texto3"] = value_string($value, 1);
	}

	function setorie_texto3($value){
		$this->fields["orie_texto3"] = value_numeric($value);
	}

	function setposx_texto3($value){
		$this->fields["posx_texto3"] = value_numeric($value);
	}

	function setposy_texto3($value){
		$this->fields["posy_texto3"] = value_numeric($value);
	}

	function setfont_texto3($value){
		$this->fields["font_texto3"] = value_numeric($value);
	}

	function setlarg_texto3($value){
		$this->fields["larg_texto3"] = value_numeric($value);
	}

	function setaltu_texto3($value){
		$this->fields["altu_texto3"] = value_numeric($value);
	}

	function settext_texto3($value){
		$this->fields["text_texto3"] = value_string($value, 300);
	}

	function setshow_texto4($value){
		$this->fields["show_texto4"] = value_string($value, 1);
	}

	function setorie_texto4($value){
		$this->fields["orie_texto4"] = value_numeric($value);
	}

	function setposx_texto4($value){
		$this->fields["posx_texto4"] = value_numeric($value);
	}

	function setposy_texto4($value){
		$this->fields["posy_texto4"] = value_numeric($value);
	}

	function setfont_texto4($value){
		$this->fields["font_texto4"] = value_numeric($value);
	}

	function setlarg_texto4($value){
		$this->fields["larg_texto4"] = value_numeric($value);
	}

	function setaltu_texto4($value){
		$this->fields["altu_texto4"] = value_numeric($value);
	}

	function settext_texto4($value){
		$this->fields["text_texto4"] = value_string($value, 300);
	}

	function setshow_texto5($value){
		$this->fields["show_texto5"] = value_string($value, 1);
	}

	function setorie_texto5($value){
		$this->fields["orie_texto5"] = value_numeric($value);
	}

	function setposx_texto5($value){
		$this->fields["posx_texto5"] = value_numeric($value);
	}

	function setposy_texto5($value){
		$this->fields["posy_texto5"] = value_numeric($value);
	}

	function setfont_texto5($value){
		$this->fields["font_texto5"] = value_numeric($value);
	}

	function setlarg_texto5($value){
		$this->fields["larg_texto5"] = value_numeric($value);
	}

	function setaltu_texto5($value){
		$this->fields["altu_texto5"] = value_numeric($value);
	}

	function settext_texto5($value){
		$this->fields["text_texto5"] = value_string($value, 300);
	}
}