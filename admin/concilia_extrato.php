<?php

require("../include/config.php");
//Valida se ao carregar a tela deve processar
$_POST['entrada']="0";
	if($_POST['filtrar_banco']){
		$sel_banco = $_POST['filtrar_banco'];	
	} else {
		$sel_banco = "1";
	}
//	echo $sel_banco;

   if(isset($_FILES['carrega_csv']))
   {
      date_default_timezone_set("Brazil/East"); //Definindo timezone padrão

      $ext = strtolower(substr($_FILES['carrega_csv']['name'],-4)); //Pegando extensão do arquivo
      //echo $ext;
      $new_name = "extrato.csv"; //Definindo um novo nome para o arquivo
      $dir = 'uploads/'; //Diretório para uploads

      move_uploaded_file($_FILES['carrega_csv']['tmp_name'], $dir.$new_name); //Fazer upload do arquivo
   }



$banco_listar = listar("banco", "codbanco, nome");
$param_banco = ver("banco", "conc_delimitador, conc_sinaldesp, conc_posdata, conc_posdescricao, conc_posvalor", "codbanco=".$sel_banco);
//echo "<br>delimitador: ".$param_banco[conc_delimitador];
//echo "<br>conc_sinaldesp: ".$param_banco[conc_sinaldesp];
//echo "<br>conc_posdata: ".$param_banco[conc_posdata];
//echo "<br>conc_posdescricao: ".$param_banco[conc_posdescricao];
//echo "<br>conc_posvalor: ".$param_banco[conc_posvalor];
//var_dump($param_banco);
?>

<?php include("topo.php"); ?>

<script src="js/jquery.maskMoney.js" type="text/javascript"></script>

<div class="container">
	<legend>
		<h6>Rotinas -> Concilia</h6>
	</legend>
	<div class="btn-toolbar">

		<button class="btn btn-primary" style=" margin-left:50px" data-toggle="modal" data-target="#upload">Upload</button>
	</div>
	<br>
	<div class="form-group">
		<table class="table table-sm table-responsive-sm table-responsive-md">
			<thead class="thead-dark">
				<tr>
                    <th>Data</th>
                    <th>Descricao</th>
                    <th>Valor</th>
                    <th>Concilia</th>
                </tr>
			</thead>
			<?php 
//			if($_POST['entrada']="1") {

				// $delimitador = ',';	
				// if ($sel_banco=="1"){
				// 	$delimitador = ';';	
				// } else {
				// 	$delimitador = ',';	
				// }
				$delimitador = $param_banco[conc_delimitador];	
				
				$cerca = NULL;
				$f = fopen("uploads/extrato.csv","r");

				if (!$f)
				{
					echo "Erro ao abrir a arquivo.";
					exit;
				}
			?>
			<tbody>
				<?php
				while (!feof($f))
				{
				      // Ler uma linha do arquivo
				        $linha = fgetcsv($f, 0, $delimitador);
				        //var_dump($linha);
				        if (!$linha) {
				            continue;
				        }

				        //echo "<br> Var Banco == ".$sel_banco;
					//Tratamento das linhas
				    $dtextrato =  $linha[$param_banco[conc_posdata]];
				    $valor_extrato = $linha[$param_banco[conc_posvalor]];
				    $Descricao_extrato = $linha[$param_banco[conc_posdescricao]];
				    //Verificar se é um Recebimento ou Pagamento	
					if ((substr($valor_extrato,0,1))==$param_banco[conc_sinaldesp] AND $param_banco[conc_sinaldesp]=="-" ) {
						$sinal_extrato="P";
					} elseif ((substr($valor_extrato,0,1))<>$param_banco[conc_sinaldesp] AND $param_banco[conc_sinaldesp]=="-" ) {
						$sinal_extrato="R";
					} elseif  ((substr($valor_extrato,0,1))=="-" AND $param_banco[conc_sinaldesp]="X") {
						$sinal_extrato="R";
					} else {
						$sinal_extrato="P";
					}

					//Tratamento para linha do Itau, precisa melhorar essa rotina
					if ($sel_banco=="1"){
					 	$dtextrato = substr($linha[0],6,4)."-".substr($linha[0],3,2)."-".substr($linha[0],0,2);
					 	$valor_extrato = str_replace(",",".",$valor_extrato);
					}

					
					//Tratamento das linhas por banco				        
				    //Itau    
					// if ($sel_banco=="1"){
					// 	$dtextrato = substr($linha[0],6,4)."-".substr($linha[0],3,2)."-".substr($linha[0],0,2);
					// 	$valor_extrato = str_replace(",",".",str_replace(".", "", $linha[3]));
					// 	$Descricao_extrato = $linha[1];
					// 	//echo "<br> Data:".$dtextrato." / ". $valor;
	
					// 	//Verificar se é um Recebimento ou Pagamento	
					// 	if ((substr($valor_extrato,0,1))=="-"){
					// 		$sinal_extrato="P";
					// 	} else {
					// 		$sinal_extrato="R";
					// 	}

					// } else {
						
					// 	//echo "Entrou banco 5";
					// 	$dtextrato = $linha[0];
					// 	$Descricao_extrato = $linha[2];
					// 	$valor_extrato =  $linha[3];
					// 	//Verificar se é um Recebimento ou Pagamento	
					// 	if ((substr($valor_extrato,0,1))=="-"){
					// 		$sinal_extrato="R";
					// 	} else {
					// 		$sinal_extrato="P";
					// 	}

						//echo "<br> Data:".$dtextrato." / ". $valor;
					//}				        


					//Valida se linha já faz parte das informações pelo ano
				    if (substr($dtextrato,0,2)=='20') {
						 
				?>
					<tr>
							<td><?php echo $dtextrato;?></td>
							<td><?php echo $Descricao_extrato;?></td>
							<td align="right"><?php echo number_format($valor_extrato,2,",",".");?></td>
							<?php
								$where = " codbanco=".$sel_banco." AND dtliquid BETWEEN CAST('".$dtextrato."' AS date)-2 AND CAST('".$dtextrato."' AS date)+2 AND pagrec='".$sinal_extrato."' AND valorpago=abs(".$valor_extrato.")";
								$nubank = listar("lancamento", "codlancto,favorecido,referencia", $where, null, null);
								if(isset($nubank)) {
									$i = 1;
									foreach ($nubank as $Xnubank):
										if ($i>1) {
											$resp = '<br> : '.$Xnubank[codlancto].'->'.$Xnubank[favorecido];
										} else {
											$resp = ' : '.$Xnubank[codlancto].'->'.$Xnubank[favorecido];
										}
										$i = $i+1;
									endforeach;
								} else {
									$resp = "Não encontrado";
								}

								}
							?>
							<? if ($resp=="Não encontrado") {
									echo '<td style="background-color:Tomato;">'.$resp.'</td>';
								} else { echo '<td>'.$resp.'</td>';
								} ?>	
					</tr>
							
				<?php		
							
						
					}
				
				fwrite($f, "sem dados");	
				fclose($f);

			//	move_uploaded_file($_FILES['carrega_csv']['tmp_name'], $dir."processado.old"); //Fazer upload do arquivo
			//}
				?>						

			</tbody>
		</table>
	</div>
	
</div>

<!-- Modal de busca para o Banco -->
<form action="concilia_extrato.php" method="POST" enctype="multipart/form-data">
	<div id="upload" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Faça o Upload do arquivo</h4>
					<button class="close" data-dismiss="modal" arial-label="Fechar">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				
				<div class="modal-body">
					<div class="row">
						<div class="col-6">
							<span>Banco</span>
						</div>	
						<div class="col-6">
								<select class="form-control" name="filtrar_banco" id="filtrar_banco">
									<option value="">-- Todos --</option> 
									<?php  foreach ($banco_listar as $xbanco): ?>
										<option value="<?php echo $xbanco['codbanco'];?>"><?php echo $xbanco['nome'];?></option> 
									<?php endforeach; ?>
								</select> 
						</div>
						<div></div>
						<div class="row">
							<div class="col-12">
								<input type="file" name="carrega_csv">
      						</div>
						</div>
					</div>	
				</div>
				
				<div class="modal-footer">
					<input type="submit" value="Processa" class="btn btn-primary" <? $_POST['entrada']="1";?> >
					<button class="btn btn-info" data-dismiss="modal">Fechar</button>
				</div>
			</div>
		</div>
	</div>
</form> 



	<script type="text/javascript">
    $(function(){
        $("#filtrar_valor_de").maskMoney();
        $("#filtrar_valor_ate").maskMoney();
    })
    </script>

<?php include("rodape.php"); ?>