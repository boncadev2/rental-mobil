<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=rental_mobil', 'rental_user', 'rental_password');
$stmt = $pdo->query("SELECT value FROM app_settings WHERE key_name = 'hero_image'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
