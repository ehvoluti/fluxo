<?php

require("../include/config.php");

if($_POST) {
    if (inserir("catlancto", $_POST)){
        header('Location: categoria.php');
    }
}

?>
<?php include('topo.php'); ?>
<div class="row">
    <div class="span12">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Nova Categoria</h1></legend>
                </div>
                
               <div class="control-group">
                    <label class="control-label" for="codcatlancto">Codigo</label>
                    <div class="controls">
                        <input type="text" id="codcatlancto" name="codcatlancto"  class="input-xlarge">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="descricao">Descricao</label>
                    <div class="controls">
                        <input type="text" id="descricao" name="descricao" class="input-xlarge">

                    </div>
                </div>
				
                <div class="control-group">
                    <label class="control-label" for="previsao">Previsao</label>
                    <div class="controls">
                        <input type="text" id="previsao" name="previsao" class="input-xlarge" mask="decimal2" style="text-align: right;">

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