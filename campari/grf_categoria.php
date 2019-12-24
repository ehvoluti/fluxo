<?php 

$filtro = '';
if ($_GET['filtrar_ano']) {
    $filtro=$_GET['filtrar_ano'];
}

$filtro_mes ='';
if ($_GET['filtrar_mes_de']) {
    $filtro_mes=$_GET['filtrar_mes_de'];
}

if ($_GET['filtrar_mes_ate']) {
    $filtro_mes.=" AND ".$_GET['filtrar_mes_ate'];
} else {
    $filtro_mes.=" AND ".$_GET['filtrar_mes_de']; 
}

$filtro_categoria = '';
if ($_GET['filtrar_categoria']) {
    $filtro_categoria=$_GET['filtrar_categoria'];
}

require("../include/config.php"); 
?>
    <!-- Step 1 - Include the fusioncharts core library -->
    <script type="text/javascript" src="../campari/js/fusioncharts.js"></script>
    <!-- Step 2 - Include the fusion theme -->
    <script type="text/javascript" src="../campari/js/themes/fusioncharts.theme.fusion.js"></script>

   <?php 
        
        $texto = grafico($filtro, $filtro_mes, $filtro_categoria); 
        //echo $texto[json_agg];
        //var_dump($texto);
   ?>

<div class="btn-toolbar">
    <button class="btn btn-primary" style=" margin-left:50px" data-toggle="modal" data-target="#filtrarModal">Filtros</button>
</div>

<!-- Modal de busca Botão filtrar -->
<form action="#" method="get">
    <div id="filtrarModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Filtrar na Grade</h4>
                    <button class="close" data-dismiss="modal" arial-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                

                <div class="modal-body">
                    <!--Ano-->
                    <div class="row-1">
                        <span>Ano</span>
                        <div class="col-3">

                                <select class="form-control" name="filtrar_ano" id="filtrar_ano">
                                    <?php
                                        $ano_listar = listar("lancamento", "DISTINCT EXTRACT(YEAR FROM dtemissao) AS ano",null,null, "ano DESC", null);
                                        //var_dump($ano_listar);
                                        foreach ($ano_listar as $xano_listar): 
                                    ?>
                                        <option value="<?php echo $xano_listar['ano'];?>"><?php echo $xano_listar['ano'];?></option> 
                                    <?php endforeach; ?>
                                </select> 
                        </div>
                    </div>

                    <!--Valores-->
                    <div class="row">
                        <div class="col-1">
                            <span>Mes</span>
                        </div>  
                        <div class="col-3">De:
                             <select class="form-control" name="filtrar_mes_de" id="filtrar_mes_de">
                                <option value=""></option>
                                <option value="01">01</option>
                                <option value="02">02</option>
                                <option value="03">03</option>
                                <option value="04">04</option>
                                <option value="05">05</option>
                                <option value="06">06</option>
                                <option value="07">07</option>
                                <option value="08">08</option>
                                <option value="09">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="col-3">Ate:
                             <select class="form-control" name="filtrar_mes_ate" id="filtrar_mes_ate">
                                <option value=""></option>
                                <option value="01">01</option>
                                <option value="02">02</option>
                                <option value="03">03</option>
                                <option value="04">04</option>
                                <option value="05">05</option>
                                <option value="06">06</option>
                                <option value="07">07</option>
                                <option value="08">08</option>
                                <option value="09">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                    <!--Categoria-->
                    <div class="row-1">
                        <span>Categoria</span>
                        <div class="col-8">

                                <select class="form-control" name="filtrar_categoria" id="filtrar_categoria">
                                    <option value="">Categoria</option>
                                    <?php
                                        $categoria_listar = listar("catlancto", "codcatlancto, descricao",null,null, "descricao", null);
                                        //var_dump($ano_listar);
                                        foreach ($categoria_listar as $xcategoria_listar): 
                                    ?>
                                        <option value="<?php echo $xcategoria_listar['codcatlancto'];?>"><?php echo $xcategoria_listar['descricao'];?></option> 
                                    <?php endforeach; ?>
                                </select> 
                        </div>
                    </div>


                    </div>


                </div>
                
                <div class="modal-footer">
                    <input type="submit" value="Buscar" class="btn btn-primary" >
                    <button class="btn btn-info" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</form> 
<!-- Fim do Modal-->

<script type="text/javascript">
    FusionCharts.ready(function(){
    var fusioncharts = new FusionCharts({
    type: 'column2d',
    renderAt: 'chart-container',
    width: '500',
    height: '390',
    dataFormat: 'json',
    dataSource: {
        // Chart Configuration
        "chart": {
            "caption": "Despesas por Categoria",
            "subCaption": "Mes e Ano atual apenas",
            "xAxisName": "Filtros:<br>Ano: <?php echo $filtro;?> Mes:<?php echo $filtro_mes;?> Categoria:<?php echo $filtro_categoria;?>",
            //"yAxisName": "Valores",
			"numberPrefix": "R$",
            //"numberSuffix": "R$",
            "theme": "fusion",
        },
        // Chart Data
        "data": <?php echo $texto[json_agg]; ?>

        /*[{
            "label": "Venezuela",
            "value": "200"
        }, {
            "label": "Saudi",
            "value": "260"
        }, {
            "label": "Canada",
            "value": "100"
        }, {
            "label": "Iran",
            "value": "140"
        }, {
            "label": "Russia",
            "value": "115"
        }, {
            "label": "UAE",
            "value": "100"
        }, {
            "label": "US",
            "value": "30"
        }, {
            "label": "China",
            "value": "300"
        }]*/
    }
});
    fusioncharts.render();
    });
</script>
    <div id="chart-container">FusionCharts XT will load here!</div>
