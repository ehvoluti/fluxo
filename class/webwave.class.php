<?php
require_once("../def/require_php.php");

class WebWave{
	protected $con;


	protected $produto;
	protected $estabelecimento;
	protected $fornecedor;

	protected $arr_xml_cliente = array();
	protected $arr_xml_produto = array();
	protected $arr_xml_categoria = array();

	function __construct($con) {
		$this->con = $con;
	}

	public function setestabelecimento(){
		$this->estabelecimento = $estabelecimento;
	}

	public function setfornecedor(){
		$this->fornecedor = $fornecedor;
	}

	public function setdircontabil($dircontabil){
     	$this->dircontabil = $dircontabil;
	}

	public function gerar(){
		//**********************************************************************
		//						Informações do Cliente
		//**********************************************************************
		$query  = "SELECT cliente.cpfcnpj, cliente.tppessoa, cliente.nome, cliente.email, ";
		$query .= "cliente.dtnascto, cliente.sexo, cliente.cepres, cliente.enderres, ";
		$query .= "cliente.complementoent, cliente.ufent, cidade.nome ";
		$query .= "FROM cliente ";
		$query .= "INNER JOIN cidade ON (cliente.codcidadeent = cidade.codcidade) LIMIT 10";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($arr as $key => $row){
			$xml  = "<Client>\r\n";
			$xml .= "<ClientType>\"".$row["tppessoa"]."\"</ClientType>\r\n";
			$xml .= "<CpfCnpj>".$row["cpfcnpj"]."</CpfCnpj>\r\n";
			$xml .= "<FullName>".utf8_encode($row["nome"])."</FullName>\r\n";
			$xml .= "<Email>".$row["email"]."</Email>\r\n";
			$xml .= "<BirthDate>".$row["dtnascto"]."</BirthDate>\r\n";
			$xml .= "<Gender>".$row["sexo"]."</Gender>\r\n";
			$xml .= "<ZipCode>".$row["cepres"]."</ZipCode>\r\n";
			$xml .= "<Address>".utf8_encode($row["enderres"])."</Address>\r\n";
			$xml .= "<AddressNumber>".$row["numeroent"]."</AddressNumber>\r\n";
			$xml .= "<AddressNote>".$row["complementoent"]."</AddressNote>\r\n";
			$xml .= "<Neighborhood>".$row["bairroent"]."</Neighborhood>\r\n";
			$xml .= "<State>".$row["ufent"]."</State>\r\n";
			$xml .= "<City>".utf8_encode($row["nome"])."</City>\r\n";
			$xml .= "</Client>\r\n";
			$this->arr_xml_cliente[] = $xml;
		}

		//**********************************************************************
		//						Informações do Produto
		//**********************************************************************
		$query  = "SELECT produtoestab.codproduto, produtoean.codean, produto.coddepto, produto.descricao, ";
		$query .= "produtoestab.precovrj, embalagem.descricao AS volume, embalagem.quantidade,produtoestab.disponivel ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produtoean ON (produtoestab.codproduto = produtoean.codproduto) ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "WHERE produtoestab.codestabelec = 1 ORDER BY produtoestab.codproduto  LIMIT 10";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);


		foreach($arr as $key => $row){
			$xml  = "<Product>";
			$xml .=	"<ProductId>".$row["codproduto"]."</ProductId>";
			$xml .= "<Barcode>".$row["codean"]."</Barcode>";
			$xml .= "<CategoryId>".$row["coddepto"]."</CategoryId>";
			$xml .= "<Name>".str_replace("&","e",utf8_encode($row["descricao"]))."</Name>";
			$xml .= "<MarketValue>".$row["precovrj"]."</MarketValue>";
			$xml .= "<Volume>".utf8_encode($row["volume"])."</Volume>";
			$xml .= "<Weight>".$row["quantidade"]."</Weight>";
			$xml .= "<Active>".$row["disponivel"]."</Active>";
			$xml .= "</Product>";
			$this->arr_xml_produto[] = $xml;
		}

		//**********************************************************************
		//						Informações da Categoria do Produto
		//**********************************************************************
		$query  = "SELECT DISTINCT departamento.nome, departamento.coddepto ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN departamento ON (produto.coddepto = departamento.coddepto) ";
		$query .= "WHERE produtoestab.codestabelec = 1 ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);


		foreach($arr as $key => $row){
			$xml  = "<Category>";
			$xml .=	"<CategoryId>".$row["coddepto"]."</CategoryId>";
			$xml .= "<Name>".str_replace("&","e",utf8_encode($row["nome"]))."</Name>";
			$xml .= "</Category>";
			$this->arr_xml_categoria[] = $xml;
		}

		$this->gravarxml(array_merge($this->arr_xml_cliente,$this->arr_xml_produto,$this->arr_xml_categoria),"webwave");
	}

	private function gravarxml($arr_xml,$filename){
		$arquivo = fopen($this->dircontabil.$filename.".xml","w");
		fwrite($arquivo,"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
		fwrite($arquivo,"<WebWave>\n");
		foreach($arr_xml as $xml){
			fwrite($arquivo,$xml);
		}
		fwrite($arquivo,"\n</WebWave>");
		fclose($arquivo);
	}
}

?>
