<?php

namespace Tcc\EmdhaEsign\Services;

class EmdhaErrorCode
{
    private const ERROR_MAP = [
        'ESIGN-1001' => 'Invalid Request Format',
        'ESIGN-1003' => 'Invalid Version',
        'ESIGN-1004' => 'Invalid SIP Access Key Hash — verify your PFX certificate, password, alias, and license file match the SIP ID registered with EMDHA CA.',
        'ESIGN-1005' => 'Invalid Transaction ID',
        'ESIGN-1006' => 'Invalid SIP ID',
        'ESIGN-1008' => 'Request exceeds maximum number of documents allowed',
        'ESIGN-1009' => 'Invalid Timestamp',
        'ESIGN-1011' => 'Invalid KYC XML data — check that kycId, englishName, and other signer fields are correct.',
        'ESIGN-1012' => 'Duplicate Transaction ID',
        'ESIGN-1014' => 'Invalid character in the name',
        'ESIGN-1999' => 'Unknown error / Custom error from OSP',
        'ESIGN-2001' => 'Invalid Document Hash',
        'ESIGN-2002' => 'Invalid response signature type',
        'ESIGN-2003' => 'Invalid hash algorithm',
        'ESIGN-2013' => 'Invalid Signature Algorithm',
        'ESIGN-2036' => 'XML Signature validation failed',
        'ESIGN-2038' => 'Insufficient counter at SIP',
        'ESIGN-2047' => 'CN Length Validation Failed',
        'SDK-0001' => 'EMDHA SDK JAR file not found',
        'SDK-0002' => 'SDK process execution failed',
        'SDK-0003' => 'SDK output file was not created',
        'SDK-0004' => 'Invalid JSON response from SDK',
        'SDK-0005' => 'SDK process exception',
        'SDK-GEN-FAIL' => 'Failed to generate request XML',
        'SDK-APPEND-FAIL' => 'Failed to append signature response',
        'API-0001' => 'HTTP error from EMDHA API',
        'API-0002' => 'EMDHA API request failed',
        'API-0003' => 'Invalid response: SignResp element not found',
        'API-0004' => 'Failed to parse XML response',
        'API-FAIL' => 'EMDHA API request failed',
        'SYS-0001' => 'System error during signing',
        'VAL-0001' => 'KYC ID (National ID) is required for signing',
        'VAL-0002' => 'English Name is required for signing',
    ];

    public static function getMessage(string $code): string
    {
        return self::ERROR_MAP[$code] ?? 'Unknown error';
    }

    public static function getAllErrors(): array
    {
        return self::ERROR_MAP;
    }
}