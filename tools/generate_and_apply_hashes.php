<?php
// This script will generate password_hash() values for seeded users and print SQL for you to run.
// Usage: php generate_and_apply_hashes.php
$seeds = [
    ['email'=>'admin@example.com','pass'=>'Admin123!'],
    ['email'=>'prof1@example.com','pass'=>'Prof123!'],
    ['email'=>'student1@example.com','pass'=>'Stud123!']
];
foreach ($seeds as $s) {
    $h = password_hash($s['pass'], PASSWORD_DEFAULT);
    echo "UPDATE users SET password_hash = '".addslashes($h)."' WHERE email = '".$s['email']."';\n";
}
echo "\n-- Run the above SQL in your DB to update seeded passwords.\n";
