<?php

class ReportColumn{

	private $align; // Alinhamento da coluna
	private $average; // Media da coluna
	private $calcaverage; // Se e para calcular ou nao a media da coluna
	private $changevalue; // Troca o valor original da coluna (indice da propriedade) por um valor escolhido (valor do indice da propriedade)
	private $label; // Titulo do campo
	private $name; // Nome da coluna
	private $visible; // Se a coluna sera visivel ou nao
	private $total; // Total da soma da coluna
	private $totalize; // Se e para calcular ou nao o total da coluna
	private $type; // Tipo da coluna
	private $width; // Largura da coluna
	private $calcfooter; // Calcula margem rodapé

	function __construct($name){
		$this->setname($name);
		$this->setalign("left");
		$this->setaverage(0);
		$this->setcalcaverage(FALSE);
		$this->setchangevalue(array());
		$this->setlabel($name);
		$this->setvisible(TRUE);
		$this->settotal(0);
		$this->settotalize(FALSE);
		$this->settype("string");
		$this->setwidth("1%");
	}

	function getalign(){
		return $this->align;
	}

	function getaverage(){
		return $this->average;
	}

	function getcalcaverage(){
		return $this->calcaverage;
	}

	function getchangevalue(){
		return $this->changevalue;
	}

	function getlabel(){
		return $this->label;
	}

	function getname(){
		return $this->name;
	}

	function getvisible(){
		return $this->visible;
	}

	function gettotal(){
		return $this->total;
	}

	function gettotalize(){
		return $this->totalize;
	}

	function gettype(){
		return $this->type;
	}

	function getwidth(){
		return $this->width;
	}

	function getcolumncalcfooter(){
		return $this->calcfooter;
	}

	function setalign($value){
		$value = strtolower($value);
		$arr = array("left", "right", "center");
		$this->align = (in_array($value, $arr) ? $value : "left");
	}

	function setaverage($value){
		$this->average = (is_numeric($value) ? $value : 0);
	}

	function setcalcaverage($value){
		$this->calcaverage = (is_bool($value) ? $value : TRUE);
	}

	function setcalcfooter($calcfooter){
		$this->calcfooter = (is_string($calcfooter) ? $calcfooter : NULL);
	}

	function setchangevalue($value){
		$this->changevalue = (is_array($value) ? $value : array());
	}

	function setlabel($value){
		$this->label = (is_string($value) ? $value : $this->name);
	}

	private function setname($value){
		if(is_string($value)){
			$this->name = $value;
		}else{
			die("Nome informado para coluna &eacute; inv&aacute;lido");
		}
	}

	function setvisible($value){
		$this->visible = (is_bool($value) ? $value : TRUE);
	}

	function settotal($value){
		$this->total = (is_numeric($value) ? $value : 0);
	}

	function settotalize($value){
		$this->totalize = (is_bool($value) ? $value : TRUE);
	}

	function settype($value){
		$value = strtolower($value);
		$arr = array("string", "integer", "numeric", "numeric3", "numeric4", "date", "time");
		$this->type = (in_array($value, $arr) ? $value : "string");
	}

	function setwidth($value){
		$this->width = (is_string($value) ? $value : "1%");
	}

}