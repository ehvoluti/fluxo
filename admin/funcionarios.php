<?php

require("../include/config.php");

$clientes = listar("funcionarios", "*");

?>
<?php include("topo.php"); ?>
<div class="row">
    <div class="span12">
        <legend class="">
            <h1>Lista de Funcionários</h1>
        </legend>
        <div class="btn-toolbar">
            <a href="incluirFuncionario.php"><button class="btn btn-primary">Novo Funcionário</button></a>
        </div>
        <div class="well">
            <table width="100%" class="table">
                <col style="width:10%">
                <thead>
                    <tr class="bold">
                        <td>#</td>
                        <td>Nome</td>
                        <td>E-mail</td>
                        <td>Perfil</td>
                        <td style="width: 36px;">Ações</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <?php $perfil = ver("perfis", "nome", "id={$cliente['prf_id']}"); ?>
                        <tr>
                            <td><?php echo $cliente['id']; ?></td>
                            <td><?php echo $cliente['nome']; ?></td>
                            <td><?php echo $cliente['email']; ?></td>
                            <td><?php echo $perfil['nome']; ?></td>
                            <td>
                                <a href="alterarFuncionario.php?id=<?php echo $cliente["id"]; ?>"><i class="icon-pencil"></i></a>
                                <a href="excluirFuncionario.php?id=<?php echo $cliente["id"]; ?>"><i class="icon-remove"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!--<div class="pagination">
            <ul>
                <li><a href="#">Prev</a></li>
                <li><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">3</a></li>
                <li><a href="#">4</a></li>
                <li><a href="#">Next</a></li>
            </ul>
        </div>-->

        <div class="modal small hide fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel">Delete Confirmation</h3>
            </div>
            <div class="modal-body">
                <p class="error-text">Are you sure you want to delete the user?</p>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
                <button class="btn btn-danger" data-dismiss="modal">Delete</button>
            </div>
        </div>

    </div>
</div>
<?php include("rodape.php"); ?>