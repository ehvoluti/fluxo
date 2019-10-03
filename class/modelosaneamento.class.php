<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ModeloSaneamento extends Cadastro{
	function __construct($codmodelo = NULL){
		parent::__construct();
		$this->table = "modelosaneamento";
		$this->primarykey = array("codmodelo");
		$this->setcodmodelo($codmodelo);
		if(!is_null($this->getcodmodelo())){
			$this->searchbyobject();
		}
	}

	function getcodmodelo(){
		return $this->fields["codmodelo"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getnomearquivo(){
		return $this->fields["nomearquivo"];
	}

	function getlinhainicial(){
		return $this->fields["linhainicial"];
	}

	function getcol_codproduto(){
		return $this->fields["col_codproduto"];
	}

	function getcol_descricaofiscal(){
		return $this->fields["col_descricaofiscal"];
	}

	function getcol_codean(){
		return $this->fields["col_codean"];
	}

	function getcol_codigoncm(){
		return $this->fields["col_codigoncm"];
	}

	function getcol_csticmsnfe(){
		return $this->fields["col_csticmsnfe"];
	}

	function getcol_tptribicmsnfe(){
		return $this->fields["col_tptribicmsnfe"];
	}

	function getcol_aliqicmsnfe(){
		return $this->fields["col_aliqicmsnfe"];
	}

	function getcol_redicmsnfe(){
		return $this->fields["col_redicmsnfe"];
	}

	function getcol_aliqivanfe(){
		return $this->fields["col_aliqivanfe"];
	}

	function getcol_valorpautanfe(){
		return $this->fields["col_valorpautanfe"];
	}

	function getcol_csticmsnfs(){
		return $this->fields["col_csticmsnfs"];
	}

	function getcol_tptribicmsnfs(){
		return $this->fields["col_tptribicmsnfs"];
	}

	function getcol_aliqicmsnfs(){
		return $this->fields["col_aliqicmsnfs"];
	}

	function getcol_redicmsnfs(){
		return $this->fields["col_redicmsnfs"];
	}

	function getcol_aliqivanfs(){
		return $this->fields["col_aliqivanfs"];
	}

	function getcol_valorpautanfs(){
		return $this->fields["col_valorpautanfs"];
	}

	function getcol_csticmspdv(){
		return $this->fields["col_csticmspdv"];
	}

	function getcol_tptribicmspdv(){
		return $this->fields["col_tptribicmspdv"];
	}

	function getcol_aliqicmspdv(){
		return $this->fields["col_aliqicmspdv"];
	}

	function getcol_redicmspdv(){
		return $this->fields["col_redicmspdv"];
	}

	function getcol_aliqivapdv(){
		return $this->fields["col_aliqivapdv"];
	}

	function getcol_valorpautapdv(){
		return $this->fields["col_valorpautapdv"];
	}

	function getcol_cstpiscofinsent(){
		return $this->fields["col_cstpiscofinsent"];
	}

	function getcol_ccspiscofinsent(){
		return $this->fields["col_ccspiscofinsent"];
	}

	function getcol_tipopiscofinsent(){
		return $this->fields["col_tipopiscofinsent"];
	}

	function getcol_aliqpisent(){
		return $this->fields["col_aliqpisent"];
	}

	function getcol_aliqcofinsent(){
		return $this->fields["col_aliqcofinsent"];
	}

	function getcol_redpisent(){
		return $this->fields["col_redpisent"];
	}

	function getcol_redcofinsent(){
		return $this->fields["col_redcofinsent"];
	}

	function getcol_cstpiscofinssai(){
		return $this->fields["col_cstpiscofinssai"];
	}

	function getcol_ccspiscofinssai(){
		return $this->fields["col_ccspiscofinssai"];
	}

	function getcol_tipopiscofinssai(){
		return $this->fields["col_tipopiscofinssai"];
	}

	function getcol_aliqpissai(){
		return $this->fields["col_aliqpissai"];
	}

	function getcol_aliqcofinssai(){
		return $this->fields["col_aliqcofinssai"];
	}

	function getcol_redpissai(){
		return $this->fields["col_redpissai"];
	}

	function getcol_redcofinssai(){
		return $this->fields["col_redcofinssai"];
	}

	function getcol_cstipient(){
		return $this->fields["col_cstipient"];
	}
	
	function getcol_cstipisai(){
		return $this->fields["col_cstipisai"];
	}

	function getcol_tipoipi(){
		return $this->fields["col_tipoipi"];
	}

	function getcol_valipi(){
		return $this->fields["col_valipi"];
	}

	function getcol_natreceita(){
		return $this->fields["col_natreceita"];
	}

	function getcol_unidade(){
		return $this->fields["col_unidade"];
	}

	function setcodmodelo($value){
		$this->fields["codmodelo"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setnomearquivo($value){
		$this->fields["nomearquivo"] = value_string($value,40);
	}

	function setlinhainicial($value){
		$this->fields["linhainicial"] = value_numeric($value);
	}

	function setcol_codproduto($value){
		$this->fields["col_codproduto"] = value_numeric($value);
	}

	function setcol_descricaofiscal($value){
		$this->fields["col_descricaofiscal"] = value_numeric($value);
	}

	function setcol_codean($value){
		$this->fields["col_codean"] = value_numeric($value);
	}

	function setcol_codigoncm($value){
		$this->fields["col_codigoncm"] = value_numeric($value);
	}

	function setcol_csticmsnfe($value){
		$this->fields["col_csticmsnfe"] = value_numeric($value);
	}

	function setcol_tptribicmsnfe($value){
		$this->fields["col_tptribicmsnfe"] = value_numeric($value);
	}

	function setcol_aliqicmsnfe($value){
		$this->fields["col_aliqicmsnfe"] = value_numeric($value);
	}

	function setcol_redicmsnfe($value){
		$this->fields["col_redicmsnfe"] = value_numeric($value);
	}

	function setcol_aliqivanfe($value){
		$this->fields["col_aliqivanfe"] = value_numeric($value);
	}

	function setcol_valorpautanfe($value){
		$this->fields["col_valorpautanfe"] = value_numeric($value);
	}

	function setcol_csticmsnfs($value){
		$this->fields["col_csticmsnfs"] = value_numeric($value);
	}

	function setcol_tptribicmsnfs($value){
		$this->fields["col_tptribicmsnfs"] = value_numeric($value);
	}

	function setcol_aliqicmsnfs($value){
		$this->fields["col_aliqicmsnfs"] = value_numeric($value);
	}

	function setcol_redicmsnfs($value){
		$this->fields["col_redicmsnfs"] = value_numeric($value);
	}

	function setcol_aliqivanfs($value){
		$this->fields["col_aliqivanfs"] = value_numeric($value);
	}

	function setcol_valorpautanfs($value){
		$this->fields["col_valorpautanfs"] = value_numeric($value);
	}

	function setcol_csticmspdv($value){
		$this->fields["col_csticmspdv"] = value_numeric($value);
	}

	function setcol_tptribicmspdv($value){
		$this->fields["col_tptribicmspdv"] = value_numeric($value);
	}

	function setcol_aliqicmspdv($value){
		$this->fields["col_aliqicmspdv"] = value_numeric($value);
	}

	function setcol_redicmspdv($value){
		$this->fields["col_redicmspdv"] = value_numeric($value);
	}

	function setcol_aliqivapdv($value){
		$this->fields["col_aliqivapdv"] = value_numeric($value);
	}

	function setcol_valorpautapdv($value){
		$this->fields["col_valorpautapdv"] = value_numeric($value);
	}

	function setcol_cstpiscofinsent($value){
		$this->fields["col_cstpiscofinsent"] = value_numeric($value);
	}

	function setcol_ccspiscofinsent($value){
		$this->fields["col_ccspiscofinsent"] = value_numeric($value);
	}

	function setcol_tipopiscofinsent($value){
		$this->fields["col_tipopiscofinsent"] = value_numeric($value);
	}

	function setcol_aliqpisent($value){
		$this->fields["col_aliqpisent"] = value_numeric($value);
	}

	function setcol_aliqcofinsent($value){
		$this->fields["col_aliqcofinsent"] = value_numeric($value);
	}

	function setcol_redpisent($value){
		$this->fields["col_redpisent"] = value_numeric($value);
	}

	function setcol_redcofinsent($value){
		$this->fields["col_redcofinsent"] = value_numeric($value);
	}

	function setcol_cstpiscofinssai($value){
		$this->fields["col_cstpiscofinssai"] = value_numeric($value);
	}

	function setcol_ccspiscofinssai($value){
		$this->fields["col_ccspiscofinssai"] = value_numeric($value);
	}

	function setcol_tipopiscofinssai($value){
		$this->fields["col_tipopiscofinssai"] = value_numeric($value);
	}

	function setcol_aliqpissai($value){
		$this->fields["col_aliqpissai"] = value_numeric($value);
	}

	function setcol_aliqcofinssai($value){
		$this->fields["col_aliqcofinssai"] = value_numeric($value);
	}

	function setcol_redpissai($value){
		$this->fields["col_redpissai"] = value_numeric($value);
	}

	function setcol_redcofinssai($value){
		$this->fields["col_redcofinssai"] = value_numeric($value);
	}

	function setcol_cstipient($value){
		$this->fields["col_cstipient"] = value_numeric($value);
	}
	
	function setcol_cstipisai($value){
		$this->fields["col_cstipisai"] = value_numeric($value);
	}

	function setcol_tipoipi($value){
		$this->fields["col_tipoipi"] = value_numeric($value);
	}

	function setcol_valipi($value){
		$this->fields["col_valipi"] = value_numeric($value);
	}

	function setcol_natreceita($value){
		$this->fields["col_natreceita"] = value_numeric($value);
	}

	function setcol_unidade($value){
		$this->fields["col_unidade"] = value_numeric($value);
	}
}
?>