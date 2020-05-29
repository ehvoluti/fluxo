<?php 

require("../include/config.php");
$banco = listar("banco", "*", null, null, " codbanco");
$catlancto = listar("catlancto", "*", null, null, " codcatlancto");
$subcatlancto = listar("subcatlancto", "*", null, null, " codsubcatlancto");

require("topo.php"); 
?>

<html>
<div class="container">
        <form class="form-horizontal" action='' method="POST">
            <fieldset>
                <div id="legend">
                    <legend class="" ><h1>Fornecedor</h1></legend>
                </div>
                
               <div class="control-group">
                    <label class="control-label">Codigo</label>
                    <div class="controls">
                        <input type="text" id="codfornec" name="codfornec"  class="form-control col-4 col-xl-2 col-sm-8" >
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="nome">Fornecedor</label>
                    <div class="controls">
                        <input type="text" id="nome" name="nome" value="" class="form-control col-6 col-xl-8 col-sm-8">

                    </div>
                </div>

                <label>Banco</label>
                <div>
                    <div class="form-group ">
                        <select class="form-control col-5 col-xl-4 col-sm-5 " name="codbanco" id="codbanco">
                            <?php  foreach ($banco as $xbanco): ?>
                                <option value="<?php echo $xbanco['codbanco'];?>"><?php echo $xbanco['nome'];?></option> 
                            <?php endforeach; ?>
                        </select> 
                    </div>
                </div>


                <label>Categoria</label>
                <div>
                    <div class="form-group ">
                        <select class="form-control col-5 col-xl-4 col-sm-5 " name="codcatlancto" id="codcatlancto" onchange="loadSubCat();">
                            <?php  foreach ($catlancto as $xcatlancto): ?>
                                <option value="<?php echo $xcatlancto['codcatlancto'];?>"><?php echo $xcatlancto['descricao'];?></option> 
                            <?php endforeach; ?>
                        </select> 
                    </div>
                </div>

                <label>SubCategoria</label>
                <div>
                    <div class="form-group ">
                        <select class="form-control col-5 col-xl-4 col-sm-5 " name="codsubcatlancto" id="codsubcatlancto">
                        </select> 
                    </div>
                </div><br>



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

<script src="js/fornecedor.js"></script>