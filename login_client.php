<?php
session_start(); 
function logEvent($message) {
    $logFile = 'log_client.txt';
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

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
        $client_username = isset($_POST['client_username']) ? filter_var($_POST['client_username']) : '';
        $client_password = isset($_POST['client_password']) ? $_POST['client_password'] : '';

        if (empty($client_username) || empty($client_password)) {
            $error = "Username or Password is invalid";
        } else {
            require 'connection.php';
            $conn = Connect();

            $query = "SELECT client_username, client_password FROM clients WHERE client_username=? LIMIT 1";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $client_username);
            $stmt->execute();
            $stmt->bind_result($db_username, $hashed_password);
            $stmt->store_result();

            if ($stmt->fetch() && password_verify($client_password, $hashed_password)) {
                $_SESSION['login_client'] = $db_username; 

                logEvent("Successful login - User: {$_SESSION['login_client']}, IP: {$_SERVER['REMOTE_ADDR']}");
                header("location: index.php"); 
            } else {
                $error = "Username or Password is invalid";

                $_SESSION['login_attempts']++;
                logEvent("Failed login attempt - User: $client_username, IP: {$_SERVER['REMOTE_ADDR']}");

                if ($_SESSION['login_attempts'] >= 3) {
                    logEvent("Account locked - User: $client_username, IP: {$_SERVER['REMOTE_ADDR']}");
                    $error = "Account is locked due to multiple incorrect login attempts. Please try again later.";
                    $_SESSION['account_locked'] = true;
                    $_SESSION['lock_time'] = time(); 
                }
            }

            mysqli_close($conn); 
        }
    }
}
?>
