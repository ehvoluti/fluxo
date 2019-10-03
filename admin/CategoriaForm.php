<?php require("topo.php"); ?>
<html>
<div class="container">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class=""><h1>Categoria</h1></legend>
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
                        <input type="text" id="descricao" name="descricao" value="" class="form-control col-6 col-xl-8 col-sm-8">

                    </div>
                </div>
                <div class="control-group">
                    <!-- Button -->
                    <div class="controls">
                        <br><input type="submit" value="Gravar" class="btn btn-primary">
                    </div>
                </div>

            </fieldset>
        </form>

</div>
</html>