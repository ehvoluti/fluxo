<?php
require_once("websac/require_file.php");
require_file("def/function.php");
require_file("class/sast.class.php");

define("SENHAMENSAL_ONLINE_DESCONECTADO", 0);
define("SENHAMENSAL_ONLINE_VALIDO", 1);
define("SENHAMENSAL_ONLINE_INVALIDO", 2);

final class SenhaMensal{
	private $con;
	private $data;
	private $cliente;
	private $senha;

	// Passar a data no formato dd/mm/yyyy ( date("d/m/Y") )
	function __construct($con, $data, $cliente = NULL){
		$this->con = $con;
		$this->data = $data;
		if($cliente == NULL){
			$parametro = objectbytable("parametro",array("SISTEMA","CODIGOCW"),$this->con);
			$this->cliente = $parametro->getvalor();
		}else{
			$this->cliente = $cliente;
		}
	}

	private function crip($n){
		if($n == 0){
			return "00";
		}
		$arr = array("1","2","3","4","5","6","7","8","9","0","1","3","5","7","9","0");
		$x = 0;
		for($i = 1; $i <= $n; $i++){
			$x = ($x == 16 ? 0 : $x);
			$x++;
			$c1 = $arr[$x-1];
		}
		$c2 = (intval($n / 16) == 0 ? $arr[15] : $arr[intval($n/16)-1]);
		return ($c2.$c1);
	}

	function gerar($mes = 0){
		$data = getdate(strtotime(date("Ymd",mktime(0,0,0,substr($this->data,3,2) + $mes,1,substr($this->data,6,4)))));
		$mes = array("Janeiro","Fevereiro","Marco","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");
		$mes = $mes[$data["mon"] - 1];
		$cMes = "";
		for($i = 0; $i < strlen($mes); $i++){
			$cMes .= chr(ord($mes[$i]) + $this->cliente);
		}
		$z = "";
		for($i = 0; $i < 5 - strlen($data["mon"] + $this->cliente); $i++){
			$z = "0".$z;
		}
		$zMesCli = $z.($data["mon"] + $this->cliente);
		$cCod = $zMesCli.(strlen($data["mon"]) == 1 ? "0" : "").$data["mon"].$cMes;
		$byte = 100 + $data["mon"] + substr($data["year"],2,2);
		$crip = "";
		for($i=1; $i <= strlen($cCod); $i++){
			$crip .= chr(ord($cCod[$i-1]) + $byte + $i);
		}
		$tSenha = "";
		for($i = 1; $i <= strlen($crip); $i++){
			$tSenha .= $this->crip(ord($crip[$i - 1])).($i == strlen($crip) ? "" : ".");
		}
		return $tSenha;
	}

	// Variavel mes e usada pra calcular do mes atual somada com a variavel
	function validar_manual($senha, $mes = 0){
		return ($senha == $this->gerar($mes));
	}

	function validar_online(){
		return SENHAMENSAL_ONLINE_VALIDO;
		$sast = new Sast();

		if(!$sast->online()){
			return SENHAMENSAL_ONLINE_DESCONECTADO;
		}

		$idcliente = param("SISTEMA", "CODIGOCW", $this->con);

		$result = $sast->service("clienteemdebito", array("idcliente" => $idcliente), true);
		if($result === false){
			return SENHAMENSAL_ONLINE_DESCONECTADO;
		}

		$senhamensal = new SenhaMensal($this->con, date("d/m/Y"));
		$parametro = objectbytable("parametro", array("SISTEMA", "SENHAMENSAL"), $this->con);

		if($result["status"] != "0"){
			$parametro->setvalor("");
			$parametro->save();

			return SENHAMENSAL_ONLINE_INVALIDO;
		}

		$parametro->setvalor($senhamensal->gerar());
		$parametro->save();

		return SENHAMENSAL_ONLINE_VALIDO;
	}
}