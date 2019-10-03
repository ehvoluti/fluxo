 <?php

require("../include/config.php");

if ($_POST) {
    alterar("catlancto", "codcatlancto={$_GET["id"]}",$_POST);
    header('Location: categoria.php');
 }

if($_GET["id"]){
    $tipo = ver("catlancto", "*", "codcatlancto={$_GET["id"]}");
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Alterar Categoria</h1></legend>

                </div>
                <div class="control-group">
                    <label class="control-label" for="codcatlancto">Nome</label>
                    <div class="controls">
                        <input type="text" id="codcatlancto" name="codcatlancto" value="<?php echo $tipo["codcatlancto"]; ?>" class="input-xlarge">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="descricao">E-mail</label>
                    <div class="controls">
                        <input type="text" id="descricao" name="descricao" value="<?php echo $tipo["descricao"]; ?>" class="input-xlarge">
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
