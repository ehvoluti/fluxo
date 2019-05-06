<?php
require_once 'ofx.php';
require_once 'upload1.php';

//Usa conexao WebSac
//require_once("websac/require_file.php");
//require_file("connection.class.php");
require_once 'connection.class.php';

$ofx = new Ofx($dir.$new_name);
$saldo = $ofx->getBalance();

// Inicia uma nova conexao
flush();
$con = new Connection();
?>
<html>
    <head>
        <title>Transações</title>
    </head>
    <body>
        <h1>Data final no arquivo é de: <?php   echo date("d/m/Y", strtotime($saldo['date'])); ?></h1> 
       
        <h2>Transações</h2>
        <table border="1" cellpadding="3" cellspacing="0">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ofx->getTransactions() as $transaction) : ?>
                    <tr>
                        <td><?php $dataofx = date("Y-m-d", strtotime(substr($transaction->DTPOSTED, 0, 8))); echo $dataofx; ?></td>
                        <td><?php echo $transaction->MEMO; ?></td>
                        <td><?php echo $transaction->TRNTYPE; ?></td>
                        <td><?php echo $transaction->TRNAMT; $valorparcela = floatval($transaction->TRNAMT)*(-1); ?></td>
                    </tr>
                <?php
					$idofx = substr($transaction->DTPOSTED, 0, 8);
					if ($transaction->TRNAMT <0) {
						$query = "INSERT INTO ofx values ('$idofx'||NEXTVAL('idofx'),'$dataofx', $valorparcela, '$transaction->MEMO', 'P',5);";
						echo $query."<br>";
						$execultado = $con->exec($query);
						$con->start_transaction();
						if(!$execultado){
							$con->rollback();
							echo "<input type=\"submit\" value=\"Verificar\">";
							die("error");
						}
						$con->commit();
					}
					endforeach;
					echo "<br>gravado com sucesso!!<br><br>";
					?>
            </tbody>
        </table>
    </body>
</html>