<?php

require("../include/config.php");

if($_POST) {
    if (inserir("catlancto", $_POST)){
        header('Location: categoria.php');
    }
}

?>
<?php include('topo.php'); ?>
<div class="container">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Nova Categoria</h1></legend>
                </div>
                
               <div class="control-group">
                    <label class="control-label" for="codcatlancto">Codigo</label>
                    <div class="controls">
                        <input type="text" id="codcatlancto" name="codcatlancto"  class="form-control col-4 col-xl-2 col-sm-8" >
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="descricao">Descricao</label>
                    <div class="controls">
                        <input type="text" id="descricao" name="descricao" class="form-control col-6 col-xl-8 col-sm-8">

                    </div>
                </div>
				<!-- Aguardando implementação para Previsão
                <div class="control-group">
                    <label class="control-label" for="previsao">Previsao</label>
                    <div class="controls">
                        <input type="number" id="previsao" name="previsao" class="form-control col-4 col-xl-2 col-sm-8" mask="decimal2" style="text-align: right;">

                    </div>
                </div>
                -->
                <div class="control-group">
                    <!-- Button -->
                    <div class="controls">
                        <br><input type="submit" value="Incluir" class="btn btn-primary">
                    </div>
                </div>

            </fieldset>
        </form>

</div>
<?php include("rodape.php"); ?>