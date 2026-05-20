<?php

namespace Tcc\EmdhaEsign\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tcc\EmdhaEsign\DTO\DocOptions;
use Tcc\EmdhaEsign\DTO\EmdhaSigningResult;
use Tcc\EmdhaEsign\DTO\SignerInfo;

class EmdhaSigningService
{
    public function __construct(
        private EmdhaSdkService $sdkService,
        private EmdhaApiService $apiService,
        private EmdhaXmlService $xmlService,
    ) {}

    public function signDocument(
        string $docBase64,
        SignerInfo $signerInfo,
        ?DocOptions $docOptions = null,
        ?string $transactionId = null,
    ): EmdhaSigningResult {
        $docOptions = $docOptions ?? new DocOptions();
        $transactionId = $transactionId ?? $this->generateTransactionId();

        if (empty($signerInfo->kycId)) {
            return new EmdhaSigningResult(
                success: false,
                errorCode: 'VAL-0001',
                errorMessage: 'KYC ID (National ID) is required for signing',
                transactionId: $transactionId,
            );
        }

        if (empty($signerInfo->englishName)) {
            return new EmdhaSigningResult(
                success: false,
                errorCode: 'VAL-0002',
                errorMessage: 'English Name is required for signing',
                transactionId: $transactionId,
            );
        }

        try {
            $inputJson = $this->xmlService->buildSdkInputJson(
                $docBase64,
                $signerInfo->toArray(),
                $transactionId,
                $docOptions->toArray()
            );

            $generateResult = $this->sdkService->generateSignDocRequestXML($inputJson);

            if (($generateResult['status'] ?? 0) !== 1) {
                Log::error('EmdhaSigningService: GenerateSignDocRequestXML failed', [
                    'transaction_id' => $transactionId,
                    'error' => $generateResult['errorMessage'] ?? 'Unknown error',
                ]);

                return new EmdhaSigningResult(
                    success: false,
                    errorCode: $generateResult['errorCode'] ?? 'SDK-GEN-FAIL',
                    errorMessage: $generateResult['errorMessage'] ?? 'Failed to generate request XML',
                    transactionId: $transactionId,
                );
            }

            $requestXmlBase64 = $generateResult['requestXML'];
            $requestXml = base64_decode($requestXmlBase64);

            $apiResult = $this->apiService->sendSignDocRequest($requestXmlBase64);

            if (($apiResult['status'] ?? 0) !== 1) {
                Log::error('EmdhaSigningService: API SignDoc failed', [
                    'transaction_id' => $transactionId,
                    'error' => EmdhaErrorCode::getMessage($apiResult['errorCode'] ?? ''),
                ]);

                return new EmdhaSigningResult(
                    success: false,
                    errorCode: $apiResult['errorCode'] ?? 'API-FAIL',
                    errorMessage: $apiResult['errorMessage'] ?? 'API request failed',
                    transactionId: $transactionId,
                    requestXml: $requestXml,
                    responseXml: $apiResult['rawResponse'] ?? null,
                );
            }

            $appendInput = $this->xmlService->buildAppendInputJson(
                $inputJson['eaServiceInit'],
                $inputJson['tempFolderPath'],
                $apiResult['rawResponse']
            );

            $appendResult = $this->sdkService->appendSignDocResponse($appendInput);

            if (($appendResult['status'] ?? 0) !== 1) {
                Log::error('EmdhaSigningService: AppendSignDocResponse failed', [
                    'transaction_id' => $transactionId,
                    'error' => $appendResult['errorMessage'] ?? 'Unknown error',
                ]);

                return new EmdhaSigningResult(
                    success: false,
                    errorCode: $appendResult['errorCode'] ?? 'SDK-APPEND-FAIL',
                    errorMessage: $appendResult['errorMessage'] ?? 'Failed to append signature',
                    transactionId: $transactionId,
                    requestXml: $requestXml,
                    responseXml: $apiResult['rawResponse'] ?? null,
                );
            }

            $signedDocs = $appendResult['docs'] ?? [];
            $signedDocBase64 = $signedDocs[0]['signedDocument'] ?? null;

            if ($signedDocBase64) {
                Log::info('EmdhaSigningService: Document signed successfully', [
                    'transaction_id' => $transactionId,
                ]);

                return new EmdhaSigningResult(
                    success: true,
                    signedPdfBase64: $signedDocBase64,
                    transactionId: $transactionId,
                    requestXml: $requestXml,
                    responseXml: $apiResult['rawResponse'] ?? null,
                );
            }

            return new EmdhaSigningResult(
                success: false,
                errorCode: 'SDK-APPEND-FAIL',
                errorMessage: 'No signed document returned from SDK',
                transactionId: $transactionId,
                requestXml: $requestXml,
                responseXml: $apiResult['rawResponse'] ?? null,
            );

        } catch (\Exception $e) {
            Log::error('EmdhaSigningService: Exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return new EmdhaSigningResult(
                success: false,
                errorCode: 'SYS-0001',
                errorMessage: $e->getMessage(),
                transactionId: $transactionId,
            );
        }
    }

    public function signMultipleDocuments(
        array $docsBase64,
        SignerInfo $signerInfo,
        array $docOptionsList = [],
        ?string $transactionId = null,
    ): EmdhaSigningResult {
        $transactionId = $transactionId ?? $this->generateTransactionId();

        if (empty($signerInfo->kycId)) {
            return new EmdhaSigningResult(
                success: false,
                errorCode: 'VAL-0001',
                errorMessage: 'KYC ID (National ID) is required for signing',
                transactionId: $transactionId,
            );
        }

        if (empty($signerInfo->englishName)) {
            return new EmdhaSigningResult(
                success: false,
                errorCode: 'VAL-0002',
                errorMessage: 'English Name is required for signing',
                transactionId: $transactionId,
            );
        }

        try {
            $inputJson = $this->xmlService->buildMultiDocInputJson(
                $docsBase64,
                $signerInfo->toArray(),
                $transactionId,
                $docOptionsList
            );

            $generateResult = $this->sdkService->generateSignDocRequestXML($inputJson);

            if (($generateResult['status'] ?? 0) !== 1) {
                return new EmdhaSigningResult(
                    success: false,
                    errorCode: $generateResult['errorCode'] ?? 'SDK-GEN-FAIL',
                    errorMessage: $generateResult['errorMessage'] ?? 'Failed to generate request XML',
                    transactionId: $transactionId,
                );
            }

            $requestXmlBase64 = $generateResult['requestXML'];
            $requestXml = base64_decode($requestXmlBase64);

            $apiResult = $this->apiService->sendSignDocRequest($requestXmlBase64);

            if (($apiResult['status'] ?? 0) !== 1) {
                return new EmdhaSigningResult(
                    success: false,
                    errorCode: $apiResult['errorCode'] ?? 'API-FAIL',
                    errorMessage: $apiResult['errorMessage'] ?? 'API request failed',
                    transactionId: $transactionId,
                    requestXml: $requestXml,
                    responseXml: $apiResult['rawResponse'] ?? null,
                );
            }

            $appendInput = $this->xmlService->buildAppendInputJson(
                $inputJson['eaServiceInit'],
                $inputJson['tempFolderPath'],
                $apiResult['rawResponse']
            );

            $appendResult = $this->sdkService->appendSignDocResponse($appendInput);

            if (($appendResult['status'] ?? 0) !== 1) {
                return new EmdhaSigningResult(
                    success: false,
                    errorCode: $appendResult['errorCode'] ?? 'SDK-APPEND-FAIL',
                    errorMessage: $appendResult['errorMessage'] ?? 'Failed to append signature',
                    transactionId: $transactionId,
                    requestXml: $requestXml,
                    responseXml: $apiResult['rawResponse'] ?? null,
                );
            }

            $signedDocs = $appendResult['docs'] ?? [];

            if (!empty($signedDocs)) {
                $firstSignedBase64 = $signedDocs[0]['signedDocument'] ?? null;

                Log::info('EmdhaSigningService: Multiple documents signed successfully', [
                    'transaction_id' => $transactionId,
                    'doc_count' => count($signedDocs),
                ]);

                return new EmdhaSigningResult(
                    success: true,
                    signedPdfBase64: $firstSignedBase64,
                    transactionId: $transactionId,
                    requestXml: $requestXml,
                    responseXml: $apiResult['rawResponse'] ?? null,
                );
            }

            return new EmdhaSigningResult(
                success: false,
                errorCode: 'SDK-APPEND-FAIL',
                errorMessage: 'No signed documents returned from SDK',
                transactionId: $transactionId,
                requestXml: $requestXml,
                responseXml: $apiResult['rawResponse'] ?? null,
            );

        } catch (\Exception $e) {
            Log::error('EmdhaSigningService: Exception during multi-doc signing', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return new EmdhaSigningResult(
                success: false,
                errorCode: 'SYS-0001',
                errorMessage: $e->getMessage(),
                transactionId: $transactionId,
            );
        }
    }

    public function generateTransactionId(): string
    {
        $uuid = Str::uuid()->toString();
        $uuid = str_replace('-', '', $uuid);

        return substr($uuid, 0, 26);
    }
}