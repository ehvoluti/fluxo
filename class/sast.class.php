<?php

class Sast{

	private $host = "sast.controlware.com.br";
	private $user = "websac@controlware.com.br";
	private $password = "automacao";

	function gethost(){
		return $this->host;
	}

	function online(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "http://{$this->host}");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		//curl_setopt($curl, CURLOPT_TIMEOUT_MS, 3);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 3);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_exec($curl);
		$info = curl_getinfo($curl);

		return (in_array($info["http_code"], array(200, 302)));

		// Status:
		// 200 - Sucesso
		// 302 - Direcionamento
	}

	function service($name, $json, $return_original = false){
		unset($_SESSION["ERROR"]);

		$name = basename($name, ".php");
		$url = "http://{$this->host}/service/{$name}.php";

		if(is_array($json)){
			$json = json_encode($json);
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json", "Accept: application/json"));
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "{$this->user}:{$this->password}");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

		$result = curl_exec($curl);
		$info = curl_getinfo($curl);

		if($info["http_code"] != 200){
			$error = curl_error($curl);
			$_SESSION["ERROR"] = "Erro {$info["http_code"]} ao consultar o servi&ccedil;o.<br>{$error}";
			return false;
		}

		$result = json_decode($result, true);

		if($return_original){
			return $result;
		}else{
			if($result["status"] > 0){
				$_SESSION["ERROR"] = $result["message"];
				return false;
			}
			return $result;
		}
	}
}