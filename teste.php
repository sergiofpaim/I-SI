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
if (isset($_POST['LogOut'])) {
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

        // Just a simple query to check connection
        $connection->query("SELECT 1")->closeCursor();

        $transaction = $connection->prepare("CALL TransferMoney(:sender, :receiver, :amount);");
        $transaction->execute([
            'sender'   => $senderId,
            'receiver' => $receiverId,
            'amount'   => $amount
        ]);
        $transaction->closeCursor();
        $connection->commit();
        echo "<script>alert('Transaction completed!');window.location.href = 'index.php';</script>";
        exit();
    } catch (PDOException $e) {
        $connection->rollBack();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');window.location.href = 'index.php';</script>";
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
                if ($sender == $accountId) {
                    echo "<li>Transaction to account $receiver: $amount</li>";
                } else {
                    echo "<li>Transaction from account $sender: $amount</li>";
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
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Login Page</title>
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
            <input type='text' name='username' placeholder='Enter your name' required>
            <input type='password' name='password' placeholder='Enter your password' required>
            <button type='login'>Sign In</button>
        </form>
        <div class='register-link'>
            <p>Don't have an account? <a href='index.php?register'>Register here</a>.</p>
        </div>
    </div>
</body>
</html>";
    exit;
}

function showRegister() {
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Login Page</title>
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
        <h2>Register</h2>
        <form action='index.php' method='post'>
            <input type='text' name='registro' placeholder='Enter your name' required>
            <input type='password' name='password' placeholder='Enter your password' required>
            <button type='login'>Registre-see</button>
        </form>
        <div class='register-link'>
            <p>Ja tem uma conta? <a href='index.php'>Logue aqui!</a>.</p>
        </div>
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
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Bank Dashboard</title>
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
        <h1>Digital Bank</h1>
        <div class='account-info'>
            <p><span>Account Name:</span> $accountName</p>
            <p><span>Balance:</span> $ $balance</p>
            <p><span>Id:</span> $id</p>
        </div>
    </div>
    <div class='content'>
        <h2>Welcome to your Dashboard</h2>
        <p>Manage your finances, view recent transactions and more.</p>
        <details>
            <summary>Make a Transfer</summary>
            <div class='transfer-form'>
                <h3>Make a Transfer</h3>
                <form action='index.php' method='post'>
                    <label for='accountId'>Target Account ID:</label>
                    <input type='text' id='accountId' name='accountId' required>
                    <label for='amount'>Amount:</label>
                    <input type='number' id='amount' name='amount' step='0.01' required>
                    <button type='submit'>Transfer</button>
                </form>
            </div>
        </details>
        <div>
            <h2>Recent Transactions</h2>
            <ul>";
                listTransactions($connection, $accountId);
    echo "      </ul>
        </div>
        <form class='logout-form' action='index.php' method='post'>
            <input type='hidden' name='LogOut' value='LogOut'>
            <button type='submit'>Logout</button>
        </form>
    </div>
</body>
</html>";
    exit;
}
?>
