<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$merchant_id = trim(config('services.payhere.merchant_id'));
$merchant_secret = trim(config('services.payhere.merchant_secret'));
$order_id = 'AD-24-1776591494';
$amount = '2000.00';
$currency = 'LKR';

echo "\n--- HASH GENERATION VALUES ---\n";
echo "Merchant ID: [{$merchant_id}]\n";
echo "Order ID: [{$order_id}]\n";
echo "Amount: [{$amount}]\n";
echo "Currency: [{$currency}]\n";
echo "Secret: [{$merchant_secret}]\n";

$secret_hash = strtoupper(md5($merchant_secret));
echo "MD5(Secret) Upper: [{$secret_hash}]\n";

$str_to_hash = $merchant_id . $order_id . $amount . $currency . $secret_hash;
echo "Full String to Hash: [{$str_to_hash}]\n";

$final_hash = strtoupper(md5($str_to_hash));
echo "Final Hash: [{$final_hash}]\n";
