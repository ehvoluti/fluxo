<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class adwHistorico extends Cadastro{
	function __construct(){
		parent::__construct();
		$this->table = "adw_historico";
		$this->primarykey = array();
	}

	function getstatuspalavra(){
		return $this->fields["statuspalavra"];
	}

	function getpalavra(){
		return $this->fields["palavra"];
	}

	function getcampanha(){
		return $this->fields["campanha"];
	}

	function getgrupoanuncio(){
		return $this->fields["grupoanuncio"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcpcmax($format = FALSE){
		return ($format ? number_format($this->fields["cpcmax"],2,",","") : $this->fields["cpcmax"]);
	}

	function getimpressoes($format = FALSE){
		return ($format ? number_format($this->fields["impressoes"],0,",","") : $this->fields["impressoes"]);
	}

	function getcliques($format = FALSE){
		return ($format ? number_format($this->fields["cliques"],0,",","") : $this->fields["cliques"]);
	}

	function getctr($format = FALSE){
		return ($format ? number_format($this->fields["ctr"],2,",","") : $this->fields["ctr"]);
	}

	function getcpcmedio($format = FALSE){
		return ($format ? number_format($this->fields["cpcmedio"],2,",","") : $this->fields["cpcmedio"]);
	}

	function getcusto($format = FALSE){
		return ($format ? number_format($this->fields["custo"],2,",","") : $this->fields["custo"]);
	}

	function getposicaomed(){
		return $this->fields["posicaomed"];
	}

	function getindicequalidade(){
		return $this->fields["indicequalidade"];
	}

	function getcpcprimeiro($format = FALSE){
		return ($format ? number_format($this->fields["cpcprimeiro"],2,",","") : $this->fields["cpcprimeiro"]);
	}

	function getcpcsuperior($format = FALSE){
		return ($format ? number_format($this->fields["cpcsuperior"],2,",","") : $this->fields["cpcsuperior"]);
	}

	function getdtimport($format = FALSE){
		return ($format ? convert_date($this->fields["dtimport"],"Y-m-d","d/m/Y") : $this->fields["dtimport"]);
	}

	function setstatuspalavra($value){
		$this->fields["statuspalavra"] = value_string($value);
	}

	function setpalavra($value){
		$this->fields["palavra"] = value_string($value);
	}

	function setcampanha($value){
		$this->fields["campanha"] = value_string($value);
	}

	function setgrupoanuncio($value){
		$this->fields["grupoanuncio"] = value_string($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value);
	}

	function setcpcmax($value){
		$this->fields["cpcmax"] = value_numeric($value);
	}

	function setimpressoes($value){
		$this->fields["impressoes"] = value_numeric($value);
	}

	function setcliques($value){
		$this->fields["cliques"] = value_numeric($value);
	}

	function setctr($value){
		$this->fields["ctr"] = value_numeric($value);
	}

	function setcpcmedio($value){
		$this->fields["cpcmedio"] = value_numeric($value);
	}

	function setcusto($value){
		$this->fields["custo"] = value_numeric($value);
	}

	function setposicaomed($value){
		$this->fields["posicaomed"] = value_numeric($value);
	}

	function setindicequalidade($value){
		$this->fields["indicequalidade"] = value_numeric($value);
	}

	function setcpcprimeiro($value){
		$this->fields["cpcprimeiro"] = value_numeric($value);
	}

	function setcpcsuperior($value){
		$this->fields["cpcsuperior"] = value_numeric($value);
	}

	function setdtimport($value){
		$this->fields["dtimport"] = value_date($value);
	}
}
?>