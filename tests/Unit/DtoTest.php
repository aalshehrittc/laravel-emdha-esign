<?php

namespace Tcc\EmdhaEsign\Tests\Unit;

use Tcc\EmdhaEsign\DTO\DocOptions;
use Tcc\EmdhaEsign\DTO\EmdhaSigningResult;
use Tcc\EmdhaEsign\DTO\SignerInfo;
use Tcc\EmdhaEsign\Services\EmdhaErrorCode;

test('SignerInfo can be created with required fields', function () {
    $info = new SignerInfo(kycId: '2515534888', englishName: 'Ahmed', email: 'ahmed@test.com');

    expect($info->kycId)->toBe('2515534888');
    expect($info->englishName)->toBe('Ahmed');
    expect($info->email)->toBe('ahmed@test.com');
    expect($info->country)->toBe('SA');
});

test('SignerInfo toArray returns all fields', function () {
    $info = new SignerInfo(
        kycId: '123',
        englishName: 'Test',
        email: 'test@test.com',
        arabicName: 'عربي',
        mobile: '555123456',
        address: 'Riyadh',
        regionProvince: 'Riyadh Province',
    );

    $arr = $info->toArray();

    expect($arr)->toHaveKeys(['kycId', 'englishName', 'email', 'arabicName', 'mobile', 'address', 'regionProvince', 'country']);
    expect($arr['kycId'])->toBe('123');
    expect($arr['arabicName'])->toBe('عربي');
});

test('SignerInfo fromArray works with camelCase keys', function () {
    $info = SignerInfo::fromArray([
        'kycId' => '456',
        'englishName' => 'Sara',
        'email' => 'sara@test.com',
    ]);

    expect($info->kycId)->toBe('456');
    expect($info->englishName)->toBe('Sara');
});

test('SignerInfo fromArray works with snake_case keys', function () {
    $info = SignerInfo::fromArray([
        'national_id' => '789',
        'english_name' => 'Omar',
        'email' => 'omar@test.com',
    ]);

    expect($info->kycId)->toBe('789');
    expect($info->englishName)->toBe('Omar');
});

test('DocOptions has sensible defaults', function () {
    $opts = new DocOptions();

    expect($opts->reason)->toBe('Document Signing');
    expect($opts->location)->toBe('Saudi Arabia');
    expect($opts->coordinates)->toBe('bottomRight');
    expect($opts->signatureAppearanceType)->toBe('emdhaLogo');
    expect($opts->coSign)->toBeTrue();
    expect($opts->fontSize)->toBe(7);
});

test('DocOptions toArray returns all fields', function () {
    $opts = new DocOptions(
        reason: 'Test',
        location: 'Riyadh',
        signatureAppearanceType: 'customLogo',
        appearanceBackgroundImage: 'base64imagedata',
        signedBy: '',
    );

    $arr = $opts->toArray();

    expect($arr['reason'])->toBe('Test');
    expect($arr['signatureAppearanceType'])->toBe('customLogo');
    expect($arr['appearanceBackgroundImage'])->toBe('base64imagedata');
    expect($arr['signedBy'])->toBe('');
});

test('DocOptions fromArray accepts snake_case keys', function () {
    $opts = DocOptions::fromArray([
        'appearance_type' => 'noImage',
        'page_to_be_signed' => 'Last',
        'signed_by' => '',
    ]);

    expect($opts->signatureAppearanceType)->toBe('noImage');
    expect($opts->pageTobeSigned)->toBe('Last');
    expect($opts->signedBy)->toBe('');
});

test('DocOptions signedBy defaults to null for englishName fallback', function () {
    $opts = new DocOptions();

    expect($opts->signedBy)->toBeNull()
        ->and($opts->toArray()['signedBy'])->toBeNull();
});

test('EmdhaSigningResult success has signedPdfBase64', function () {
    $result = new EmdhaSigningResult(
        success: true,
        signedPdfBase64: base64_encode('signed-pdf-content'),
        transactionId: 'abc123',
    );

    expect($result->success)->toBeTrue();
    expect($result->signedPdfBase64)->not->toBeNull();
    expect($result->getSignedPdf())->toBe('signed-pdf-content');
    expect($result->errorCode)->toBeNull();
});

test('EmdhaSigningResult failure has error details', function () {
    $result = new EmdhaSigningResult(
        success: false,
        errorCode: 'ESIGN-1004',
        errorMessage: 'Invalid SIP Access Key Hash',
        transactionId: 'abc123',
    );

    expect($result->success)->toBeFalse();
    expect($result->signedPdfBase64)->toBeNull();
    expect($result->errorCode)->toBe('ESIGN-1004');
    expect($result->getErrorDescription())->toBe(EmdhaErrorCode::getMessage('ESIGN-1004'));
});

test('EmdhaErrorCode returns known messages', function () {
    expect(EmdhaErrorCode::getMessage('ESIGN-1004'))->toContain('Invalid SIP Access Key Hash');
    expect(EmdhaErrorCode::getMessage('VAL-0001'))->toContain('KYC ID');
    expect(EmdhaErrorCode::getMessage('SDK-0001'))->toContain('JAR file not found');
    expect(EmdhaErrorCode::getMessage('NONEXISTENT'))->toBe('Unknown error');
});

test('EmdhaErrorCode getAllErrors returns full map', function () {
    $errors = EmdhaErrorCode::getAllErrors();

    expect($errors)->toHaveKey('ESIGN-1004');
    expect($errors)->toHaveKey('SDK-0001');
    expect($errors)->toHaveKey('API-0001');
    expect($errors)->toHaveKey('VAL-0001');
    expect(count($errors))->toBeGreaterThanOrEqual(25);
});