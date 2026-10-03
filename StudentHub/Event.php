<?php
    $dbLocalhost = mysqli_connect("localhost","root","")
    or die("Could not connect:".mysqli_connect_error());
    mysqli_select_db($dbLocalhost,"event")
    or die("could not find database:".mysqli_connect_error());
    echo"<h1>Connected to database</h1>";
?>