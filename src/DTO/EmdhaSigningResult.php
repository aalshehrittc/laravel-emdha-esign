<?php

namespace Tcc\EmdhaEsign\DTO;

class EmdhaSigningResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $signedPdfBase64 = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $requestXml = null,
        public readonly ?string $responseXml = null,
    ) {}

    public function getSignedPdf(): ?string
    {
        return $this->signedPdfBase64 !== null
            ? base64_decode($this->signedPdfBase64)
            : null;
    }

    public function getErrorDescription(): string
    {
        if ($this->errorCode === null) {
            return '';
        }

        return \Tcc\EmdhaEsign\Services\EmdhaErrorCode::getMessage($this->errorCode);
    }
}