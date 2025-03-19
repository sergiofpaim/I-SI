<?php
// Configurações de erro
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações para a conexão com o banco de dados
$host     = 'localhost';
$db       = 'bank_exercise';
$username = 'db';
$password = '12345';

try {
    $conexao = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $username, $password);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Erro: ' . $e->getMessage();
    exit;
}

// Tratamento de logout
if (isset($_POST['LogOut'])) {
    setcookie('id', '', time() - 3600, "/");
    header('Location: index.php');
    exit;
}

// Requisição de autenticação
if (isset($_POST['username']) && !empty($_POST['username'])) { 
  $user  = $_POST['username'];
  auth($conexao, $user);
}

// Verifica se o cookie 'id' existe
$id = !empty($_COOKIE['id']) ? $_COOKIE['id'] : '';

// Consulta para obter informações da conta
$resultado = $conexao->prepare("SELECT name, balance FROM account WHERE id = :id");
$resultado->execute(['id' => $id]);
$linha = $resultado->fetch(PDO::FETCH_ASSOC);
$resultado->closeCursor();

// Requisição de transferencia
if (isset($_POST['accountId']) && isset($_POST['amount']))
{
  $dest = $_POST['accountId'];
  $valor = $_POST['amount'];
  if (is_numeric($dest) && is_numeric($valor) && $dest != $id) {
    transferMoney($conexao, $id, $dest, $valor);
  } else {
    echo "<script>alert('Valores Invalidos');</script>";
  }
}

// Caso não haja resultado ou o id esteja vazio, chama a função de login
if (!$linha || $id == '') {
    login();
} else {
  $nome  = $linha['name'];
  $saldo = $linha['balance'];
  dashboard($conexao, $id, $nome, $saldo);

}

function transferMoney($conexao ,$id, $dest, $amount)
{
  echo "<script>console.log('Função transferMoney chamada');</script>";
  try {
    $conexao->beginTransaction();

    $conexao->query("SELECT 1")->closeCursor();

    $transaction = $conexao->prepare("CALL TransferMoney(:sender, :receiver, :amount);");
    $transaction->execute(['sender' => $id, 'receiver' => $dest, 'amount' => $amount]);
    $transaction->closeCursor();
    $conexao->commit();
    echo "<script>alert('Transação conluida!');window.location.href = 'index.php';</script>";
    exit();
  } catch (PDOException $e){
    $conexao->rollBack();
    echo "<script>alert('Erro: " . addslashes($e->getMessage()) . ");window.location.href = 'index.php';</script>";
  }
}

function listTransanction($conexao, $id)
{
  try {
  $query = $conexao->prepare("SELECT sender_id, receiver_id, amount, created_at FROM transaction WHERE sender_id = :id OR receiver_id = :id ORDER BY transaction_id DESC");
  $query->execute(['id' => $id]);

  if ($query->rowCount() > 0)
  {
    while ($resultado = $query->fetch(PDO::FETCH_ASSOC))
    {

      $amount = $resultado['amount'];
      $dest = $resultado['receiver_id'];
      $origem = $resultado['sender_id'];
      if ($resultado['sender_id'] == $id)
      {
        echo "<li>Transação para conta $dest: $amount</li>";
      } else {
        echo "<li>Transação da conta $origem: $amount</li>";
      }
    }
  } else {
    echo '<li>Sem transações</li>';
  }
  $query->closeCursor();
  } catch (PDOException $e){
    echo 'Erro: ' . $e->getMessage();
  }
}

function auth($conexao, $user)
{
    try {
        $auth = $conexao->prepare("SELECT id FROM account WHERE name = :user");
        $auth->execute(['user' => $user]);
        $resp = $auth->fetch(PDO::FETCH_ASSOC);
        $auth->closeCursor();
        if ($resp) {
            $id = $resp['id'];
            // Cookie válido por 1 hora
            setcookie('id', $id, time() + 3600, "/");
            header("Location: " . $_SERVER['PHP_SELF']); // Atualiza a página para não ter que reenviar o login para abrir o dashboard
            exit();
        } else {
            echo "<script>alert('Usuário não encontrado');</script>";
        }
    } catch (PDOException $e) {
        echo 'Erro: ' . $e->getMessage();
    }
}

/**
 * Função que exibe a página de login.
 */
function login() {
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Página de Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .login-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .login-container button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .register-link {
            margin-top: 15px;
            font-size: 14px;
            color: #555;
        }
        .register-link a {
            color: #007bff;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class='login-container'>
        <h2>Login</h2>
        <form action='index.php' method='post'>
            <input type='text' name='username' placeholder='Digite seu nome' required>
            <button type='submit'>Entrar</button>
        </form>
        <div class='register-link'>
            <p>Não tem uma conta? <a href='registro.html'>Registre-se aqui</a>.</p>
        </div>
    </div>
</body>
</html>";
    exit;
}

/**
 * Função que exibe o dashboard com as informações da conta.
 */
function dashboard($conexao, $id, $nome, $saldo) {
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Dashboard Bancário</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .top-bar {
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .top-bar h1 {
            margin: 0;
            font-size: 24px;
        }
        .account-info {
            text-align: center;
            margin-top: 10px;
        }
        .account-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        .account-info p span {
            font-weight: bold;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 600px;
            margin-top: 20px;
        }
        .content h2 {
            color: #333;
            text-align: center;
        }
        .content p {
            color: #555;
            text-align: center;
        }
        .transfer-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .transfer-form label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
        }
        .transfer-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .transfer-form button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .transfer-form button:hover {
            background-color: #0056b3;
        }
        details {
            margin-bottom: 20px;
        }
        summary {
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        summary:hover {
            background-color: #e9ecef;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
        }
        .logout-form button {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        .logout-form button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class='top-bar'>
        <h1>Banco Digital</h1>
        <div class='account-info'>
            <p><span>Nome da Conta:</span> $nome</p>
            <p><span>Saldo:</span> R$ $saldo</p>
        </div>
    </div>
    <div class='content'>
        <h2>Bem-vindo ao seu Dashboard</h2>
        <p>Aqui você pode gerenciar suas finanças, ver transações recentes e muito mais.</p>
        <details>
            <summary>Realizar Transferência</summary>
            <div class='transfer-form'>
                <h3>Realizar Transferência</h3>
                <form action='index.php' method='post'>
                    <label for='accountId'>ID da Conta Destino:</label>
                    <input type='text' id='accountId' name='accountId' required>
                    <label for='amount'>Valor:</label>
                    <input type='number' id='amount' name='amount' step='0.01' required>
                    <button type='submit'>Transferir</button>
                </form>
            </div>
        </details>
        <div>
          <h2>Transações Recentes</h2>
          <ul>
          ", listTransanction($conexao, $id), "
          </ul>
        </div>
        <form class='logout-form' action='index.php' method='post'>
            <input type='hidden' name='LogOut' value='LogOut'>
            <button type='submit'>Sair</button>
        </form>
    </div>
</body>
</html>";
    exit;
}
?>
