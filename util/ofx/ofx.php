<?php

class Ofx {

    private $ofxFile;

    public function __construct($ofxFile) {
        $this->ofxFile = $ofxFile;
    }

    /*
     * Converte o arquivo OFX para XML
     */

    public function getOfxAsXML() {
	
        $content = file_get_contents($this->ofxFile);
        $line = strpos($content, "<OFX>");
        $ofx = substr($content, $line - 1);

        $buffer = $ofx;
        $count = 0;

        while ($pos = strpos($buffer, '<')) {
            $count++;
            $pos2 = strpos($buffer, '>');
            $element = substr($buffer, $pos + 1, $pos2 - $pos - 1);
            if (substr($element, 0, 1) == '/')
                $sla[] = substr($element, 1);
            else
                $als[] = $element;
            $buffer = substr($buffer, $pos2 + 1);
        }
        $adif = array_diff($als, $sla);
        $adif = array_unique($adif);
        $ofxy = $ofx;

        foreach ($adif as $dif) {
            $dpos = 0;
            while ($dpos = strpos($ofxy, $dif, $dpos + 1)) {
                $npos = strpos($ofxy, '<', $dpos + 1);
                $ofxy = substr_replace($ofxy, "</$dif>\n<", $npos, 1);
                $dpos = $npos + strlen($element) + 3;
            }
        }
        $ofxy = str_replace('&', '&amp;', $ofxy);

        return $ofxy;
    }

    /*
     * Retorna o Saldo da conta na data de exportação do extrato
     */

    public function getBalance() {
        $xml = new SimpleXMLElement($this->getOfxAsXML());
        $balance = $xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->LEDGERBAL->BALAMT;
        $dateOfBalance = $xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->LEDGERBAL->DTASOF;
        $date = strtotime(substr($dateOfBalance, 0, 8));
        $dateToReturn = date('Y-m-d', $date);

        return Array('date' => $dateToReturn, 'balance' => $balance);
    }

    /*
     * Retora um array de objetos com as transações
     * 
     * DTPOSTED => Data da Transação
     * TRNAMT   => Valor da Transação
     * TRNTYPE  => Tipo da Transação (Débito ou Crédito)
     * MEMO     => Descrição da transação
     */

    public function getTransactions() {
        $xml = new SimpleXMLElement($this->getOfxAsXML());
        $transactions = $xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->BANKTRANLIST->STMTTRN;
        return $transactions;
    }

}