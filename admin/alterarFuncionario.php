<?php

require("../include/config.php");

$perfis = listar("perfis", "*");
if ($_POST) {
    alterar("funcionarios", "id={$_GET["id"]}",$_POST);
    header('Location: funcionarios.php');
 }

if($_GET["id"]){
    $cliente = ver("funcionarios", "*", "id={$_GET["id"]}");
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Alterar Funcionário</h1></legend>
                </div>
                <div class="control-group">
                    <label class="control-label" for="nome">Nome</label>
                    <div class="controls">
                        <input type="text" id="nome" name="nome" value="<?php echo $cliente["nome"]; ?>" class="input-xlarge">
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
                        <input type="text" id="senha" name="senha" value="" class="input-xlarge">                           
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="prf_id">Perfil</label>
                    <div class="controls">
                        <select name="prf_id">
                            <?php
                            foreach ($perfis as $vals){
                                if ($cliente["prf_id"] == $vals["id"]){
                                    echo "<option selected='selected' value='{$vals["id"]}'>{$vals["nome"]}</option>";
                                } else {
                                    echo "<option value='{$vals["id"]}'>{$vals["nome"]}</option>";
                                }

                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <!-- Button -->
                    <div class="controls">
                        <input type="submit" value="Alterar" >
                    </div>
                </div>

            </fieldset>
        </form>
    </div>
</div>
<?php include("rodape.php"); ?>
