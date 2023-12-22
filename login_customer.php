<?php
session_start(); 


$error = ''; 

$session_timeout = 10 * 60; 

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0; 
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    session_unset();
    session_destroy();
    header("location: customerlogin.php");
}

$_SESSION['last_activity'] = time(); 
if (isset($_POST['submit'])) {
    if (isset($_SESSION['account_locked']) && $_SESSION['account_locked']) {
        $lockTime = isset($_SESSION['lock_time']) ? $_SESSION['lock_time'] : 0;
        $elapsedTime = time() - $lockTime;

        if ($elapsedTime >= 600) { 
            $_SESSION['account_locked'] = false; 
            $_SESSION['login_attempts'] = 0; 
        } else {
            $error = "Account is locked. Please try again later.";
        }
    } else {
        $customer_username = isset($_POST['customer_username']) ? filter_var($_POST['customer_username']) : '';
        $customer_password = isset($_POST['customer_password']) ? $_POST['customer_password'] : '';

        if (empty($customer_username) || empty($customer_password)) {
            $error = "Username or Password is invalid";
        } else {
            require 'connection.php';
            $conn = Connect();

            $query = "SELECT customer_username, customer_password FROM customers WHERE customer_username=? LIMIT 1";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $customer_username);
            $stmt->execute();
            $stmt->bind_result($db_username, $hashed_password);
            $stmt->store_result();

            if ($stmt->fetch() && password_verify($customer_password, $hashed_password)) {
                $_SESSION['login_customer'] = $db_username; 

                logLogin("Successful login: $db_username");

                header("location: index.php"); 
            } else {
                $error = "Username or Password is invalid";

                $_SESSION['login_attempts']++;

                logLogin("Failed login attempt - User: $customer_username, IP: {$_SERVER['REMOTE_ADDR']}");


                if ($_SESSION['login_attempts'] >= 3) {

                    $error = "Account is locked due to multiple incorrect login attempts. Please try again later.";
                    $_SESSION['account_locked'] = true;
                    $_SESSION['lock_time'] = time(); 
                logLogin("Account locked for user: $customer_username due to multiple incorrect login attempts.");
                }
            }

            mysqli_close($conn); 
        }
    }
}

function logLogin($message) {
    $logFile = '/Applications/XAMPP/xamppfiles/htdocs/Car_Rentals/log_customer.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

?>