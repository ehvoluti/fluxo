<?php
class LoteLancamento{
	private $con;
	
	function __construct($con = NULL){
		$this->con = (is_null($con) ? new Connection() : $con);
	}

	function novonumlote(){
		$res = $this->con->query("SELECT MAX(numlote) AS numlote FROM lancamento");
		$numlote = $res->fetchColumn();
		if(strlen($numlote) == 0){
			$numlote = 0;
		}
		return ++$numlote;
	}
	
	function getlancamentos($numlote){
		$arr_lancamento = array();
		if(is_numeric($numlote)){
			$lancamento = objectbytable("lacamento",NULL,$this->con);
			$lancamento->setnumlote($numlote);
			$search = $lancamento->searchbyobject();
			if($search !== FALSE){
				if(!is_array($search)){
					$search = array($search);
				}
				foreach($search as $key){
					$arr_lancamento[] = objectbytable("lacamento",$key,$this->con);
				}
			}
		}
		return $arr_lancamento;
	}
}
?>