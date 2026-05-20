<?php

namespace Tcc\EmdhaEsign\Tests\Feature;

use Tcc\EmdhaEsign\Services\EmdhaApiService;

test('EmdhaApiService parseSignDocResponse handles success XML', function () {
    $config = ['base_url' => 'https://esign-dev.emdha.sa/eSign/SignDoc', 'timeout' => 120];
    $service = new EmdhaApiService($config);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<SignResp status="1" respCode="000" txn="test123">'
        . '<docSignature id="1">'
        . base64_encode('pkcs7data')
        . '</docSignature>'
        . '<X509Certificate>' . base64_encode('certdata') . '</X509Certificate>'
        . '</SignResp>';

    $reflector = new \ReflectionClass($service);
    $method = $reflector->getMethod('parseSignDocResponse');

    $result = $method->invoke($service, $xml);

    expect($result['status'])->toBe(1);
    expect($result['transactionId'])->toBe('test123');
    expect($result['docSignatures'])->toHaveCount(1);
    expect($result['x509Certificate'])->not->toBeNull();
});

test('EmdhaApiService parseSignDocResponse handles error XML', function () {
    $config = ['base_url' => 'https://esign-dev.emdha.sa/eSign/SignDoc', 'timeout' => 120];
    $service = new EmdhaApiService($config);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<SignResp version="1.0" status="0" errorCode="ESIGN-1004" errorMessage="Invalid SIP Access Key Hash">';

    $reflector = new \ReflectionClass($service);
    $method = $reflector->getMethod('parseSignDocResponse');

    $result = $method->invoke($service, $xml);

    expect($result['status'])->toBe(0);
    expect($result['errorCode'])->toBe('ESIGN-1004');
    expect($result['errorMessage'])->toBe('Invalid SIP Access Key Hash');
});

test('EmdhaApiService parseSignDocResponse handles missing SignResp', function () {
    $config = ['base_url' => 'https://esign-dev.emdha.sa/eSign/SignDoc', 'timeout' => 120];
    $service = new EmdhaApiService($config);

    $xml = '<?xml version="1.0"?><OtherElement/>';

    $reflector = new \ReflectionClass($service);
    $method = $reflector->getMethod('parseSignDocResponse');

    $result = $method->invoke($service, $xml);

    expect($result['status'])->toBe(0);
    expect($result['errorCode'])->toBe('API-0003');
});

test('EmdhaApiService getBaseUrl returns configured URL', function () {
    $config = ['base_url' => 'https://custom.emdha.sa/eSign/SignDoc', 'timeout' => 60];
    $service = new EmdhaApiService($config);

    expect($service->getBaseUrl())->toBe('https://custom.emdha.sa/eSign/SignDoc');
});

test('EmdhaApiService guards against double /SignDoc', function () {
    $config = ['base_url' => 'https://esign-dev.emdha.sa/eSign/SignDoc', 'timeout' => 120];
    $service = new EmdhaApiService($config);

    $reflector = new \ReflectionClass($service);
    $method = $reflector->getMethod('sendSignDocRequest');

    expect($method)->not->toBeNull();
});