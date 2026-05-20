<?php

namespace Tcc\EmdhaEsign\Tests\Unit;

use Tcc\EmdhaEsign\Services\EmdhaXmlService;

test('buildSdkInputJson creates valid structure', function () {
    $config = [
        'license_path' => '/path/to/license.lics',
        'pfx_path' => '/path/to/cert.pfx',
        'pfx_password' => 'password',
        'pfx_alias' => 'fin',
        'sip_id' => 'UAT-GOV-TCC01',
        'content_estimated' => 40000,
        'sip_is_rka' => true,
        'kyc_id_provider' => 'nid',
        'temp_path' => '/tmp/emdha',
    ];

    $service = new EmdhaXmlService($config);

    $result = $service->buildSdkInputJson(
        docBase64: 'JVBERi0xLjQ=',
        signerInfo: [
            'kycId' => '2515534888',
            'englishName' => 'Ahmed Al-Rashid',
            'email' => 'ahmed@test.com',
            'arabicName' => 'أحمد',
            'mobile' => '556654362',
            'address' => 'Riyadh',
            'regionProvince' => 'Riyadh Province',
            'country' => 'SA',
        ],
        transactionId: 'abc123def456',
        docOptions: [
            'reason' => 'Contract Approval',
            'location' => 'Riyadh',
            'signatureAppearanceType' => 'emdhaLogo',
            'coordinates' => 'bottomRight',
        ]
    );

    expect($result)->toHaveKeys(['eaServiceInit', 'signerInfo', 'docInputs', 'tempFolderPath', 'transactionId', 'userConsent', 'kycIdProvider']);
    expect($result['eaServiceInit']['sipId'])->toBe('UAT-GOV-TCC01');
    expect($result['signerInfo']['kycId'])->toBe('2515534888');
    expect($result['signerInfo']['englishName'])->toBe('Ahmed Al-Rashid');
    expect($result['signerInfo']['rkaName'])->toBe('UAT-GOV-TCC01');
    expect($result['docInputs'])->toHaveCount(1);
    expect($result['docInputs'][0]['docBase64'])->toBe('JVBERi0xLjQ=');
    expect($result['docInputs'][0]['reason'])->toBe('Contract Approval');
    expect($result['docInputs'][0]['signatureAppearanceType'])->toBe('emdhaLogo');
    expect($result['transactionId'])->toBe('abc123def456');
    expect($result['kycIdProvider'])->toBe('nid');
    expect($result['userConsent'])->toBeTrue();
});

test('buildSdkInputJson uses config defaults when docOptions empty', function () {
    $config = [
        'license_path' => '/path/to/license.lics',
        'pfx_path' => '/path/to/cert.pfx',
        'pfx_password' => 'password',
        'pfx_alias' => 'fin',
        'sip_id' => 'UAT-GOV-TCC01',
        'content_estimated' => 40000,
        'sip_is_rka' => false,
        'kyc_id_provider' => 'nid',
        'temp_path' => '/tmp/emdha',
    ];

    $service = new EmdhaXmlService($config);

    $result = $service->buildSdkInputJson(
        docBase64: 'JVBERi0xLjQ=',
        signerInfo: ['kycId' => '123', 'englishName' => 'Test', 'email' => 't@t.com'],
        transactionId: 'tx123',
    );

    expect($result['signerInfo']['rkaName'])->toBe('');
    expect($result['docInputs'][0]['reason'])->toBe('Document Signing');
    expect($result['docInputs'][0]['location'])->toBe('Saudi Arabia');
    expect($result['docInputs'][0]['signatureAppearanceType'])->toBe('emdhaLogo');
});

test('buildMultiDocInputJson creates multiple docInputs', function () {
    $config = [
        'license_path' => '/path/to/license.lics',
        'pfx_path' => '/path/to/cert.pfx',
        'pfx_password' => 'password',
        'pfx_alias' => 'fin',
        'sip_id' => 'UAT-GOV-TCC01',
        'content_estimated' => 40000,
        'sip_is_rka' => true,
        'kyc_id_provider' => 'nid',
        'temp_path' => '/tmp/emdha',
    ];

    $service = new EmdhaXmlService($config);

    $result = $service->buildMultiDocInputJson(
        docsBase64: ['base64doc1', 'base64doc2'],
        signerInfo: ['kycId' => '123', 'englishName' => 'Test', 'email' => 't@t.com'],
        transactionId: 'tx456',
        docOptionsList: [
            ['reason' => 'Sign Doc 1', 'coordinates' => 'bottomRight'],
            ['reason' => 'Sign Doc 2', 'coordinates' => 'bottomLeft', 'signatureAppearanceType' => 'customLogo'],
        ]
    );

    expect($result['docInputs'])->toHaveCount(2);
    expect($result['docInputs'][0]['docBase64'])->toBe('base64doc1');
    expect($result['docInputs'][0]['reason'])->toBe('Sign Doc 1');
    expect($result['docInputs'][1]['docBase64'])->toBe('base64doc2');
    expect($result['docInputs'][1]['reason'])->toBe('Sign Doc 2');
    expect($result['docInputs'][1]['signatureAppearanceType'])->toBe('customLogo');
});

test('buildAppendInputJson creates valid structure', function () {
    $config = [
        'license_path' => '/path/to/license.lics',
        'pfx_path' => '/path/to/cert.pfx',
        'pfx_password' => 'password',
        'pfx_alias' => 'fin',
        'sip_id' => 'UAT-GOV-TCC01',
        'content_estimated' => 40000,
        'sip_is_rka' => true,
        'kyc_id_provider' => 'nid',
        'temp_path' => '/tmp/emdha',
    ];

    $service = new EmdhaXmlService($config);

    $eaServiceInit = [
        'licenseFilePath' => '/path/to/license.lics',
        'pfxFilePath' => '/path/to/cert.pfx',
        'pfxPassword' => 'password',
        'pfxAlias' => 'fin',
        'sipId' => 'UAT-GOV-TCC01',
        'contentEstimated' => 40000,
    ];

    $result = $service->buildAppendInputJson(
        eaServiceInit: $eaServiceInit,
        tempFolderPath: '/tmp/emdha',
        apiResponseXml: '<SignResp status="1"></SignResp>'
    );

    expect($result)->toHaveKeys(['eaServiceInit', 'tempFilePath', 'apiResponseXML']);
    expect($result['eaServiceInit'])->toBe($eaServiceInit);
    expect($result['tempFilePath'])->toBe('/tmp/emdha');
    expect(base64_decode($result['apiResponseXML']))->toBe('<SignResp status="1"></SignResp>');
});