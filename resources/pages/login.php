<?php

//handle user login logics 



$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $userType = $_POST['user_type'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password cannot be empty';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        exit();
    }
    if ($userType == "administrator") {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE emailAddress = :email");
    } elseif ($userType == "lecture") {
        $stmt = $pdo->prepare("SELECT * FROM lecture WHERE emailAddress = :email");
    }
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

  
    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user'] = [
            'id' => $user['Id'],
            'email' => $user['emailAddress'],
            'name' => $user['firstName'],
            'role' => $userType,
        ];

        header('Location: home');
        exit();
    } else {
        $errors['login'] = 'Invalid email or password';
        $_SESSION['errors'] = $errors;
    }
}
if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
}


function display_error($error, $is_main = false)
{
    global $errors;
    if (isset($errors["{$error}"])) {

        echo '<div class="' . ($is_main ? 'error-main' : 'error') . '">
                  <p>' . $errors["{$error}"] . '</p>
           </div>';
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Login to access dashboard </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="resources/assets/css/login_styles.css">
</head>

<div class="bg-grid"></div>

<body>
    <div class="container" id="signIn">
        <div class="brand-mark">
            <div class="logo-icon">AF</div>
            <span class="brand-name">AFRAs</span>
        </div>
        <h1 class="form-title">Welcome back</h1>
        <p class="form-subtitle">Sign in to access your dashboard</p>
        <?php
        display_error('login', true);
        ?>
        <form method="POST" action="">
            <label class="field-label">Role</label>
            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select name="user_type" id="" required>
                    <option value="">Select Role</option>
                    <option value="lecture">Lecturer</option>
                    <option value="administrator">Administrator</option>
                </select>
            </div>
            <label class="field-label">Email Address</label>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="you@institution.edu" required>
                <?php
                display_error('email');
                ?>
            </div>
            <label class="field-label">Password</label>
            <div class="input-group password">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
                <i id="eye" class="fa fa-eye"></i>
                <?php
                display_error('password')
                ?>
            </div>
            <p class="recover">
                <a href="#">Forgot password?</a>
            </p>
            <input type="submit" class="btn" value="Sign In" name="login">
        </form>
    </div>
    <script src="resources/assets/javascript/script.js"></script>
</body>

</html>