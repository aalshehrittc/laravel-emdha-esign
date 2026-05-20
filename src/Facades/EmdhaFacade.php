<?php

namespace Tcc\EmdhaEsign\Facades;

use Illuminate\Support\Facades\Facade;
use Tcc\EmdhaEsign\DTO\DocOptions;
use Tcc\EmdhaEsign\DTO\EmdhaSigningResult;
use Tcc\EmdhaEsign\DTO\SignerInfo;
use Tcc\EmdhaEsign\Services\EmdhaSigningService;

/**
 * @method static EmdhaSigningResult signDocument(string $docBase64, SignerInfo $signerInfo, ?DocOptions $docOptions = null, ?string $transactionId = null)
 * @method static EmdhaSigningResult signMultipleDocuments(array $docsBase64, SignerInfo $signerInfo, array $docOptionsList = [], ?string $transactionId = null)
 * @method static string generateTransactionId()
 */
class EmdhaFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmdhaSigningService::class;
    }
}