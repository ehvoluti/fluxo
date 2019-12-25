<?php 
require("../include/config.php");
$catlancto = listar("catlancto", "*", null, null, " codcatlancto");
require("topo.php"); 
?>

<html>
<div class="container">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class="" ><h1>SubCategoria</h1></legend>
                </div>
                
               <div class="control-group">
                    <label class="control-label" for="codsubcatlancto">Codigo</label>
                    <div class="controls">
                        <input type="text" id="codsubcatlancto" name="codsubcatlancto"  class="form-control col-4 col-xl-2 col-sm-8" >
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="descricao">SubCategoria</label>
                    <div class="controls">
                        <input type="text" id="descricao" name="descricao" value="" class="form-control col-6 col-xl-8 col-sm-8">

                    </div>
                </div>

                <label>Categoria</label>
                <div>
                    <div class="form-group ">
                        <select class="form-control col-5 col-xl-4 col-sm-5 " name="codcatlancto" id="codcatlancto">
                            <?php  foreach ($catlancto as $xcatlancto): ?>
                                <option value="<?php echo $xcatlancto['codcatlancto'];?>"><?php echo $xcatlancto['descricao'];?></option> 
                            <?php endforeach; ?>
                        </select> 
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