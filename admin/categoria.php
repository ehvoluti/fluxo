<?php

require("../include/config.php");

$tipos = listar("catlancto", "*");

?>
<?php include("topo.php"); ?>
<div class="row">
    <div class="span12">
        <legend class="">
            <h1>Categoria</h1>
        </legend>
        <div class="btn-toolbar">
            <a href="incluirCategoria.php"><button class="btn btn-primary">Nova Categoria</button></a>
        </div>
        <div class="well">
            <table width="100%" class="table">
                <col style="width:10%">
                <thead>
                    <tr class="bold">
                        <td>#</td>
                        <td>Descricao</td>
						<td>Previsao de Despesas</td>
                        <td style="width: 36px;">Ações</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                        <tr>
                            <td><?php echo $tipo['codcatlancto']; ?></td>
                            <td><?php echo $tipo['descricao']; ?></td>
							<td><?php echo $tipo['previsao']; ?></td>
                            <td>
                                <a href="alterarCategoria.php?id=<?php echo $tipo["codcatlancto"]; ?>"><i class="icon-pencil"></i></a>
                                <a href="excluirCategoria.php?id=<?php echo $tipo["codcatlancto"]; ?>"><i class="icon-remove"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include("rodape.php"); ?>