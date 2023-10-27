<?php

require 'vendor/autoload.php'; // Подключаем библиотеку Guzzle

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

if ($argc < 4) {
    echo "Использование: php tester.php [url] [количество запросов] [запросов в секунду]\n";
    exit(1);
}

$url = $argv[1];
$count = intval($argv[2]);
$rate = floatval($argv[3]);

$client = new Client([
    'verify' => false, // Игнорировать проверку SSL-сертификата
]);

$promises = [];
$delay = 1 / $rate;

echo "Тестирование {$url} с {$count} запросами ({$rate} запросов в секунду)...\n";

$statusCodes = [];

for ($i = 0; $i < $count; $i++) {
    $promises[] = $client->getAsync($url)->then(
        function (Response $response) use (&$statusCodes) {
            $statusCode = $response->getStatusCode();
            $statusCodes[$statusCode] = ($statusCodes[$statusCode] ?? 0) + 1;
        },
        function (RequestException $reason) use (&$statusCodes) {
            $response = $reason->getResponse();
            if ($response instanceof Response) {
                $statusCode = $response->getStatusCode();
            } else {
                $statusCode = 'N/A'; // Используем "N/A" для отсутствующего ответа
            }
            $statusCodes[$statusCode] = ($statusCodes[$statusCode] ?? 0) + 1;
        }
    );

    usleep($delay * 1000000);
}

Promise\Utils::settle($promises)->wait();

$currentTime = date('Y-m-d H:i:s');
echo "\nОтчет по статус-кодам:\n";
echo "--------------------------\n";
echo "Статус-код | Количество\n";
echo "--------------------------\n";
foreach ($statusCodes as $code => $count) {
    echo str_pad($code, 10) . " | " . str_pad($count, 10) . "\n";
}
echo "--------------------------\n";
