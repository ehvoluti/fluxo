<?php

require("../include/config.php");

$mensagens = listar("fale_conosco", "*");

?>
<?php include("topo.php"); ?>
<div class="row">
    <div class="span12">
        <legend class="">
            <h1>Lista de Mensagens</h1>
        </legend>

        <div class="well">
            <table width="100%" class="table">
                <col style="width:10%">
                <thead>
                    <tr class="bold">
                        <td>#</td>
                        <td>Nome</td>
                        <td>E-mail</td>
                        <td>Assunto</td>
                        <td style="width: 36px;">Ações</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mensagens as $mensagem): ?>
                        <tr>
                            <td><?php echo $mensagem['id']; ?></td>
                            <td><?php echo $mensagem['nome']; ?></td>
                            <td><?php echo $mensagem['email']; ?></td>
                            <td><?php echo $mensagem['assunto']; ?></td>
                            <td class="center">
                                <a href="verMensagem.php?id=<?php echo $mensagem["id"]; ?>"><i class="icon-eye-open"></i></a>
                                <a href="excluirMensagem.php?id=<?php echo $mensagem["id"]; ?>"><i class="icon-remove"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
<?php include("rodape.php"); ?>