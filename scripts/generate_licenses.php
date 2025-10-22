<?php
// scripts/generate_licenses.php
require_once __DIR__ . '/../src/init.php';

for ($i = 0; $i < 20; $i++) {
  $key = bin2hex(random_bytes(16));        // مفتاح عشوائي 32 محرف
  $hash = hash('sha256', $key);            // هاش للتحقق لاحقًا
  $db->prepare(
    "INSERT INTO Licenses (license_key, file_hash) VALUES (:key, :hash)"
  )->execute([':key'=>$key, ':hash'=>$hash]);
  // انشئ ملف .lic يحتوي فقط على المفتاح
  file_put_contents(__DIR__ . "/license_{$key}.lic", $key);
}
echo "Generated 20 licenses\n";
