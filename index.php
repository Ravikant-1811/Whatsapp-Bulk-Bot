<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();
$apiKey = '52016dd504d3d0b72bb4b4146b65c61f26e9278a4dbd3589cad838e2e3698924';
$url = 'https://www.wasenderapi.com/api/send-message';

// List of numbers
$numbers = [
    '+918347141878',
    '+91096291214',
];

$message = 'Hello, here is your update.';

foreach ($numbers as $number) {
    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'to'   => $number,
                'text' => $message
            ]
        ]);

        echo "Message sent to {$number}\n";

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        echo "Failed for {$number}: " . $e->getMessage() . "\n";

        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody() . "\n";
        }
    }
}
