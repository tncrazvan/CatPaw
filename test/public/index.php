<?php
namespace app\www;
use com\github\tncrazvan\catpaw\tools\scripts\Script;

list($username) = Script::args();

$session = &Script::startSession();

$session["username"] = $username;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    my username is: <?php echo $username ?>
</body>
</html>