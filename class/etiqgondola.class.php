<?php

require_file("class/cadastro.class.php");

class EtiqGondola extends Cadastro{

	function __construct($codetiqgondola = NULL){
		parent::__construct();
		$this->table = "etiqgondola";
		$this->primarykey = array("codetiqgondola");
		$this->setcodetiqgondola($codetiqgondola);
		if(!is_null($this->getcodetiqgondola())){
			$this->searchbyobject();
		}
	}

	function getcodetiqgondola(){
		return $this->fields["codetiqgondola"];
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

	function getshow_estabelecimento(){
		return $this->fields["show_estabelecimento"];
	}

	function getorie_estabelecimento(){
		return $this->fields["orie_estabelecimento"];
	}

	function getposx_estabelecimento(){
		return $this->fields["posx_estabelecimento"];
	}

	function getposy_estabelecimento(){
		return $this->fields["posy_estabelecimento"];
	}

	function getfont_estabelecimento(){
		return $this->fields["font_estabelecimento"];
	}

	function getlarg_estabelecimento(){
		return $this->fields["larg_estabelecimento"];
	}

	function getaltu_estabelecimento(){
		return $this->fields["altu_estabelecimento"];
	}

	function getshow_descricao(){
		return $this->fields["show_descricao"];
	}

	function getorie_descricao(){
		return $this->fields["orie_descricao"];
	}

	function getposx_descricao(){
		return $this->fields["posx_descricao"];
	}

	function getposy_descricao(){
		return $this->fields["posy_descricao"];
	}

	function getfont_descricao(){
		return $this->fields["font_descricao"];
	}

	function getlarg_descricao(){
		return $this->fields["larg_descricao"];
	}

	function getaltu_descricao(){
		return $this->fields["altu_descricao"];
	}

	function getshow_preco(){
		return $this->fields["show_preco"];
	}

	function getorie_preco(){
		return $this->fields["orie_preco"];
	}

	function getposx_preco(){
		return $this->fields["posx_preco"];
	}

	function getposy_preco(){
		return $this->fields["posy_preco"];
	}

	function getfont_preco(){
		return $this->fields["font_preco"];
	}

	function getlarg_preco(){
		return $this->fields["larg_preco"];
	}

	function getaltu_preco(){
		return $this->fields["altu_preco"];
	}

	function getshow_dtpreco(){
		return $this->fields["show_dtpreco"];
	}

	function getorie_dtpreco(){
		return $this->fields["orie_dtpreco"];
	}

	function getposx_dtpreco(){
		return $this->fields["posx_dtpreco"];
	}

	function getposy_dtpreco(){
		return $this->fields["posy_dtpreco"];
	}

	function getfont_dtpreco(){
		return $this->fields["font_dtpreco"];
	}

	function getlarg_dtpreco(){
		return $this->fields["larg_dtpreco"];
	}

	function getaltu_dtpreco(){
		return $this->fields["altu_dtpreco"];
	}

	function getshow_moeda(){
		return $this->fields["show_moeda"];
	}

	function getorie_moeda(){
		return $this->fields["orie_moeda"];
	}

	function getposx_moeda(){
		return $this->fields["posx_moeda"];
	}

	function getposy_moeda(){
		return $this->fields["posy_moeda"];
	}

	function getfont_moeda(){
		return $this->fields["font_moeda"];
	}

	function getlarg_moeda(){
		return $this->fields["larg_moeda"];
	}

	function getaltu_moeda(){
		return $this->fields["altu_moeda"];
	}

	function getshow_codean(){
		return $this->fields["show_codean"];
	}

	function getorie_codean(){
		return $this->fields["orie_codean"];
	}

	function getposx_codean(){
		return $this->fields["posx_codean"];
	}

	function getposy_codean(){
		return $this->fields["posy_codean"];
	}

	function getfont_codean(){
		return $this->fields["font_codean"];
	}

	function getlarg_codean(){
		return $this->fields["larg_codean"];
	}

	function getaltu_codean(){
		return $this->fields["altu_codean"];
	}

	function getshow_codeantexto(){
		return $this->fields["show_codeantexto"];
	}

	function getorie_codeantexto(){
		return $this->fields["orie_codeantexto"];
	}

	function getposx_codeantexto(){
		return $this->fields["posx_codeantexto"];
	}

	function getposy_codeantexto(){
		return $this->fields["posy_codeantexto"];
	}

	function getfont_codeantexto(){
		return $this->fields["font_codeantexto"];
	}

	function getlarg_codeantexto(){
		return $this->fields["larg_codeantexto"];
	}

	function getaltu_codeantexto(){
		return $this->fields["altu_codeantexto"];
	}

	function getshow_mensagemvenda(){
		return $this->fields["show_mensagemvenda"];
	}

	function getorie_mensagemvenda(){
		return $this->fields["orie_mensagemvenda"];
	}

	function getposx_mensagemvenda(){
		return $this->fields["posx_mensagemvenda"];
	}

	function getposy_mensagemvenda(){
		return $this->fields["posy_mensagemvenda"];
	}

	function getfont_mensagemvenda(){
		return $this->fields["font_mensagemvenda"];
	}

	function getlarg_mensagemvenda(){
		return $this->fields["larg_mensagemvenda"];
	}

	function getaltu_mensagemvenda(){
		return $this->fields["altu_mensagemvenda"];
	}

	function getshow_codproduto(){
		return $this->fields["show_codproduto"];
	}

	function getorie_codproduto(){
		return $this->fields["orie_codproduto"];
	}

	function getposx_codproduto(){
		return $this->fields["posx_codproduto"];
	}

	function getposy_codproduto(){
		return $this->fields["posy_codproduto"];
	}

	function getfont_codproduto(){
		return $this->fields["font_codproduto"];
	}

	function getlarg_codproduto(){
		return $this->fields["larg_codproduto"];
	}

	function getaltu_codproduto(){
		return $this->fields["altu_codproduto"];
	}

	function getshow_unidade(){
		return $this->fields["show_unidade"];
	}

	function getorie_unidade(){
		return $this->fields["orie_unidade"];
	}

	function getposx_unidade(){
		return $this->fields["posx_unidade"];
	}

	function getposy_unidade(){
		return $this->fields["posy_unidade"];
	}

	function getfont_unidade(){
		return $this->fields["font_unidade"];
	}

	function getlarg_unidade(){
		return $this->fields["larg_unidade"];
	}

	function getaltu_unidade(){
		return $this->fields["altu_unidade"];
	}

	function getnumcarreiras(){
		return $this->fields["numcarreiras"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getshow_qtdeunidade(){
		return $this->fields["show_qtdeunidade"];
	}

	function getorie_qtdeunidade(){
		return $this->fields["orie_qtdeunidade"];
	}

	function getposx_qtdeunidade(){
		return $this->fields["posx_qtdeunidade"];
	}

	function getposy_qtdeunidade(){
		return $this->fields["posy_qtdeunidade"];
	}

	function getfont_qtdeunidade(){
		return $this->fields["font_qtdeunidade"];
	}

	function getlarg_qtdeunidade(){
		return $this->fields["larg_qtdeunidade"];
	}

	function getaltu_qtdeunidade(){
		return $this->fields["altu_qtdeunidade"];
	}

	function getprecooferta(){
		return $this->fields["precooferta"];
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

	function getshow_precoof(){
		return $this->fields["show_precoof"];
	}

	function getorie_precoof(){
		return $this->fields["orie_precoof"];
	}

	function getposx_precoof(){
		return $this->fields["posx_precoof"];
	}

	function getposy_precoof(){
		return $this->fields["posy_precoof"];
	}

	function getfont_precoof(){
		return $this->fields["font_precoof"];
	}

	function getlarg_precoof(){
		return $this->fields["larg_precoof"];
	}

	function getaltu_precoof(){
		return $this->fields["altu_precoof"];
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

	function getrecuo(){
		return $this->fields["recuo"];
	}

	function gettiposervidor(){
		return $this->fields["tiposervidor"];
	}

	function getshow_infonutricional(){
		return $this->fields["show_infonutricional"];
	}

	function getorie_infonutricional(){
		return $this->fields["orie_infonutricional"];
	}

	function getposx_infonutricional(){
		return $this->fields["posx_infonutricional"];
	}

	function getposy_infonutricional(){
		return $this->fields["posy_infonutricional"];
	}

	function getfont_infonutricional(){
		return $this->fields["font_infonutricional"];
	}

	function getlarg_infonutricional(){
		return $this->fields["larg_infonutricional"];
	}

	function getaltu_infonutricional(){
		return $this->fields["altu_infonutricional"];
	}

	function getcampos_infonutricional(){
		return $this->fields["campos_infonutricional"];
	}

	function getcoluna1_tabelainfonutricional(){
		return $this->fields["coluna1_tabelainfonutricional"];
	}

	function getcoluna2_tabelainfonutricional(){
		return $this->fields["coluna2_tabelainfonutricional"];
	}

	function getcoluna3_tabelainfonutricional(){
		return $this->fields["coluna3_tabelainfonutricional"];
	}

	function getposy_tabelainfonutricional(){
		return $this->fields["posy_tabelainfonutricional"];
	}

	function getposx_tabelainfonutricional(){
		return $this->fields["posx_tabelainfonutricional"];
	}

	function getaltura_tabelainfonutricional(){
		return $this->fields["altura_tabelainfonutricional"];
	}

	function getlargura_tabelainfonutricional(){
		return $this->fields["largura_tabelainfonutricional"];
	}

	function getbox(){
		return $this->fields["box"];
	}

	function getshow_diasvalidade(){
		return $this->fields["show_diasvalidade"];
	}

	function getorie_diasvalidade(){
		return $this->fields["orie_diasvalidade"];
	}

	function getposx_diasvalidade(){
		return $this->fields["posx_diasvalidade"];
	}

	function getposy_diasvalidade(){
		return $this->fields["posy_diasvalidade"];
	}

	function getfont_diasvalidade(){
		return $this->fields["font_diasvalidade"];
	}

	function getlarg_diasvalidade(){
		return $this->fields["larg_diasvalidade"];
	}

	function getaltu_diasvalidade(){
		return $this->fields["altu_diasvalidade"];
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

	function getshow_codfornec(){
		return $this->fields["show_codfornec"];
	}

	function getorie_codfornec(){
		return $this->fields["orie_codfornec"];
	}

	function getposx_codfornec(){
		return $this->fields["posx_codfornec"];
	}

	function getposy_codfornec(){
		return $this->fields["posy_codfornec"];
	}

	function getfont_codfornec(){
		return $this->fields["font_codfornec"];
	}

	function getlarg_codfornec(){
		return $this->fields["larg_codfornec"];
	}

	function getaltu_codfornec(){
		return $this->fields["altu_codfornec"];
	}

	function gettext_codfornec(){
		return $this->fields["text_codfornec"];
	}

	function getshow_qtdatacado(){
		return $this->fields["show_qtdatacado"];
	}

	function getorie_qtdatacado(){
		return $this->fields["orie_qtdatacado"];
	}

	function getposx_qtdatacado(){
		return $this->fields["posx_qtdatacado"];
	}

	function getposy_qtdatacado(){
		return $this->fields["posy_qtdatacado"];
	}

	function getfont_qtdatacado(){
		return $this->fields["font_qtdatacado"];
	}

	function getlarg_qtdatacado(){
		return $this->fields["larg_qtdatacado"];
	}

	function getaltu_qtdatacado(){
		return $this->fields["altu_qtdatacado"];
	}

	function getshow_precoatc(){
		return $this->fields["show_precoatc"];
	}

	function getorie_precoatc(){
		return $this->fields["orie_precoatc"];
	}

	function getposx_precoatc(){
		return $this->fields["posx_precoatc"];
	}

	function getposy_precoatc(){
		return $this->fields["posy_precoatc"];
	}

	function getfont_precoatc(){
		return $this->fields["font_precoatc"];
	}

	function getlarg_precoatc(){
		return $this->fields["larg_precoatc"];
	}

	function getaltu_precoatc(){
		return $this->fields["altu_precoatc"];
	}

	function getqtdeetiqnf(){
		return $this->fields["qtdeetiqnf"];
	}

	function getshow_infofornec(){
		return $this->fields["show_infofornec"];
	}

	function getorie_infofornec(){
		return $this->fields["orie_infofornec"];
	}

	function getposx_infofornec(){
		return $this->fields["posx_infofornec"];
	}

	function getposy_infofornec(){
		return $this->fields["posy_infofornec"];
	}

	function getfont_infofornec(){
		return $this->fields["font_infofornec"];
	}

	function getlarg_infofornec(){
		return $this->fields["larg_infofornec"];
	}

	function getaltu_infofornec(){
		return $this->fields["altu_infofornec"];
	}

	function gettext_infofornec(){
		return $this->fields["text_infofornec"];
	}

	function getshow_logo(){
		return $this->fields["show_logo"];
	}

	function getposx_logo(){
		return $this->fields["posx_logo"];
	}

	function getposy_logo(){
		return $this->fields["posy_logo"];
	}

	function getlarg_logo(){
		return $this->fields["larg_logo"];
	}

	function getaltu_logo(){
		return $this->fields["altu_logo"];
	}

	function getinvertercor(){
		return $this->fields["invertercor"];
	}

	function gettipo_infonutricional(){
		return $this->fields["tipo_infonutricional"];
	}

	function setcodetiqgondola($value){
		$this->fields["codetiqgondola"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 40);
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
		$this->fields["tipodescricao"] = value_string($value, 1);
	}

	function setshow_estabelecimento($value){
		$this->fields["show_estabelecimento"] = value_string($value, 1);
	}

	function setorie_estabelecimento($value){
		$this->fields["orie_estabelecimento"] = value_numeric($value);
	}

	function setposx_estabelecimento($value){
		$this->fields["posx_estabelecimento"] = value_numeric($value);
	}

	function setposy_estabelecimento($value){
		$this->fields["posy_estabelecimento"] = value_numeric($value);
	}

	function setfont_estabelecimento($value){
		$this->fields["font_estabelecimento"] = value_numeric($value);
	}

	function setlarg_estabelecimento($value){
		$this->fields["larg_estabelecimento"] = value_numeric($value);
	}

	function setaltu_estabelecimento($value){
		$this->fields["altu_estabelecimento"] = value_numeric($value);
	}

	function setshow_descricao($value){
		$this->fields["show_descricao"] = value_string($value, 1);
	}

	function setorie_descricao($value){
		$this->fields["orie_descricao"] = value_numeric($value);
	}

	function setposx_descricao($value){
		$this->fields["posx_descricao"] = value_numeric($value);
	}

	function setposy_descricao($value){
		$this->fields["posy_descricao"] = value_numeric($value);
	}

	function setfont_descricao($value){
		$this->fields["font_descricao"] = value_numeric($value);
	}

	function setlarg_descricao($value){
		$this->fields["larg_descricao"] = value_numeric($value);
	}

	function setaltu_descricao($value){
		$this->fields["altu_descricao"] = value_numeric($value);
	}

	function setshow_preco($value){
		$this->fields["show_preco"] = value_string($value, 1);
	}

	function setorie_preco($value){
		$this->fields["orie_preco"] = value_numeric($value);
	}

	function setposx_preco($value){
		$this->fields["posx_preco"] = value_numeric($value);
	}

	function setposy_preco($value){
		$this->fields["posy_preco"] = value_numeric($value);
	}

	function setfont_preco($value){
		$this->fields["font_preco"] = value_numeric($value);
	}

	function setlarg_preco($value){
		$this->fields["larg_preco"] = value_numeric($value);
	}

	function setaltu_preco($value){
		$this->fields["altu_preco"] = value_numeric($value);
	}

	function setshow_dtpreco($value){
		$this->fields["show_dtpreco"] = value_string($value, 1);
	}

	function setorie_dtpreco($value){
		$this->fields["orie_dtpreco"] = value_numeric($value);
	}

	function setposx_dtpreco($value){
		$this->fields["posx_dtpreco"] = value_numeric($value);
	}

	function setposy_dtpreco($value){
		$this->fields["posy_dtpreco"] = value_numeric($value);
	}

	function setfont_dtpreco($value){
		$this->fields["font_dtpreco"] = value_numeric($value);
	}

	function setlarg_dtpreco($value){
		$this->fields["larg_dtpreco"] = value_numeric($value);
	}

	function setaltu_dtpreco($value){
		$this->fields["altu_dtpreco"] = value_numeric($value);
	}

	function setshow_moeda($value){
		$this->fields["show_moeda"] = value_string($value, 1);
	}

	function setorie_moeda($value){
		$this->fields["orie_moeda"] = value_numeric($value);
	}

	function setposx_moeda($value){
		$this->fields["posx_moeda"] = value_numeric($value);
	}

	function setposy_moeda($value){
		$this->fields["posy_moeda"] = value_numeric($value);
	}

	function setfont_moeda($value){
		$this->fields["font_moeda"] = value_numeric($value);
	}

	function setlarg_moeda($value){
		$this->fields["larg_moeda"] = value_numeric($value);
	}

	function setaltu_moeda($value){
		$this->fields["altu_moeda"] = value_numeric($value);
	}

	function setshow_codean($value){
		$this->fields["show_codean"] = value_string($value, 1);
	}

	function setorie_codean($value){
		$this->fields["orie_codean"] = value_numeric($value);
	}

	function setposx_codean($value){
		$this->fields["posx_codean"] = value_numeric($value);
	}

	function setposy_codean($value){
		$this->fields["posy_codean"] = value_numeric($value);
	}

	function setfont_codean($value){
		$this->fields["font_codean"] = value_numeric($value);
	}

	function setlarg_codean($value){
		$this->fields["larg_codean"] = value_numeric($value);
	}

	function setaltu_codean($value){
		$this->fields["altu_codean"] = value_numeric($value);
	}

	function setshow_codeantexto($value){
		$this->fields["show_codeantexto"] = value_string($value, 1);
	}

	function setorie_codeantexto($value){
		$this->fields["orie_codeantexto"] = value_numeric($value);
	}

	function setposx_codeantexto($value){
		$this->fields["posx_codeantexto"] = value_numeric($value);
	}

	function setposy_codeantexto($value){
		$this->fields["posy_codeantexto"] = value_numeric($value);
	}

	function setfont_codeantexto($value){
		$this->fields["font_codeantexto"] = value_numeric($value);
	}

	function setlarg_codeantexto($value){
		$this->fields["larg_codeantexto"] = value_numeric($value);
	}

	function setaltu_codeantexto($value){
		$this->fields["altu_codeantexto"] = value_numeric($value);
	}

	function setshow_mensagemvenda($value){
		$this->fields["show_mensagemvenda"] = value_string($value, 1);
	}

	function setorie_mensagemvenda($value){
		$this->fields["orie_mensagemvenda"] = value_numeric($value);
	}

	function setposx_mensagemvenda($value){
		$this->fields["posx_mensagemvenda"] = value_numeric($value);
	}

	function setposy_mensagemvenda($value){
		$this->fields["posy_mensagemvenda"] = value_numeric($value);
	}

	function setfont_mensagemvenda($value){
		$this->fields["font_mensagemvenda"] = value_numeric($value);
	}

	function setlarg_mensagemvenda($value){
		$this->fields["larg_mensagemvenda"] = value_numeric($value);
	}

	function setaltu_mensagemvenda($value){
		$this->fields["altu_mensagemvenda"] = value_numeric($value);
	}

	function setshow_codproduto($value){
		$this->fields["show_codproduto"] = value_string($value, 1);
	}

	function setorie_codproduto($value){
		$this->fields["orie_codproduto"] = value_numeric($value);
	}

	function setposx_codproduto($value){
		$this->fields["posx_codproduto"] = value_numeric($value);
	}

	function setposy_codproduto($value){
		$this->fields["posy_codproduto"] = value_numeric($value);
	}

	function setfont_codproduto($value){
		$this->fields["font_codproduto"] = value_numeric($value);
	}

	function setlarg_codproduto($value){
		$this->fields["larg_codproduto"] = value_numeric($value);
	}

	function setaltu_codproduto($value){
		$this->fields["altu_codproduto"] = value_numeric($value);
	}

	function setshow_unidade($value){
		$this->fields["show_unidade"] = value_string($value, 1);
	}

	function setorie_unidade($value){
		$this->fields["orie_unidade"] = value_numeric($value);
	}

	function setposx_unidade($value){
		$this->fields["posx_unidade"] = value_numeric($value);
	}

	function setposy_unidade($value){
		$this->fields["posy_unidade"] = value_numeric($value);
	}

	function setfont_unidade($value){
		$this->fields["font_unidade"] = value_numeric($value);
	}

	function setlarg_unidade($value){
		$this->fields["larg_unidade"] = value_numeric($value);
	}

	function setaltu_unidade($value){
		$this->fields["altu_unidade"] = value_numeric($value);
	}

	function setnumcarreiras($value){
		$this->fields["numcarreiras"] = value_numeric($value);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value, 1);
	}

	function setshow_qtdeunidade($value){
		$this->fields["show_qtdeunidade"] = value_string($value, 1);
	}

	function setorie_qtdeunidade($value){
		$this->fields["orie_qtdeunidade"] = value_numeric($value);
	}

	function setposx_qtdeunidade($value){
		$this->fields["posx_qtdeunidade"] = value_numeric($value);
	}

	function setposy_qtdeunidade($value){
		$this->fields["posy_qtdeunidade"] = value_numeric($value);
	}

	function setfont_qtdeunidade($value){
		$this->fields["font_qtdeunidade"] = value_numeric($value);
	}

	function setlarg_qtdeunidade($value){
		$this->fields["larg_qtdeunidade"] = value_numeric($value);
	}

	function setaltu_qtdeunidade($value){
		$this->fields["altu_qtdeunidade"] = value_numeric($value);
	}

	function setprecooferta($value){
		$this->fields["precooferta"] = value_string($value, 1);
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
		$this->fields["localimpressora"] = value_string($value, 200);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setshow_precoof($value){
		$this->fields["show_precoof"] = value_string($value, 1);
	}

	function setorie_precoof($value){
		$this->fields["orie_precoof"] = value_numeric($value);
	}

	function setposx_precoof($value){
		$this->fields["posx_precoof"] = value_numeric($value);
	}

	function setposy_precoof($value){
		$this->fields["posy_precoof"] = value_numeric($value);
	}

	function setfont_precoof($value){
		$this->fields["font_precoof"] = value_numeric($value);
	}

	function setlarg_precoof($value){
		$this->fields["larg_precoof"] = value_numeric($value);
	}

	function setaltu_precoof($value){
		$this->fields["altu_precoof"] = value_numeric($value);
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

	function setshow_dtatual($value){
		$this->fields["show_dtatual"] = value_string($value, 1);
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

	function setshow_fornecedor($value){
		$this->fields["show_fornecedor"] = value_string($value, 1);
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

	function setshow_reffornec($value){
		$this->fields["show_reffornec"] = value_string($value, 1);
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

	function setrecuo($value){
		$this->fields["recuo"] = value_numeric($value);
	}

	function settiposervidor($value){
		$this->fields["tiposervidor"] = value_string($value, 1);
	}

	function setshow_infonutricional($value){
		$this->fields["show_infonutricional"] = value_string($value, 1);
	}

	function setorie_infonutricional($value){
		$this->fields["orie_infonutricional"] = value_numeric($value);
	}

	function setposx_infonutricional($value){
		$this->fields["posx_infonutricional"] = value_numeric($value);
	}

	function setposy_infonutricional($value){
		$this->fields["posy_infonutricional"] = value_numeric($value);
	}

	function setfont_infonutricional($value){
		$this->fields["font_infonutricional"] = value_numeric($value);
	}

	function setlarg_infonutricional($value){
		$this->fields["larg_infonutricional"] = value_numeric($value);
	}

	function setaltu_infonutricional($value){
		$this->fields["altu_infonutricional"] = value_numeric($value);
	}

	function setcampos_infonutricional($value){
		$this->fields["campos_infonutricional"] = value_string($value, 1000);
	}

	function setcoluna1_tabelainfonutricional($value){
		$this->fields["coluna1_tabelainfonutricional"] = value_numeric($value);
	}

	function setcoluna2_tabelainfonutricional($value){
		$this->fields["coluna2_tabelainfonutricional"] = value_numeric($value);
	}

	function setcoluna3_tabelainfonutricional($value){
		$this->fields["coluna3_tabelainfonutricional"] = value_numeric($value);
	}

	function setposy_tabelainfonutricional($value){
		$this->fields["posy_tabelainfonutricional"] = value_numeric($value);
	}

	function setposx_tabelainfonutricional($value){
		$this->fields["posx_tabelainfonutricional"] = value_numeric($value);
	}

	function setaltura_tabelainfonutricional($value){
		$this->fields["altura_tabelainfonutricional"] = value_numeric($value);
	}

	function setlargura_tabelainfonutricional($value){
		$this->fields["largura_tabelainfonutricional"] = value_numeric($value);
	}

	function setbox($value){
		$this->fields["box"] = value_string($value, 1);
	}

	function setshow_diasvalidade($value){
		$this->fields["show_diasvalidade"] = value_string($value, 1);
	}

	function setorie_diasvalidade($value){
		$this->fields["orie_diasvalidade"] = value_numeric($value);
	}

	function setposx_diasvalidade($value){
		$this->fields["posx_diasvalidade"] = value_numeric($value);
	}

	function setposy_diasvalidade($value){
		$this->fields["posy_diasvalidade"] = value_numeric($value);
	}

	function setfont_diasvalidade($value){
		$this->fields["font_diasvalidade"] = value_numeric($value);
	}

	function setlarg_diasvalidade($value){
		$this->fields["larg_diasvalidade"] = value_numeric($value);
	}

	function setaltu_diasvalidade($value){
		$this->fields["altu_diasvalidade"] = value_numeric($value);
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

	function setshow_codfornec($value){
		$this->fields["show_codfornec"] = value_string($value, 1);
	}

	function setorie_codfornec($value){
		$this->fields["orie_codfornec"] = value_numeric($value);
	}

	function setposx_codfornec($value){
		$this->fields["posx_codfornec"] = value_numeric($value);
	}

	function setposy_codfornec($value){
		$this->fields["posy_codfornec"] = value_numeric($value);
	}

	function setfont_codfornec($value){
		$this->fields["font_codfornec"] = value_numeric($value);
	}

	function setlarg_codfornec($value){
		$this->fields["larg_codfornec"] = value_numeric($value);
	}

	function setaltu_codfornec($value){
		$this->fields["altu_codfornec"] = value_numeric($value);
	}

	function settext_codfornec($value){
		$this->fields["text_codfornec"] = value_string($value, 300);
	}

	function setshow_qtdatacado($value){
		$this->fields["show_qtdatacado"] = value_string($value, 1);
	}

	function setorie_qtdatacado($value){
		$this->fields["orie_qtdatacado"] = value_numeric($value);
	}

	function setposx_qtdatacado($value){
		$this->fields["posx_qtdatacado"] = value_numeric($value);
	}

	function setposy_qtdatacado($value){
		$this->fields["posy_qtdatacado"] = value_numeric($value);
	}

	function setfont_qtdatacado($value){
		$this->fields["font_qtdatacado"] = value_numeric($value);
	}

	function setlarg_qtdatacado($value){
		$this->fields["larg_qtdatacado"] = value_numeric($value);
	}

	function setaltu_qtdatacado($value){
		$this->fields["altu_qtdatacado"] = value_numeric($value);
	}

	function setshow_precoatc($value){
		$this->fields["show_precoatc"] = value_string($value, 1);
	}

	function setorie_precoatc($value){
		$this->fields["orie_precoatc"] = value_numeric($value);
	}

	function setposx_precoatc($value){
		$this->fields["posx_precoatc"] = value_numeric($value);
	}

	function setposy_precoatc($value){
		$this->fields["posy_precoatc"] = value_numeric($value);
	}

	function setfont_precoatc($value){
		$this->fields["font_precoatc"] = value_numeric($value);
	}

	function setlarg_precoatc($value){
		$this->fields["larg_precoatc"] = value_numeric($value);
	}

	function setaltu_precoatc($value){
		$this->fields["altu_precoatc"] = value_numeric($value);
	}

	function setqtdeetiqnf($value){
		$this->fields["qtdeetiqnf"] = value_string($value, 1);
	}

	function setshow_infofornec($value){
		$this->fields["show_infofornec"] = value_string($value,1);
	}

	function setorie_infofornec($value){
		$this->fields["orie_infofornec"] = value_numeric($value);
	}

	function setposx_infofornec($value){
		$this->fields["posx_infofornec"] = value_numeric($value);
	}

	function setposy_infofornec($value){
		$this->fields["posy_infofornec"] = value_numeric($value);
	}

	function setfont_infofornec($value){
		$this->fields["font_infofornec"] = value_numeric($value);
	}

	function setlarg_infofornec($value){
		$this->fields["larg_infofornec"] = value_numeric($value);
	}

	function setaltu_infofornec($value){
		$this->fields["altu_infofornec"] = value_numeric($value);
	}

	function settext_infofornec($value){
		$this->fields["text_infofornec"] = value_string($value, 300);
	}

	function setshow_logo($value){
		$this->fields["show_logo"] = value_string($value,1);
	}

	function setposx_logo($value){
		$this->fields["posx_logo"] = value_numeric($value);
	}

	function setposy_logo($value){
		$this->fields["posy_logo"] = value_numeric($value);
	}

	function setlarg_logo($value){
		$this->fields["larg_logo"] = value_numeric($value);
	}

	function setaltu_logo($value){
		$this->fields["altu_logo"] = value_numeric($value);
	}

	function setinvertercor($value){
		$this->fields["invertercor"] = value_string($value,1);
	}

	function settipo_infonutricional($value){
		$this->fields["tipo_infonutricional"] = value_numeric($value);
	}
}
