<?php

require("../include/config.php");

$tipos = listar("catgoria", "*");

?>
<?php include("topo.php"); ?>
<div class="row">
    <div class="span12">
        <legend class="">
            <h1>Categoria</h1>
        </legend>
        <div class="btn-toolbar">
            <a href="incluirTipoencomenda.php"><button class="btn btn-primary">Nova Categoria</button></a>
        </div>
        <div class="well">
            <table width="100%" class="table">
                <col style="width:10%">
                <thead>
                    <tr class="bold">
                        <td>#</td>
                        <td>Codigo</td>
                        <td>Descricao</td>
                        <td style="width: 36px;">Ações</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                        <tr>
                            <td><?php echo $tipo['codcatlancto']; ?></td>
                            <td><?php echo $tipo['descricao']; ?></td>
                          -- <td><?php echo $tipo['valor_km']; ?></td>
                            <td><?php echo $tipo['prazo_maximo']; ?></td>
                            <td>
                                <a href="alterarTipoencomenda.php?id=<?php echo $tipo["id"]; ?>"><i class="icon-pencil"></i></a>
                                <a href="excluirTipoencomenda.php?id=<?php echo $tipo["id"]; ?>"><i class="icon-remove"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include("rodape.php"); ?>