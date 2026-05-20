<?php

namespace Tcc\EmdhaEsign\Tests\Feature;

use Tcc\EmdhaEsign\Services\EmdhaSigningService;
use Tcc\EmdhaEsign\DTO\SignerInfo;
use Tcc\EmdhaEsign\Services\EmdhaApiService;
use Tcc\EmdhaEsign\Services\EmdhaSdkService;
use Tcc\EmdhaEsign\Services\EmdhaXmlService;
use Mockery;

test('signDocument returns VAL-0001 when kycId is empty', function () {
    $sdkService = Mockery::mock(EmdhaSdkService::class);
    $apiService = Mockery::mock(EmdhaApiService::class);
    $xmlService = Mockery::mock(EmdhaXmlService::class);

    $service = new EmdhaSigningService($sdkService, $apiService, $xmlService);

    $signer = new SignerInfo(kycId: '', englishName: 'Test', email: 'test@test.com');
    $result = $service->signDocument('base64pdf', $signer);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('VAL-0001');
});

test('signDocument returns VAL-0002 when englishName is empty', function () {
    $sdkService = Mockery::mock(EmdhaSdkService::class);
    $apiService = Mockery::mock(EmdhaApiService::class);
    $xmlService = Mockery::mock(EmdhaXmlService::class);

    $service = new EmdhaSigningService($sdkService, $apiService, $xmlService);

    $signer = new SignerInfo(kycId: '123', englishName: '', email: 'test@test.com');
    $result = $service->signDocument('base64pdf', $signer);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('VAL-0002');
});

test('generateTransactionId produces 26-char hex string', function () {
    $sdkService = Mockery::mock(EmdhaSdkService::class);
    $apiService = Mockery::mock(EmdhaApiService::class);
    $xmlService = Mockery::mock(EmdhaXmlService::class);

    $service = new EmdhaSigningService($sdkService, $apiService, $xmlService);

    $txId = $service->generateTransactionId();

    expect(strlen($txId))->toBe(26);
    expect(preg_match('/^[a-f0-9]{26}$/', $txId))->toBe(1);
});

test('generateTransactionId produces unique IDs', function () {
    $sdkService = Mockery::mock(EmdhaSdkService::class);
    $apiService = Mockery::mock(EmdhaApiService::class);
    $xmlService = Mockery::mock(EmdhaXmlService::class);

    $service = new EmdhaSigningService($sdkService, $apiService, $xmlService);

    $ids = collect(range(1, 50))->map(fn() => $service->generateTransactionId());

    expect($ids->unique()->count())->toBe(50);
});