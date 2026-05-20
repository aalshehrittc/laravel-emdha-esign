<?php

namespace Tcc\EmdhaEsign\Tests\Feature;

test('can resolve emdha service from container', function () {
    $service = $this->app->make(\Tcc\EmdhaEsign\Services\EmdhaSdkService::class);
    expect($service)->toBeInstanceOf(\Tcc\EmdhaEsign\Services\EmdhaSdkService::class);
});

test('can access emdha facade', function () {
    expect(class_exists(\Tcc\EmdhaEsign\Facades\EmdhaFacade::class))->toBeTrue();
});