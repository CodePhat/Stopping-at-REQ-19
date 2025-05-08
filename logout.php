<?php
session_start();

$_SESSION = array();

session_destroy();

foreach ($_COOKIE as $key => $value) {
    if ($key !== 'remember_username') {
        setcookie($key, '', time() - 3600, '/'); // Expire all other cookies
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Logout Successful</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous" />
  
  <!-- jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  
  <script>
    let timeLeft = 10;
    function countdown() {
      if (timeLeft > 0) {
        document.getElementById("timer").innerText = timeLeft;
        timeLeft--;
        setTimeout(countdown, 1000);
      } else {
        window.location.href = "login.php";
      }
    }
    window.onload = countdown;
  </script>
</head>

<body style="background-color: #f8f9fa;">
  <div class="container">
    <div class="row justify-content-center align-items-center" style="height: 100vh;">
      <div class="col-md-6 p-4 border rounded bg-white text-center">
        <h4>Logout Successful</h4>
        <p>Your account has been logged out.</p>
        <p>Click <a href="login.php">here</a> to return to login, or wait <span class="text-danger" id="timer">10</span> seconds to be redirected automatically.</p>
        <button class="btn btn-success px-5" onclick="window.location.href='login.php'">Login Again</button>
      </div>
    </div>
  </div>
</body>
</html>
