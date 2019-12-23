<?php

require_once("../include/config.php");

$tipos = listar("fornecedor", "*", NULL, NULL, "nome", NULL);
//$cat_listar = listar("subcatlancto", "descricao");

?>
<?php require_once("topo.php"); ?>
<div class="container">
    <legend>
        <h1 style="color:gray">Fornecedor</h1>
    </legend>
    <div class="btn-toolbar">
        <a href="incluirFornecedor.php"><button class="btn btn-primary">Novo Fornecedor</button></a>
    </div><br>
    <div class="form-group">
        <table class="table">
            <thead class="bg-dark text-light">
                <tr>
                    <td>#</td>
                    <td>Fornecedor</td>
                    <td>Banco</td>
                    <td>Categoria</td>
                    <td>SubCategoria</td>
                    <!--<td>Previsao de Despesas</td> --> <!-- Aguardando implementacao-->
                    <td style="width: 36px;">Ações</td>
                </tr>
            </thead>
        
            <tbody>
                <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?php echo $tipo['codfornec']; ?></td>
                        <td><?php echo $tipo['nome']; ?></td>
                        <?php $banco = listar("banco", "*", "codbanco=".$tipo['codbanco']);  
                            foreach ($banco as $xbanco): ?>
                            <th><?php  if (STRLEN($xbanco['nome'])>0) {
                                echo $xbanco['nome'];} else {echo ':(';};?></th>
                            <?php endforeach; ?>
                        <?php $catlancto = listar("catlancto", "*", "codcatlancto=".$tipo['codcatlancto']);  
                            $guarda_codcatlancto=$tipo['codcatlancto'];
                            foreach ($catlancto as $xcatlancto): ?>
                            <th><?php echo $xcatlancto['descricao'];?></th>
                            <?php endforeach; ?>
                        <?php $subcatlancto = listar("subcatlancto", "*", "codsubcatlancto=".$tipo['codsubcatlancto']." AND codcatlancto=".$guarda_codcatlancto);  
                            foreach ($subcatlancto as $xsubcatlancto): ?>
                            <th><?php echo $xsubcatlancto['descricao'];?></th>
                            <?php endforeach; ?>    

                        <!-- Aguardando implementacao-- <td><?php //echo $tipo['previsao']; ?></td>  -->
                        <td>
                            <a href="alterarFornecedor.php?id=<?php echo $tipo["codfornec"]; ?>"><i class="fas fa-edit"></i></i></a>
                            <a href="excluirFornecedor.php?id=<?php echo $tipo["codfornec"]; ?>"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>    
<?php include("rodape.php"); ?>