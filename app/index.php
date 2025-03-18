<?php
// configurações de erro
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// configurações para a conexao com a db
$host = 'localhost';
$db = 'bank_exercise';
$username = 'db';
$password = '12345';
$conexao = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $username, $password);
$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Requisição de autenticação
if (isset($_POST['username']) && !empty($_POST['username'])) // Verificando se o campo username foi enviado && Não esta vazio
{
    //fazendo a autenticação
    $user = $_POST['username'];
    $login = "SELECT id FROM account WHERE name = :user ;";
    try {
        $auth = $conexao->prepare($login);
        $auth->execute(['user' => $user]);
        $Resp = $auth->fetch(PDO::FETCH_ASSOC);
        if ($Resp) {
            $id = $Resp['id'];
            setcookie('id', $id, time() + 3600, "/"); // Cookie do id valido por 1 hora
        } else {
            echo "<script>alert('Usuario não encontardo')</script>";
        }
    } catch (PDOException $e) {
        echo 'Erro: ', $e->getMessage();
    }
}

// Se o cookie 'id' não estiver vazio
if (!empty($_COOKIE['id'])) {
    $id = $_COOKIE['id'];
} else {
    $id = '';
}

// Fazendo a querie
$resultado = $conexao->prepare("SELECT name, balance FROM account WHERE id = :valor");
$resultado->bindParam(':valor', $id);
$resultado->execute();
$linhas = $resultado->fetch(PDO::FETCH_ASSOC);

// Condicional : Se não houver um resultado para o id
if (!$linhas || $id == '') {
    login();
} else {
    // Atruibuido os resulados
    $nome = $linhas['name'];
    $saldo = $linhas['balance'];

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
              box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
              box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
          .button {
              background-color: #28a745;
              color: white;
              padding: 10px 20px;
              border: none;
              border-radius: 5px;
              cursor: pointer;
              font-size: 16px;
              margin-bottom: 20px;
              text-decoration: none;
              display: inline-block;
              width: 100%;
              text-align: center;
          }
          .button:hover {
              background-color: #218838;
          }
          .transfer-form {
              background-color: #ffffff;
              padding: 20px;
              border-radius: 8px;
              box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
      </style>
  </head>
  <body>

      <!-- Barra superior com informações da conta -->
      <div class='top-bar'>
          <h1>Banco Digital</h1>
          <div class='account-info'>
    <p><span>Nome da Conta: $nome</span></p>
    <p><span>Saldo:</span> R$ $saldo</p>
          </div>
      </div>

      <!-- Conteúdo adicional abaixo da barra superior -->
      <div class='content'>
          <h2>Bem-vindo ao seu Dashboard</h2>
          <p>Aqui você pode gerenciar suas finanças, ver transações recentes e muito mais.</p>

          <!-- Botão para exibir o formulário de transferência -->
          <details>
              <summary>Realizar Transferência</summary>
              <div class='transfer-form'>
                  <h3>Realizar Transferência</h3>
                  <form action='transaccion.php' method='post'>
                      <label for='accountId'>ID da Conta Destino:</label>
                      <input type='text' id='accountId' name='accountId' required>

                      <label for='amount'>Valor:</label>
                      <input type='number' id='amount' name='amount' step='0.01' required>

                      <button type='submit'>Transferir</button>
                  </form>
              </div>
          </details>

          <!-- Exemplo de transações recentes -->
          <div>
              <h2>Transações Recentes</h2>
              <ul>
                  <li>Transferência para Maria - R$ 200,00</li>
                  <li>Depósito - R$ 1.000,00</li>
                  <li>Pagamento de Conta - R$ 150,00</li>
              </ul>
          </div>
          <form action='index.php' method='post'>
            <button id='sair' type='submit'>Sair</button>
          </form>
      </div>

  </body>
  </html>";
}

function login()
{
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
              box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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

      <!-- Container do Login -->
      <div class='login-container'>
          <h2>Login</h2>
          <form action='index.php' method='post'>
              <input type='text' name='username' placeholder='Digite seu nome' required>
              <button id='sair' type='submit'>Entrar</button>
          </form>
          <div class='register-link'>
              <p>Não tem uma conta? <a href='registro.html'>Registre-se aqui</a>.</p>
          </div>
      </div>

  </body>
  </html>";
}

function dashboard() {}
