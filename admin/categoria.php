<?php

require_once("../include/config.php");

$tipos = listar("catlancto", "*", NULL, NULL, "descricao", NULL);

?>
<?php require_once("topo.php"); ?>
<div class="container">
    <legend>
        <h1>Categoria</h1>
    </legend>
    <div class="btn-toolbar">
        <a href="incluirCategoria2.php"><button class="btn btn-primary">Nova Categoria</button></a>
    </div><br>
    <div class="form-group">
        <table class="table">
            <thead class="bg-dark text-light">
                <tr>
                    <td>#</td>
                    <td>Descrição</td>
                    <!--<td>Previsao de Despesas</td> --> <!-- Aguardando implementacao-->
                    <td style="width: 36px;">Ações</td>
                </tr>
            </thead>
        
            <tbody>
                <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?php echo $tipo['codcatlancto']; ?></td>
                        <td><?php echo $tipo['descricao']; ?></td>
                        <!-- Aguardando implementacao-- <td><?php //echo $tipo['previsao']; ?></td>  -->
                        <td>
                            <a href="alterarCategoria.php?id=<?php echo $tipo["codcatlancto"]; ?>"><i class="fas fa-edit"></i></i></a>
                            <a href="excluirCategoria.php?id=<?php echo $tipo["codcatlancto"]; ?>"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>    
<?php include("rodape.php"); ?>