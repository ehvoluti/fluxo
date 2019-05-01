<?php

require("../include/config.php");

$perfis = listar("perfis", "*");

if($_POST) {
    if (inserir("funcionarios", $_POST)){
        header('Location: funcionarios.php');
    }
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Novo Funcionário</h1></legend>
                </div>
               <div class="control-group">
                    <label class="control-label" for="nome">Nome</label>
                    <div class="controls">
                        <input type="text" id="nome" name="nome"  class="input-xlarge">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">E-mail</label>
                    <div class="controls">
                        <input type="text" id="email" name="email" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="senha">Senha</label>
                    <div class="controls">
                        <input type="text" id="senha" name="senha" class="input-xlarge">                           
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="prf_id">Perfil</label>
                    <div class="controls">
                        <select name="prf_id">
                            <?php
                            foreach ($perfis as $vals){
                                echo "<option value='{$vals["id"]}'>{$vals["nome"]}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <!-- Button -->
                    <div class="controls">
                        <input type="submit" value="Incluir" >
                    </div>
                </div>

            </fieldset>
        </form>
    </div>
</div>
<?php include("rodape.php"); ?>