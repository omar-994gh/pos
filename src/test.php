<?php
require __DIR__ . '/../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

try {
    $connector = new WindowsPrintConnector("POS-80C");
    $printer = new Printer($connector);
    $printer->text("اختبار طباعة من PHP\n");
    $printer->cut();
    $printer->close();
    echo "تمت الطباعة بنجاح!";
} catch (Exception $e) {
    echo "خطأ بالطباعة: " . $e->getMessage();
}
