<?php
// Error settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$dbHost     = 'localhost';
$dbName     = 'bank_exercise';
$dbUsername = 'db';
$dbPassword = '12345';

try {
    $connection = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}

// Logout handling
if (isset($_GET['logout'])) {
    setcookie('id', '', time() - 3600, "/");
    header('Location: index.php');
    exit;
}

// Mostrar a tela de Registro
if (isset($_GET['register'])) {
    showRegister();
}

// Chama o registro
if (isset($_POST['registro']) && !empty($_POST['registro'])) {
  if (empty($_POST['password'])) {
    echo "<script>alert('Insira a senha');</script>";
  }
  else {
    $User = $_POST['registro'];
    $Password = sha1($_POST['password']);
    register($connection, $User, $Password);
  }
}

// Authentication request
if (isset($_POST['username']) && !empty($_POST['username'])) {
  $inputUser = $_POST['username'];
  $Password = sha1($_POST['password']);
    authenticate($connection, $inputUser, $Password);
}

// Check if the cookie 'id' exists
$accountId = !empty($_COOKIE['id']) ? $_COOKIE['id'] : '';

// Query to get account information
$stmt = $connection->prepare("SELECT name, balance FROM account WHERE id = :id");
$stmt->execute(['id' => $accountId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// Transfer request
if (isset($_POST['accountId']) && isset($_POST['amount'])) {
    $targetAccount = $_POST['accountId'];
    $transferAmount = $_POST['amount'];
    if (is_numeric($targetAccount) && is_numeric($transferAmount) && $targetAccount != $accountId) {
        transferMoney($connection, $accountId, $targetAccount, $transferAmount);
    } else {
        echo "<script>alert('Invalid values');</script>";
    }
}

// If no account info is found or the account ID is empty, call the login function
if (!$row || $accountId == '') {
    showLogin();
} else {
    $accountName = $row['name'];
    $balance     = $row['balance'];
    $id          = $_COOKIE['id']; 
    showDashboard($connection, $accountId, $accountName, $balance, $id);
}

function transferMoney($connection, $senderId, $receiverId, $amount)
{
    try {
        $connection->beginTransaction();

        // Verificar saldo do remetente
        $stmt = $connection->prepare("SELECT balance FROM account WHERE id = ?");
        $stmt->execute([$senderId]);
        $senderBalance = $stmt->fetchColumn();

        // Verificar se a conta existe
        if ($senderBalance === false) {
            throw new Exception("Conta do remetente não encontrada");
        }

        // Validar saldo suficiente
        if ($senderBalance < $amount) {
            $connection->rollBack();
            echo "<script>alert('Saldo insuficiente');window.location.href = 'index.php';</script>";
            exit();
        }

        // Atualizar contas
        $stmt = $connection->prepare("UPDATE account SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $senderId]);
        
        $stmt = $connection->prepare("UPDATE account SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $receiverId]);

        // Registrar transação
        $stmt = $connection->prepare("INSERT INTO transaction (sender_id, receiver_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$senderId, $receiverId, $amount]);

        $connection->commit();
        echo "<script>alert('Transferência realizada com sucesso!');window.location.href = 'index.php';</script>";
        exit();

    } catch (Exception $e) { // Alterado para capturar todas as exceções
        $connection->rollBack();
        echo "<script>alert('Erro: " . addslashes($e->getMessage()) . "');window.location.href = 'index.php';</script>";
        exit();
    }
}

function listTransactions($connection, $accountId)
{
    try {
        $query = $connection->prepare("SELECT sender_id, receiver_id, amount, created_at FROM transaction WHERE sender_id = :id OR receiver_id = :id ORDER BY transaction_id DESC");
        $query->execute(['id' => $accountId]);

        if ($query->rowCount() > 0) {
            while ($result = $query->fetch(PDO::FETCH_ASSOC)) {
                $amount   = $result['amount'];
                $receiver = $result['receiver_id'];
                $sender   = $result['sender_id'];
                $time     = $result['created_at'];
                if ($sender == $accountId) {
                    echo "<li class='transaction-item'>
                    <div>
                        <h4>Transferência Enviada</h4>
                        <small>$time - Conta: $receiver</small>
                    </div>
                    <span class='amount negative'>- R$ $amount</span>";
                } else {
                    echo "<li class='transaction-item'>
                    <div>
                        <h4>Transferência Enviada</h4>
                        <small>$time - Conta: $sender</small>
                    </div>
                    <span class='amount positive'>+ R$ $amount</span>";
                }
            }
        } else {
            echo '<li>No transactions</li>';
        }
        $query->closeCursor();
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

// Função que realiza a Autenticação
function authenticate($connection, $username, $password)
{
    try {
        $stmt = $connection->prepare("SELECT id FROM account WHERE name = :username AND password = :password");
        $stmt->execute(['username' => $username, 'password' => $password]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($result) {
            $id = $result['id'];
            // Cookie valid for 1 hour
            setcookie('id', $id, time() + 3600, "/");
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "<script>alert('User not found');</script>";
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

// Função para realizar o Registro
function register($connection, $username, $password)
{
  try {
    $reg = $connection->prepare("INSERT INTO account(name, password) VALUES (:username, :password)");
    $reg->execute(['username' => $username, 'password' => $password]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  } catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062){
            echo "<script>alert('Nome em uso');</script>"; 
    } else {
      echo 'Error: ' . $e->getMessage();
    }
  }
}

/**
 * Displays the login page.
 */
function showLogin() {
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Login - PurpleBank</title>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap' rel='stylesheet'>
    <style>
        :root {
            --primary: #8A2BE2;
            --secondary: #4B0082;
            --background: #0a0615;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .background-blur {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            animation: rotate 20s linear infinite;
            filter: blur(80px);
            opacity: 0.1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            padding: 2.5rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 440px;
            margin: 0 1rem;
            position: relative;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo img {
            width: 80px;
            filter: drop-shadow(0 0 20px var(--primary));
        }

        .form-group {
            margin-bottom: 2rem;
        }

        label {
            display: block;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.15);
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(138, 43, 226, 0.3);
        }

        .additional-options {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
        }

        .remember-me input {
            width: auto;
        }

        .forgot-password a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary);
        }

        .security-info {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                border-radius: 16px;
            }
            
            input, button {
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <div class='background-blur'></div>
    
    <div class='login-container'>
        <div class='logo'>
            <img src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNTYgMjU2Ij48cGF0aCBkPSJNMjI0LDEyOGE5Ni45LDk2LjksMCwwLDEtMTYuNzMsNTMuNjZMMTI4LDEyOCw5NS4zMiw2NC42NEE5Niw5NiwwLDEsMSwyMjQsMTI4WiIgZmlsbD0iIzhBMkJFMiIvPjxwYXRoIGQ9Ik0xODguNjcsMTg0YTI0LDI0LDAsMSwxLTI0LTI0QTI0LDI0LDAsMCwxLDE4OC42NywxODRaIiBmaWxsPSIjNEIwMDgyIi8+PC9zdmc+' alt='PurpleBank'>
        </div>

        <form action='index.php' method='post'>
            <div class='form-group'>
                <label for='username'>Nome</label>
                <input type='text' name='username' placeholder='exemplo@purplebank.com'>
            </div>

            <div class='form-group'>
                <label for='password'>Senha</label>
                <input type='password' name='password' placeholder='••••••••'>
            </div>

            <div class='additional-options'>
                <div class='remember-me'>
                    <input type='checkbox' id='remember'>
                    <label for='remember'>Lembrar-me</label>
                </div>
                <div class='forgot-password'>
                    <a href='index.php?register'>Crie uma conta</a>
                </div>
            </div>

            <button type='submit'>ACESSAR CONTA</button>

            <div class='security-info'>
                <p>Segurança SSL 256-bit • Autenticação de dois fatores</p>
            </div>
        </form>
    </div>
</body>
</html>";
    exit;
}

function showRegister() {
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cadastro - PurpleBank</title>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap' rel='stylesheet'>
    <style>
        :root {
            --primary: #8A2BE2;
            --secondary: #4B0082;
            --background: #0a0615;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .background-blur {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            animation: rotate 20s linear infinite;
            filter: blur(80px);
            opacity: 0.1;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            padding: 2.5rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 440px;
            margin: 0 1rem;
            position: relative;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo img {
            width: 80px;
            filter: drop-shadow(0 0 20px var(--primary));
        }

        .form-group {
            margin-bottom: 2rem;
        }

        label {
            display: block;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.15);
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(138, 43, 226, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        .login-link a:hover {
            opacity: 0.8;
        }

        .security-info {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.5rem;
                border-radius: 16px;
            }
            
            input, button {
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <div class='background-blur'></div>
    
    <div class='register-container'>
        <div class='logo'>
            <img src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNTYgMjU2Ij48cGF0aCBkPSJNMjI0LDEyOGE5Ni45LDk2LjksMCwwLDEtMTYuNzMsNTMuNjZMMTI4LDEyOCw5NS4zMiw2NC42NEE5Niw5NiwwLDEsMSwyMjQsMTI4WiIgZmlsbD0iIzhBMkJFMiIvPjxwYXRoIGQ9Ik0xODguNjcsMTg0YTI0LDI0LDAsMSwxLTI0LTI0QTI0LDI0LDAsMCwxLDE4OC42NywxODRaIiBmaWxsPSIjNEIwMDgyIi8+PC9zdmc+' alt='PurpleBank'>
        </div>

        <form action='index.php' method='post'>
            <div class='form-group'>
                <label for='name'>Nome completo</label>
                <input type='text' name='registro' placeholder='Digite seu nome completo'>
            </div>

            <div class='form-group'>
                <label for='password'>Crie sua senha</label>
                <input type='password' name='password' placeholder='••••••••'>
            </div>

            <button type='submit'>CRIAR CONTA</button>

            <div class='login-link'>
                <p>Já tem uma conta? <a href='#'>Faça login</a></p>
            </div>

            <div class='security-info'>
                <p>Seus dados estão protegidos com criptografia de ponta</p>
            </div>
        </form>
    </div>
</body>
</html>";
    exit;
}

/**
 * Displays the account dashboard.
 */
function showDashboard($connection, $accountId, $accountName, $balance, $id) {
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Dashboard - PurpleBank</title>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        :root {
            --primary: #8A2BE2;
            --secondary: #4B0082;
            --background: #160028;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            color: white;
        }

        .user-header {
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }

        .user-info h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .account-info {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .main-content {
            padding: 0 2rem 2rem;
        }

        .balance-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.2);
        }

        .transactions {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 1.5rem;
        }

        .transactions h3 {
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .transaction-list {
            list-style: none;
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin: 0.5rem 0;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
        }

        .transaction-item div {
            flex: 2;
        }

        .amount {
            flex: 1;
            text-align: right;
            font-weight: 500;
        }

        .positive { color: #00ff88; }
        .negative { color: #ff4444; }

        .transaction-panel {
            position: fixed;
            right: -100%;
            top: 0;
            width: 400px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            padding: 2rem;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            transition: right 0.3s ease;
        }

        #transaction-panel:target {
            right: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            margin-top: 0.5rem;
        }

        .btn {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
        }

        .new-transaction-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(138, 43, 226, 0.3);
            transition: transform 0.3s ease;
            text-decoration: none;
            color: white;
        }

        .close-panel {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
        }
            
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }

        .logout-btn {
            background: rgba(138, 43, 226, 0.2);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: var(--primary);
            color: white;
        }

        .logout-btn i {
            font-size: 0.9rem;
        }

        @media (max-width: 480px) {
            .logout-btn span {
                display: none;
            }
            
            .logout-btn {
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class='user-header'>
        <div class='user-info'>
            <h1>$accountName</h1>
            <p class='account-info'>Conta: $id</p>
            <a href='index.php?logout' class='logout-btn'>
            <i class='fas fa-sign-out-alt'></i>
            <span>Sair</span>
            </a>
        </div>
    </div>

    <div class='main-content'>
        <div class='balance-card'>
            <p class='balance-label'>Saldo Disponível</p>
            <h2 class='balance-amount'>R$ $balance</h2>
        </div>

        <div class='transactions'>
            <h3>Últimas Transações</h3>
            <ul class='transaction-list'>";
            listTransactions($connection, $id);
            echo "</ul>
        </div>
    </div>

    <a href='#transaction-panel' class='new-transaction-btn'>
        <i class='fas fa-plus'></i>
    </a>

    <div id='transaction-panel' class='transaction-panel'>
        <a href='#' class='close-panel'>&times;</a>
        <h3>Nova Transação</h3>
        <form action='index.php' method='post'>
            <div class='form-group'>
                <label>Valor</label>
                <input type='number' name='amount' class='form-control' placeholder='R$ 0,00' step='0.01' required>
            </div>

            <div class='form-group'>
                <label>Destinatário</label>
                <input type='text' name='accountId' class='form-control' placeholder='CPF ou Chave Pix' required>
            </div>

            <div class='form-group'>
                <label>Descrição</label>
                <textarea class='form-control' rows='3'></textarea>
            </div>

            <button type='submit' class='btn'>Confirmar Transação</button>
        </form>
    </div>
</body>
</html>";
    exit;
}
?>
