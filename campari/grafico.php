<?php require("../include/config.php"); ?>
<html>
<head>
    <!-- Step 1 - Include the fusioncharts core library -->
    <script type="text/javascript" src="js/fusioncharts.js"></script>
    <!-- Step 2 - Include the fusion theme -->
    <script type="text/javascript" src="js/themes/fusioncharts.theme.fusion.js"></script>

   <?php 
        $texto = grafico("texto qualquer"); 
        //echo $texto[json_agg];
        //var_dump($texto);
   ?>

   <br><br><br>

<script type="text/javascript">
    FusionCharts.ready(function(){
    var fusioncharts = new FusionCharts({
    type: 'column2d',
    renderAt: 'chart-container',
    width: '700',
    height: '400',
    dataFormat: 'json',
    dataSource: {
        // Chart Configuration
        "chart": {
            "caption": "Despesas por Categoria",
            "subCaption": "Mes e Ano atual apenas",
            "xAxisName": "Categorias",
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
</head>
<body>
    <div id="chart-container">FusionCharts XT will load here!</div>
</body>
</html>
