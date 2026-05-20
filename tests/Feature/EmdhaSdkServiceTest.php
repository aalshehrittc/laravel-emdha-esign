<?php

namespace Tcc\EmdhaEsign\Tests\Feature;

use Tcc\EmdhaEsign\Services\EmdhaSdkService;

test('EmdhaSdkService returns SDK-0001 when JAR not found', function () {
    $config = [
        'sdk_jar_path' => '/nonexistent/path/emdhaCLI.jar',
        'sdk_classpath' => '',
        'timeout' => 120,
        'temp_path' => sys_get_temp_dir() . '/emdha_test',
        'license_path' => '',
        'pfx_path' => '',
        'pfx_password' => '',
        'pfx_alias' => '',
        'sip_id' => '',
        'content_estimated' => 40000,
    ];

    $service = new EmdhaSdkService($config);

    expect($service->jarExists())->toBeFalse();

    $result = $service->generateSignDocRequestXML([]);

    expect($result['status'])->toBe(0);
    expect($result['errorCode'])->toBe('SDK-0001');
    expect($result['errorMessage'])->toContain('JAR file not found');
});

test('EmdhaSdkService buildEaServiceInit returns config values', function () {
    $config = [
        'sdk_jar_path' => '/path/to/jar',
        'sdk_classpath' => '',
        'timeout' => 60,
        'temp_path' => '/tmp/emdha',
        'license_path' => '/path/to/license.lics',
        'pfx_path' => '/path/to/cert.pfx',
        'pfx_password' => 'secret',
        'pfx_alias' => 'fin',
        'sip_id' => 'UAT-GOV-TCC01',
        'content_estimated' => 40000,
    ];

    $service = new EmdhaSdkService($config);

    $eaInit = $service->buildEaServiceInit();

    expect($eaInit['licenseFilePath'])->toBe('/path/to/license.lics');
    expect($eaInit['pfxFilePath'])->toBe('/path/to/cert.pfx');
    expect($eaInit['pfxPassword'])->toBe('secret');
    expect($eaInit['pfxAlias'])->toBe('fin');
    expect($eaInit['sipId'])->toBe('UAT-GOV-TCC01');
    expect($eaInit['contentEstimated'])->toBe(40000);
});

test('EmdhaSdkService getJarPath returns configured path', function () {
    $config = [
        'sdk_jar_path' => '/custom/path/emdhaCLI.jar',
        'sdk_classpath' => '',
        'timeout' => 120,
        'temp_path' => '/tmp/emdha',
        'license_path' => '',
        'pfx_path' => '',
        'pfx_password' => '',
        'pfx_alias' => '',
        'sip_id' => '',
        'content_estimated' => 40000,
    ];

    $service = new EmdhaSdkService($config);

    expect($service->getJarPath())->toBe('/custom/path/emdhaCLI.jar');
});

test('EmdhaSdkService getClasspath returns configured classpath', function () {
    $config = [
        'sdk_jar_path' => '/path/to/jar',
        'sdk_classpath' => '/path/to/emdhaCLI.jar:/path/to/eaCore.jar',
        'timeout' => 120,
        'temp_path' => '/tmp/emdha',
        'license_path' => '',
        'pfx_path' => '',
        'pfx_password' => '',
        'pfx_alias' => '',
        'sip_id' => '',
        'content_estimated' => 40000,
    ];

    $service = new EmdhaSdkService($config);

    expect($service->getClasspath())->toBe('/path/to/emdhaCLI.jar:/path/to/eaCore.jar');
});