<?php
    $dbLocalhost = mysql_connect("localhost","root","")
    or die("Could not connect:".mysql_error());
    mysqli_select_db("glassesrus",$dbLocalhost)
    or die("could not find database:".mysql_error());
    echo"<h1>Connected to database</h1>";
?>