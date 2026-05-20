<?php

namespace Tcc\EmdhaEsign\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Tcc\EmdhaEsign\EmdhaServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EmdhaServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Emdha' => \Tcc\EmdhaEsign\Facades\EmdhaFacade::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('emdha', array_merge($app['config']->get('emdha', []), [
            'base_url' => 'https://esign-dev.emdha.sa/eSign/SignDoc',
            'sip_id' => 'UAT-GOV-TCC01',
            'license_path' => storage_path('emdha/sdk/test.lics'),
            'pfx_path' => storage_path('emdha/sdk/test.pfx'),
            'pfx_password' => 'testpassword',
            'pfx_alias' => 'test',
            'sdk_jar_path' => storage_path('emdha/sdk/emdhaCLI.jar'),
            'sdk_classpath' => '',
            'environment' => 'sandbox',
            'content_estimated' => 40000,
            'sign_algorithm' => 'ECC',
            'hash_algorithm' => 'SHA256',
            'response_sig_type' => 'PKCS7',
            'kyc_id_provider' => 'nid',
            'sip_is_rka' => true,
            'user_consent' => true,
            'timeout' => 120,
            'temp_path' => storage_path('app/emdha/temp'),
        ]));
    }

    protected function getBasePath(): string
    {
        return sys_get_temp_dir() . '/laravel-emdha-esign-testing';
    }
}