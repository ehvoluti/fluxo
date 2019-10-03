<?php
require_once("../def/require_php.php");

class DadoFiscal{
	protected $con;
	
	protected $estabelecimento; // Estabelecimentos emitentes das notas fiscais	
	protected $mes;
	protected $ano;
	protected $datainicial;
	protected $datafinal;
	protected $arr_notafiscal; 	
			
	function __construct($con){
		$this->con = $con;
	}
			
	public function gerarnotafiscal(){
		$query  = "SELECT * FROM notafiscal ";
		$query .= "WHERE notafiscal.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
		$query .= "	AND ((notafiscal.operacao IN ('CP','DF','TE') AND notafiscal.dtentrega BETWEEN '".$this->datainicial."' AND '".$this->datafinal."') ";
		$query .= "	 OR  (notafiscal.operacao NOT IN ('CP','DF','TE') AND notafiscal.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."')) ";
		$query .= "	AND notafiscal.status != 'C' ";
		$query .= " AND notafiscal.gerafiscal = 'S' ";

		foreach($this->arr_filtro as $coluna => $valor){
			if($coluna == "dtentregaini"){
				$query .= "	AND notafiscal.dtentrega >= '".$valor."' ";
			}elseif($coluna == "dtentregafim"){
				$query .= "	AND notafiscal.dtentrega <= '".$valor."' ";
			}else{
				$query .= "	AND notafiscal.".$coluna." = '".$valor."' ";
			}
		}
		$query .= "ORDER BY idnotafiscal ";
		die($query);
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_notafiscal[$row["idnotafiscal"]] = $row;
			$arr_idnotafiscal[] = $row["idnotafiscal"];
		}
		if(sizeof($arr_idnotafiscal) > 0){
			$res = $this->con->query("SELECT * FROM itnotafiscal WHERE idnotafiscal IN (".implode(",",$arr_idnotafiscal).") AND composicao IN ('N','P') ORDER BY idnotafiscal, seqitem");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$row["preco"] = $row["precopolitica"];
				$this->arr_notafiscal[$row["idnotafiscal"]]["itnotafiscal"][] = $row;
			}
		}

		
		return $this->arr_notafiscal;
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
}
?>