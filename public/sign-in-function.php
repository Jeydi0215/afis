<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
unset($_SESSION['admin_id']);
include 'db-conn.php';
if (isset($_POST['uid']) && isset($_POST['password'])) {
    
    $uid = $_POST['uid'];
    $password = $_POST['password'];
    if (empty($uid)) {
        header('location: index.php?error=Username or Email is Required!');
        exit();
    } elseif (empty($password)) {
        header('location: index.php?error=Password is Required!');
        exit();
    } else {
        // Modify the query to check both user_email and username
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_email = ? OR user_name = ?");
        $stmt->execute([$uid, $uid]);
        if ($stmt->rowCount() === 1) {
            $users = $stmt->fetch();
            $user_id = $users['user_id'];
            $user_email = $users['user_email'];
            $username = $users['user_name'];
            $user_name = $users['full_name'];
            $user_hashed_password = $users['user_password'];
            $access_level = $users['access_level']; // Fetch access level
            
            // Hash the entered password with SHA-256 for comparison
            $hashed_input_password = hash('sha256', $password);
            
            // Compare the SHA-256 hash or fallback to password_verify for backwards compatibility
            if ($hashed_input_password === $user_hashed_password || password_verify($password, $user_hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $user_email;
                $_SESSION['user_name'] = $username;
                $_SESSION['full_name'] = $user_name;
                $_SESSION['access_level'] = $access_level; // Store access level in session
                // Redirect based on user role
                if ($access_level == 1) {
                    header("Location: afis/Dashboard.php");
                } else if ($access_level == 2){
                    header("Location: afis/Dashboard.php");
                } else if ($access_level == 3){
                    header("Location: afis/Dashboard.php");
                } else if ($access_level == 0) { 
                    header("Location: /pending");
                }
                exit();
            } else {
                header('location: index.php?error=Wrong Credentials!');
                exit();
            }
        } else {
            header('location: index.php?error=Invalid User!');
            exit();
        }
    }
}
?>