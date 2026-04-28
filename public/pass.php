<?php

$password = "cliente123";

$options = [
    'cost' => 12
];

$hash = password_hash($password, PASSWORD_BCRYPT, $options);

echo $hash;

?>