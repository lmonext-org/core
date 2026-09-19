<?php
// PHP-Weiterleitung
header("Location: home.php");

// Falls der Header fehlschlägt, greift HTML/JavaScript
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0;url=home.php">
    <title>Weiterleitung...</title>
</head>
<body>
    <script>
        window.location.href = "home.php";
    </script>
    <p>Falls Sie nicht automatisch weitergeleitet werden, klicken Sie bitte <a href="home.php">hier</a>.</p>
</body>
</html>
