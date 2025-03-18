<?php 

$destino = $_POST['conta_destino'];
$origem = $_POST['accountId'];
$valor = $_POST['amount'];
$host = 'localhost';
$db = 'bank_exercise';
$Username = 'db';
$Password = '12345';

$pdo = new PDO("mysql:host=$host;dbname=$db", $Username, $Password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = $pdo->prepare("CALL TransferMoney(:sender, :receiver, :value);");
//$sql->bindParam(':sender', $origem, ':receiver', $destino, ':value', $valor);
$sql->execute(['sender' => $origem, 'receiver' => $destino, 'value' => $valor]);

$resultado = $sql->fetch(PDO::FETCH_ASSOC);
if (resultado)

?>
