<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/pdf.class.php");

class LivroFiscal{
	private $con;
	
	protected $estabelecimento; // Estabelecimentos emitentes das notas fiscais	
	protected $mes;
	protected $ano;
	protected $datainicial;
	protected $datafinal;
	protected $arr_notafiscal; 	
	
	function __construct($con){
		$this->con = $con;
	}
	
	public function setestabelecimento($value){
		$this->estabelecimento = $value;
	}
	
	public function setdatainicial($value){
		$this->datainicial = $value;
	}
	
	public function setdatafinal($value){
		$this->datafinal = $value;
	}
	
	public function setmes($value){
		$this->mes = $value;
	}
	
	public function setano($value){
		$this->ano = $value;
	}
	
	function registro_entrada(){
		
	}		
}

?>