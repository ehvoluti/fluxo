<?php

require("../include/config.php");

if ($_POST) {
   alterar("clientes", "id={$_GET["id"]}",$_POST);
   header('Location: clientes.php');
}

if($_GET["id"]){
    $cliente = ver("clientes", "*", "id={$_GET["id"]}");
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Alterar Cliente</h1></legend>
                </div>
                <div class="control-group">
                    <label class="control-label" for="nome_razao">Nome / Razão Social</label>
                    <div class="controls">
                        <input type="text" id="nome_razao" name="nome_razao" value="<?php echo $cliente["nome_razao"]; ?>" class="input-xlarge">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="cpf_cnpj">CPF/CNPJ</label>
                    <div class="controls">
                        <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo $cliente["cpf_cnpj"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">E-mail</label>
                    <div class="controls">
                        <input type="text" id="email" name="email" value="<?php echo $cliente["email"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="senha">Senha</label>
                    <div class="controls">
                        <input type="password" id="senha" name="senha" value="" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="telefone">Telefone</label>
                    <div class="controls">
                        <input type="text" id="telefone" name="telefone" value="<?php echo $cliente["telefone"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="celular">Celular</label>
                    <div class="controls">
                        <input type="text" id="celular" name="celular" value="<?php echo $cliente["celular"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="endereco">Endereço</label>
                    <div class="controls">
                        <input type="text" id="endereco" name="endereco" value="<?php echo $cliente["endereco"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="bairro">Bairro</label>
                    <div class="controls">
                        <input type="text" id="bairro" name="bairro" value="<?php echo $cliente["bairro"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="cidade">Cidade</label>
                    <div class="controls">
                        <input type="text" id="cidade" name="cidade" value="<?php echo $cliente["cidade"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="estado">Estado</label>
                    <div class="controls">
                        <input type="text" id="estado" name="estado" value="<?php echo $cliente["estado"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="cep">CEP</label>
                    <div class="controls">
                        <input type="text" id="cep" name="cep" value="<?php echo $cliente["cep"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <!-- Button -->
                    <div class="controls">
                        <input type="submit" >
                    </div>
                </div>

            </fieldset>
        </form>
    </div>
</div>
<?php include("rodape.php"); ?>
