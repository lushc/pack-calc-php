<?php

require __DIR__ . '/vendor/autoload.php';

return function ($event) {
    $request = json_decode($event['body'], true);
    $response = [
        'statusCode' => 200,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];

    try {
        $data = (new PackCalc($request['quantity'], $request['packSizes']))->calculate();
    } catch (InvalidArgumentException $e) {
        $data = ['error' => $e->getMessage()];
        $response['statusCode'] = 400;
    }

    $response['body'] = json_encode($data);

    return $response;
};
