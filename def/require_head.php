<?php
if(!isset($con)){
	$con = new Connection();
}

$tiposervidor = param("SISTEMA", "TIPOSERVIDOR", $con);

$arr_idtable = array(
	"NFSaida",
	"CadNotaFiscalTS",
	"Orcamento",
	"NFDevCliente",
	"NFDevFornecedor",
	"NFImportacao",
	"NFComplemento",
	"NFPerda",
	"NFProdutor",
	"NFRemessaCli",
	"NFRemessaFor",
	"ImpEtiqCliente",
	"EtiqImpGondola",
	"Recibo",
	"ImpCarne",
	"SpedPisCofins",
	"SpedFiscal",
	"Sintegra",
	"AtuaIntExport",
	"BalancaArq",
	"ExportaColetor",
	"IntegracaoBancoRemessa",
	"MgContabilidade",
	"Contmatic",
	"Buddywin",
	"GeracaoLayout",
	"Saneamento",
	"MalaDireta",
	"CadLayout",
	"Lancto",
	""
);
?>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-store, no-cache, must-revalidate" />
<meta http-equiv="Pragma-directive" content="no-cache">
<meta http-equiv="Cache-Directive" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="robots" content="noindex">

<!-- JQUERY -->
<script type="text/javascript" src="../lib/jquery-2.1.4/jquery-2.1.4.min.js"></script>

<!-- JQUERY UI -->
<script type="text/javascript" src="../lib/jquery-ui-1.11.4/jquery-ui.js"></script>
<link type="text/css" rel="stylesheet" href="../lib/jquery-ui-1.11.4/jquery-ui.css">

<!-- JQUERY MEIOMASK -->
<script type="text/javascript" src="../lib/jquery-meiomask-1.1.4/meiomask.min.js"></script>

<!-- BOOTSTRAP -->
<!--<script type="text/javascript" src="../lib/bootstrap-3.3.6/js/bootstrap.min.js"></script>-->
<!--<link rel="stylesheet" type="text/css" href="../lib/bootstrap-3.3.6/css/bootstrap.min.css">-->

<!-- MOMENT -->
<script type="text/javascript" src="../lib/moment-2.10.6/moment.js"></script>

<!-- TABLE SORTER -->
<script type="text/javascript" src="../lib/tablesorter-2.0.5/tablesorter.js"></script>

<!-- FONT AWESOME -->
<link type="text/css" rel="stylesheet" href="../lib/font-awesome-4.6.3/css/font-awesome.min.css">

<!-- MULTIPLE SELECT -->
<script type="text/javascript" src="../lib/multiple-select-1.2.1/multiple-select.js"></script>
<link type="text/css" rel="stylesheet" href="../lib/multiple-select-1.2.1/multiple-select.css">

<!-- POST WINDOW -->
<script type="text/javascript" src="../lib/postwindow-1.0.0/postwindow.js"></script>

<?php
if(!isset($_REQUEST["mobile"])){
	if((in_array($_REQUEST["idtable"], $arr_idtable) && $tiposervidor == "1") || in_array($_REQUEST["idtable"], array("PontoVenda"))){
		?>
		<applet id="WSApplet" code="WSApplet.class" archive="../java/WSApplet.jar" width="0" height="0">
			<param name="initial_focus" value="false">
		</applet>
		<?php
	}
}

require_once("../addon/addon.php");

// Inclui os arquivos CSS padroes
$arr_dirname = array("../css/def", "../css/elem", "../css/misc");
foreach($arr_dirname as $dirname){
	$arr_filename = scandir($dirname);
	foreach($arr_filename as $filename){
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if($extension === "css"){
			echo "<link type='text/css' rel='stylesheet' href='{$dirname}/{$filename}'>\n";
		}
	}
}

// Inclui os arquivos JS padroes
$arr_dirname = array("../js/def");
foreach($arr_dirname as $dirname){
	$arr_filename = scandir($dirname);
	foreach($arr_filename as $filename){
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if($extension === "js"){
			echo "<script type='text/javascript' src='{$dirname}/{$filename}'></script>\n";
		}
	}
}

// Outros arquivos JS
$arr_filename = array("../js/misc/selecaoproduto.js");
foreach($arr_filename as $filename){
	$extension = pathinfo($filename, PATHINFO_EXTENSION);
	if($extension === "js"){
		echo "<script type='text/javascript' src='{$filename}'></script>\n";
	}
}
?>

<script type="text/javascript">
	var server = ({
		secound: "<?= date("s") ?>",
		minute: "<?= date("i") ?>",
		hour: "<?= date("h") ?>",
		day: "<?= date("d") ?>",
		month: "<?= date("m") ?>",
		year: "<?= date("Y") ?>"
	});

	function java_etiqueta(tipo, codetiqueta){
		if($("#java").html().length === 0){
			$.ajax({
				url: "../ajax/impetiq_java.php",
				data: ({
					codetiqueta: codetiqueta,
					tipo: tipo
				}),
				dataType: "html",
				success: function(html){
					$("#java").html(html);
				}
			});
		}
	}
</script>
