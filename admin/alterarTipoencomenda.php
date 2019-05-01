<?php

require("../include/config.php");

if ($_POST) {
    alterar("tipos_encomenda", "codcatlancto={$_GET["id"]}",$_POST);
    header('Location: categoria.php');
 }

if($_GET["id"]){
    $tipo = ver("categoria", "*", "id={$_GET["id"]}");
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Alterar Tipo de encomenda</h1></legend>
                </div>
                <div class="control-group">
                    <label class="control-label" for="nome">Nome</label>
                    <div class="controls">
                        <input type="text" id="nome" name="nome" value="<?php echo $tipo["nome"]; ?>" class="input-xlarge">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="valor_km">E-mail</label>
                    <div class="controls">
                        <input type="text" id="valor_km" name="valor_km" value="<?php echo $tipo["valor_km"]; ?>" class="input-xlarge">

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="prazo_maximo">Prazo Máximo</label>
                    <div class="controls">
                        <input type="text" id="prazo_maximo" name="prazo_maximo" value="<?php echo $tipo["prazo_maximo"]; ?>" class="input-xlarge">                           
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
