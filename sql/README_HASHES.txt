The SQL setup inserts placeholder password hashes. After creating the database run the PHP script
`src/tools/generate_and_apply_hashes.php` to create secure password hashes for the seeded accounts.
Alternatively, replace the $2y$... placeholders manually with password_hash() outputs.
