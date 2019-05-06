<?php
echo "Iniciando....<br>";

   if(isset($_FILES['carrega_csv']))
   {
      date_default_timezone_set("Brazil/East"); //Definindo timezone padrão

      $ext = strtolower(substr($_FILES['carrega_csv']['name'],-4)); //Pegando extensão do arquivo
      $new_name = "despesas" . $ext; //Definindo um novo nome para o arquivo
      $dir = 'uploads/'; //Diretório para uploads

      move_uploaded_file($_FILES['carrega_csv']['tmp_name'], $dir.$new_name); //Fazer upload do arquivo
   }

/* 
	Importando informações de um arquivo CSV para dentro do lancamento do websac
 */

$delimitador = ',';
$cerca = NULL;
$f = fopen("uploads/despesas.csv","r");

if (!$f)
{
	echo "Erro ao abrir a arquivo.";
	exit;
}
  echo "Arquivo encontrado!!<br>";
//Websac banco de teste HUGO banco real
 if(!@($conexao=pg_connect ("host=localhost dbname=hugo port=5432 user=postgres password=postgres"))) {
   print "Nao foi possivel estabelecer uma conexao com o banco de dados.";
} else {
	echo "Rotinas da conexao... <br>";
	
	echo "Teste de leitura do despesas.csv"."<br>"; 
	//echo $conexao."<br>";	
 echo "___________________________________________________________"."<br>";
 ?>
 
 <html>
    <head>
		<meta charset="UTF-8">0
        <title>Transações</title>
		<link href="../admin/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>

        <h1>Movimentação Mensal</h1>
       
        <h2>Transações</h2>
        <table class="table"> <!--border="1" cellpadding="3" cellspacing="0" -->
		
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Fornecedor</th>
                    <th>Valor</th>
					<th>Banco</th>
					<th>Referencia</th>
                </tr>
            </thead>
            <tbody>
 
 <?php
while (!feof($f))
{
       // Montar registro com valores indexados pelo cabecalho
      //  $registro = array_combine($cabecalho, $linha);
      // Ler uma linha do arquivo
        $linha = fgetcsv($f, 0, $delimitador);
        if (!$linha) {
            continue;
        }
	$testalinha=substr($linha[0],0,4); 	
	//echo substr($linha[0],-4,4)."<br>";
    if ($testalinha=='2019') {
         //Tira virgula dos valores maiores que 999.99
         $linha[3] = str_replace(",","",$linha[3]);

         //Limita referencia a 35 character
         $linha[6] = substr($linha[6],0,35);
		 
		 //Se referencia for maior que 35 CHAR coloca todo conteudo na observacao
		 if (strlen ($linha[6])>35 ) {
			$observacao = $linha[6];
		 }

		 
		 //Verifica se deve gerar pagamento ou recebimento, # na frente da referencia é um recebimento
		if (SUBSTR($linha[6],0,1)=="#") {
			$pagrec='R';
		}else{
			$pagrec='P';
		}
?>		
        
	<tr>
			<td><?php echo $linha[0];?></td>
			<td><?php echo $linha[2];?></td>
			<td align="right"><?php echo $linha[3];?></td>
			<td><?php echo $linha[5];?></td>
			<td><?php echo $linha[6];?></td>
	</tr>
			
<?php		
			//Inclusao na tabela lancamento
			$sql = "INSERT INTO lancamentogru (codlanctogru, codestabelec, pagrec, tipoparceiro, codparceiro, codcondpagto, codbanco, codespecie, valorbruto, 
												dtlancto, dtemissao, codcatlancto, codsubcatlancto, codmoeda, favorecido, referencia, valorliquido, usuario, datalog, observacao)
					VALUES 
										(NULL, 1, '$pagrec', 'F', $linha[1], 2, $linha[4], 8, $linha[3], '$linha[0]', '$linha[0]', NULL, NULL, 1, '$linha[2]', '$linha[6]', $linha[3], 
							'CSV', CURRENT_DATE, '$observacao');";
			//echo $sql."<br>";				
            pg_query($conexao, $sql);
			
		
	}
}
	
fclose($f);
pg_close($conexao);
} //Fecha IF_ELSE da conexao
?>
        </tbody>
        </table>
    </body>
</html>	

