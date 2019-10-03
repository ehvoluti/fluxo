<?php
final class SenhaAtivacao{
	private $con;
	private $codcliente;
	private $terminais;
	private $serial;
	private $senha;

	function __construct($con = NULL){
		if($con == NULL){
			$this->con = new Connection();
		}else{
			$this->con = $con;
		}
		$this->codcliente = param("SISTEMA","CODIGOCW",$this->con);
		$this->terminais = param("SISTEMA","TERMINAIS",$this->con);
		$this->gerarserial();
		$this->gerarsenha();
	}

	private function gerarserial(){
        // Serial no Windows
		$info  = getenv("OS");
		$info .= getenv("COMPUTERNAME");
		$info .= getenv("NUMBER_OF_PROCESSORS");
		$info .= getenv("PROCESSOR_ARCHITECTURE");
		$info .= getenv("PROCESSOR_LEVEL");
		$info .= getenv("PROCESSOR_REVISION");

		// Serial no Linux (maquina fisica)
		if(strlen($info) == 0){
			$info = exec("smartctl -i /dev/hda | grep 'Serial'");
			$info = str_replace("Serial number:","",$info);
		}

		// Serial no Linux (maquina virtual)
		if(strlen($info) == 0){
			$info = exec("smartctl -d ata -i /dev/sda | grep 'Serial'");
			$info = str_replace("Serial number:","",$info);
		}

		// Ultima alternativa de pegar serial no Linux
		if(strlen($info) == 0){
			$info  = exec("grep \"model name\" /proc/cpuinfo");
			$info .= exec("grep flags /proc/cpuinfo");
		}

		// Serial no Macbook
		if(strlen($info) == 0){
			$info = exec("system_profiler SPHardwareDataType | grep 'Serial Number (system)' | awk '{print $"."NF}'");
		}

		// Serial no windows
		if(strlen($info) < 5){
			$info = substr(exec("vol"),30).exec("whoami");
		}

		$arr = array();
		for($i = 0; $i < strlen($info); $i++){
			$arr[] = str_pad(strtoupper(dechex(ord(substr($info,$i,1)))),2,"0",STR_PAD_LEFT);
		}
		$i = 0;
		while(sizeof($arr) > 10){
			if($i % 2 == 0){
				array_pop($arr);
			}else{
				array_shift($arr);
			}
			$i++;
		}
		$this->serial = implode(".",$arr);
		return $this->serial;
	}

	private function gerarsenha(){
		$arr = explode(".",$this->serial);
		foreach($arr as $i => $v){
			if($i % 3 == 0){
				$v = ceil((hexdec($v) + $this->codcliente) / pi());
			}else{
				$v = floor((hexdec($v) - $this->codcliente) * pi());
			}
			$arr[$i] = str_pad(substr(strtoupper(dechex(abs($v))),-2),2,substr($i,0,1),STR_PAD_LEFT);
		}
		$arr[1] = substr(str_pad(strtoupper(dechex(abs(hexdec($arr[1]) - $this->terminais * $this->terminais))),2,"0",STR_PAD_LEFT),-2);
		$this->senha = implode(".",array_reverse($arr));
        if(in_array(param("SISTEMA","CODIGOCW",$this->con),array("1543"/*perin*/,"2252"/*garca*/))){
            $this->senha = "";
        }
		return $this->senha;
	}

	function getserial(){
		return $this->serial;
	}

	function getsenha(){
		return $this->senha;
	}
}
