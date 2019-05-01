<?php

require("../include/config.php");

if($_GET["id"]){
    $mensagem = ver("fale_conosco", "*", "id={$_GET["id"]}");
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='excluirMensagem.php' method="GET">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Ver Mensagem</h1></legend>
                </div>
                
                <input type="hidden" name="id" value="<?php echo $mensagem['id']; ?>" />
                
                <div class="control-group">
                    <label class="control-label" for="nome">Nome</label>
                    <div class="controls">
                        <input type="text" id="nome_razao" disabled="disabled" value="<?php echo $mensagem["nome"]; ?>" class="input-xlarge">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">E-mail</label>
                    <div class="controls">
                        <input type="text" id="cpf_cnpj" disabled="disabled" value="<?php echo $mensagem["email"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="assunto">Assunto</label>
                    <div class="controls">
                        <input type="text" id="email" disabled="disabled" value="<?php echo $mensagem["assunto"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="mensagem">Mensagem</label>
                    <div class="controls">
                        <textarea id="mensagem" disabled="disabled" rows="5"><?php echo $mensagem["mensagem"]; ?></textarea>
                    </div>
                </div>
                
                <div class="control-group">
                    <!-- Button -->
                    <div class="controls">
                        <input type="submit" value="Clique para excluir" >
                    </div>
                </div>

            </fieldset>
        </form>
    </div>
</div>
<?php include("rodape.php"); ?>
