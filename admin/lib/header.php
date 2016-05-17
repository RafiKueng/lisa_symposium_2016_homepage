<?php
/**
    Index generator for the admin area
**/

require_once "../lib/app.php";


try {
    // Create (connect to) SQLite database (creates if not exists)
    $db = open_db();
}
catch(PDOException $e) {
    // Print PDOException message
    echo $e->getMessage();
    echo '<br />';
    var_dump($e->getTraceAsString());
}

header('Content-Type: text/html; charset=utf-8');

?>

<html>

<head>
    <title>LISA meeting - administraion area</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="css/style.css">

    <script src='../js/jquery/jquery.min.js'></script>
    <script src='../js/jquery.are-you-sure/jquery.are-you-sure.js'></script>
    <script src='../js/moment/moment.min.js'></script>
    <script src='js/sha1.js'></script>
    <script src='js/user.js'></script>
</head>

<body>


<div id="wrap">
    <header>
        <h1>LISA Symposium</h1>
        <h2>Admin area</h2>
    </header>

    <nav>
<?php
require "menu.php";
?>
    </nav>

    <div id="main">
