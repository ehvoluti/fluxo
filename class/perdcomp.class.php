<?php

require_once "../def/require_php.php";

class PerDcomp
{

    protected $con;
    protected $codestabelec;
    protected $dtmovtoini;
    protected $dtmovtofim;

    private $estabelecimento;

    public function __construct($con)
    {
        $this->con = $con;
    }

    private function registros_periodo($entsai)
    {
        $where = array();
        $where[] = "operacaonota.tipo = '$entsai' ";

        $query_where = "";
        if (strlen($this->dtmovtoini) > 0) {
            $query_where  = "((notafiscal.operacao IN ('CP','DF','TE','EF','EC','DC') AND notafiscal.dtentrega >= '$this->dtmovtoini' AND notafiscal.dtentrega <= '$this->dtmovtofim' ) OR ";
            $query_where .= "(notafiscal.operacao NOT IN ('CP','DF','TE','EF','EC','DC') AND notafiscal.dtemissao >= '$this->dtmovtoini' AND notafiscal.dtemissao <= '$this->dtmovtofim' ))";
          
            $where[] = $query_where;
        }
        
        if (strlen($this->codestabelec) > 0) {$where[] = "notafiscal.codestabelec = {$this->codestabelec} ";}

        $query = "SELECT estabelecimento.cpfcnpj AS cnpj_estabelecimento, '' AS cnpj_parceiro, ";
        $query .= "lpad(EXTRACT(Month from (CASE WHEN notafiscal.operacao IN ('CP','DF','TE','EF','EC','DC') THEN notafiscal.dtentrega ELSE notafiscal.dtemissao END))::text,2,'0') AS mes, ";
        $query .= "EXTRACT(Year from (CASE WHEN notafiscal.operacao IN ('CP','DF','TE','EF','EC','DC') THEN notafiscal.dtentrega ELSE notafiscal.dtemissao END)) AS ano, substr(itnotafiscal.natoperacao,1,5) AS natoperacao, ";
        $query .= "SUM(itnotafiscal.totalliquido) AS totalliquido, ";
        $query .= "SUM(itnotafiscal.totalipi) AS totalipi, ";
        $query .= "SUM(CASE itnotafiscal.totalipi WHEN 0 THEN itnotafiscal.totalliquido ELSE 0 END) AS totalipiisento ";
        $query .= "FROM notafiscal ";
        $query .= "INNER JOIN itnotafiscal ON (notafiscal.idnotafiscal = itnotafiscal.idnotafiscal) ";
        $query .= "INNER JOIN estabelecimento ON (notafiscal.codestabelec = estabelecimento.codestabelec) ";
        $query .= "INNER JOIN v_parceiro ON (notafiscal.codparceiro = v_parceiro.codparceiro AND notafiscal.tipoparceiro = v_parceiro.tipoparceiro) ";
        $query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
        $query .= "WHERE " . implode($where, " AND ");
        $query .= "GROUP BY 1, 2, 3, 4, 5 ";
        $query .= "ORDER BY 4 DESC, 3, 1, 2 ";
        
        $res = $this->con->query($query);
        return $res->fetchAll();
    }

    private function registros_nota()
    {
        $where = array();       

        $query_where = "";
        if (strlen($this->dtmovtoini) > 0) {
            $query_where  = "((notafiscal.operacao IN ('CP','DF','TE','EF','EC','DC') AND notafiscal.dtentrega >= '$this->dtmovtoini' AND notafiscal.dtentrega <= '$this->dtmovtofim' ) OR ";
            $query_where .= "(notafiscal.operacao NOT IN ('CP','DF','TE','EF','EC','DC') AND notafiscal.dtemissao >= '$this->dtmovtoini' AND notafiscal.dtemissao <= '$this->dtmovtofim' ))";
          
            $where[] = $query_where;
        }

        if (strlen($this->codestabelec) > 0) {$where[] = "notafiscal.codestabelec = {$this->codestabelec} ";}

        $query  = "SELECT estabelecimento.cpfcnpj AS cnpj_estabelecimento, v_parceiro.cpfcnpj AS cnpj_parceiro, ";
        $query .= "notafiscal.dtemissao, notafiscal.dtentrega, substr(itnotafiscal.natoperacao,1,5) AS natoperacao, ";        
        $query .= "notafiscal.numnotafis, notafiscal.serie, ";
        $query .= "SUM(itnotafiscal.totalliquido) AS totalliquido, ";
        $query .= "SUM(itnotafiscal.totalipi) AS totalipi ";        
        $query .= "FROM notafiscal ";
        $query .= "INNER JOIN itnotafiscal ON (notafiscal.idnotafiscal = itnotafiscal.idnotafiscal) ";
        $query .= "INNER JOIN estabelecimento ON (notafiscal.codestabelec = estabelecimento.codestabelec) ";
        $query .= "INNER JOIN v_parceiro ON (notafiscal.codparceiro = v_parceiro.codparceiro AND notafiscal.tipoparceiro = v_parceiro.tipoparceiro) ";
        $query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
        $query .= "WHERE " . implode($where, " AND ");        
        $query .= "GROUP BY 1, 2, 3, 4, 5, 6 ,7 ";
        $query .= "ORDER BY 4 DESC, 3, 1, 2 ";
        
        $res = $this->con->query($query);
        return $res->fetchAll();
    }

    private function registro11()
    {
        $arr_linha = array();
        $arr_registros = $this->registros_periodo("E");

        foreach ($arr_registros as $registro) {
            $linha  = "R11"; // Tipo
            $linha .= $this->formatar_cnpj($registro["cnpj_estabelecimento"]); // CNPJ do Declarante
            $linha .= $this->formatar_cnpj(""); // CNPJ da Sucedida
            $linha .= $this->formatar_cnpj($registro["cnpj_estabelecimento"]); // CNPJ do Estabelecimento Detentor do Crédito
            $linha .= $registro["ano"]; // Ano do Período de Apuração
            $linha .= $registro["mes"]; // Mês do Período de Apuração
            $linha .= "0"; // Decêndio/Quinzena do Período de Apuração
            $linha .= substr(removeformat($registro["natoperacao"]), 0, 4); // CFOP
            $linha .= $this->formatar_decimal($registro["totalliquido"]); // Operações com Crédito do Imposto - Base de Cálculo
            $linha .= $this->formatar_decimal($registro["totalipi"]); // Operações com Crédito do Imposto - IPI Creditado
            $linha .= $this->formatar_decimal($registro["totalipiisento"]); // Operações sem Crédito do Imposto - Isentas ou Não Tributadas
            $linha .= $this->formatar_decimal(0); // Operações sem Crédito do Imposto - Outras
            

            $arr_linha[] = $linha;
        }

        return $arr_linha;
    }

    private function registro12()
    {
        $arr_linha = array();
        $arr_registros = $this->registros_periodo("S");

        foreach ($arr_registros as $registro) {
            $linha  = "R12"; // Tipo
            $linha .= $this->formatar_cnpj($registro["cnpj_estabelecimento"]); // CNPJ do Declarante
            $linha .= $this->formatar_cnpj(""); // CNPJ da Sucedida
            $linha .= $this->formatar_cnpj($registro["cnpj_estabelecimento"]); // CNPJ do Estabelecimento Detentor do Crédito
            $linha .= $registro["ano"]; // Ano do Período de Apuração
            $linha .= $registro["mes"]; // Mês do Período de Apuração
            $linha .= "0"; // Decêndio/Quinzena do Período de Apuração
            $linha .= substr(removeformat($registro["natoperacao"]), 0, 4); // CFOP
            $linha .= $this->formatar_decimal($registro["totalliquido"]); // Operações com Crédito do Imposto - Base de Cálculo
            $linha .= $this->formatar_decimal($registro["totalipi"]); // Operações com Crédito do Imposto - IPI Creditado
            $linha .= $this->formatar_decimal($registro["totalipiisento"]); // Operações sem Crédito do Imposto - Isentas ou Não Tributadas
            $linha .= $this->formatar_decimal(0); // Operações sem Crédito do Imposto - Outras
            

            $arr_linha[] = $linha;
        }

        return $arr_linha;
    }

    private function registro13()
    {
        $arr_linha = array();
        $arr_registros = $this->registros_nota();

        foreach ($arr_registros as $registro) {
            $linha  = "R13"; // Tipo
            $linha .= $this->formatar_cnpj($registro["cnpj_estabelecimento"]); // CNPJ do Declarante
            $linha .= $this->formatar_cnpj(""); // CNPJ da Sucedida
            $linha .= $this->formatar_cnpj($registro["cnpj_estabelecimento"]); // CNPJ do Estabelecimento Detentor do Crédito
            $linha .= $this->formatar_cnpj($registro["cnpj_parceiro"]); // CNPJ do Estabelecimento Detentor do Crédito
            $linha .= $this->formatar_numerico($registro["numnotafis"],9); // Numerico
            $linha .= str_pad($registro["serie"], 3, " ", STR_PAD_RIGHT); // Serie
            $linha .= $this->formatar_data($registro["dtemissao"]); // Data de emissão
            $linha .= $this->formatar_data($registro["dtentrega"]); // Data de entrada
            $linha .= substr(removeformat($registro["natoperacao"]), 0, 4); // CFOP
            $linha .= $this->formatar_decimal($registro["totalliquido"]); // Operações com Crédito do Imposto - Base de Cálculo
            $linha .= $this->formatar_decimal($registro["totalipi"]); // Operações com Crédito do Imposto - IPI Creditado
            $linha .= $this->formatar_decimal($registro["totalipi"]); // Operações sem Crédito do Imposto - Isentas ou Não Tributadas            
            

            $arr_linha[] = $linha;
        }

        return $arr_linha;
    }

    private function formatar_cnpj($cnpj){
        $cnpj = removeformat($cnpj);
        $cnpj = str_pad($cnpj,14," ",STR_PAD_LEFT);
        return $cnpj;
    }

    private function formatar_decimal($decimal){
        $decimal = number_format($decimal, 2, "", ""); 
        $decimal = str_pad($decimal, 14, "0", STR_PAD_LEFT);
        return $decimal;
    } 

    private function formatar_numerico($numero,$quantidade_complemento){        
        $numero = str_pad($numero, $quantidade_complemento, "0", STR_PAD_LEFT);
        return $numero;
    }

    private function formatar_data($data){        
        $data = convert_date($data,"Y-m-d","dmY");
        return $data;
    }
    
    public function gerar(){
        $arr_linha = array_merge($this->registro11(),$this->registro12(), $this->registro13());
        $texto = implode($arr_linha,"\\r\\n");
        
        return $texto;
    }

    public function setcodestabelec($codestabelec)
    {
        $this->codestabelec = $codestabelec;
    }

    public function setdtmovtoini($dtmovtoini)
    {
        $this->dtmovtoini = $dtmovtoini;
    }

    public function setdtmovtofim($dtmovtofim)
    {
        $this->dtmovtofim = $dtmovtofim;
    }
}
