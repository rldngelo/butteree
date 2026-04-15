<?php
$conn = mysqli_connect("localhost", "root", "PASSWORDPASS", "ROLDAN_PSE");
if (!$conn)
    die("Connection failed: " . mysqli_connect_error());