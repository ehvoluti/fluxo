<?php

require_once("../include/config.php");

$tipos = listar("subcatlancto", "*", NULL, NULL, "descricao", NULL);
//$cat_listar = listar("subcatlancto", "descricao");

?>
<?php require_once("topo.php"); ?>
<div class="container">
    <legend>
        <h1>SubCategoria</h1>
    </legend>
    <div class="btn-toolbar">
        <a href="incluirSubcategoria.php"><button class="btn btn-primary">Nova Categoria</button></a>
    </div><br>
    <div class="form-group">
        <table class="table">
            <thead class="bg-dark text-light">
                <tr>
                    <td>#</td>
                    <td>Descrição</td>
                    <td>Categoria</td>
                    <!--<td>Previsao de Despesas</td> --> <!-- Aguardando implementacao-->
                    <td style="width: 36px;">Ações</td>
                </tr>
            </thead>
        
            <tbody>
                <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?php echo $tipo['codsubcatlancto']; ?></td>
                        <td><?php echo $tipo['descricao']; ?></td>
                        <?php $catlancto = listar("catlancto", "*", "codcatlancto=".$tipo['codcatlancto']);  
                            foreach ($catlancto as $xcatlancto): ?>
                            <th><?php echo $xcatlancto['descricao'];?></th>
                            <?php endforeach; ?>
                        <!-- Aguardando implementacao-- <td><?php //echo $tipo['previsao']; ?></td>  -->
                        <td>
                            <a href="alterarSubCategoria.php?id=<?php echo $tipo["codsubcatlancto"]; ?>"><i class="fas fa-edit"></i></i></a>
                            <a href="excluirSubCategoria.php?id=<?php echo $tipo["codsubcatlancto"]; ?>"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>    
<?php include("rodape.php"); ?>