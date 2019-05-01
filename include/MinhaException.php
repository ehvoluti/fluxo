<?php 
 #
    # classe personalizada instanciada
    # sempre que um erro ocorrer no sistema
    class MinhaException extends Exception
    {
        # email de quem deve receber o aviso de erro
        var $mailAdmin = 'hugo@ehvoluti.com.br';

        function __construct($message = null, $code = 0)
        {
            parent::__construct($message, $code);
            $hoje = date('Y-m-d H:i:s');
            error_log( 
                "\n ======== $hoje =========" .
                "\n Erro no arquivo : " . $this->getFile().
                "\n Linha:      " . $this->getLine() .
                "\n Mensagem:   " . $this->getMessage() .
                "\n Codigo:     " . $this->getCode() .
                "\n Trace(str): " . $this->getTraceAsString() 
                , 3
                , 'log_de_erros.dat'
            );
        }

        public function getAdminMail(){
            return $this->mailAdmin;
        }        
    }
    # fim da classe
?>	