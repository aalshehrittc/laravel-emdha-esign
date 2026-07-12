<?php

namespace Tcc\EmdhaEsign\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmdhaApiService
{
    private string $baseUrl;
    private int $timeout;
    private string $apiKey;

    public function __construct(array $config)
    {
        $this->baseUrl = $config['base_url'] ?? 'https://esign-dev.emdha.sa/eSign/SignDoc';
        $this->timeout = $config['timeout'] ?? 120;
        $this->apiKey = (string) config('emdha.api_key');
    }

    public function sendSignDocRequest(string $requestXmlBase64): array
    {
        $url = rtrim($this->baseUrl, '/');

        if (!str_ends_with($url, '/SignDoc')) {
            $url .= '/SignDoc';
        }

        $decodedXml = base64_decode($requestXmlBase64, true);
        if ($decodedXml === false) {
            $decodedXml = $requestXmlBase64;
        }

        $encodedXml = rawurlencode($decodedXml);

        Log::info('EMDHA API: Sending SignDoc request', ['url' => $url]);

        try {
            $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => '*/*',
                ])
                ->timeout($this->timeout)
                ->withBody($encodedXml, 'application/x-www-form-urlencoded')
                ->post($url);

            if (!$response->successful()) {
                Log::error('EMDHA API: HTTP error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return [
                    'status' => 0,
                    'errorCode' => 'API-0001',
                    'errorMessage' => 'HTTP error: ' . $response->status(),
                    'rawResponse' => $response->body(),
                ];
            }

            $responseBody = $response->body();

            Log::info('EMDHA API: Response received', [
                'status' => $response->status(),
            ]);

            return $this->parseSignDocResponse($responseBody);

        } catch (\Exception $e) {
            Log::error('EMDHA API: Exception', ['message' => $e->getMessage()]);

            return [
                'status' => 0,
                'errorCode' => 'API-0002',
                'errorMessage' => 'API request failed: ' . $e->getMessage(),
                'rawResponse' => null,
            ];
        }
    }

    private function parseSignDocResponse(string $xml): array
    {
        try {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            $result = [
                'status' => 0,
                'errorCode' => null,
                'errorMessage' => null,
                'respCode' => null,
                'transactionId' => null,
                'docSignatures' => [],
                'x509Certificate' => null,
                'signature' => null,
                'rawResponse' => $xml,
            ];

            $signResp = $dom->getElementsByTagName('SignResp')->item(0);
            if (!$signResp) {
                $result['errorCode'] = 'API-0003';
                $result['errorMessage'] = 'Invalid response: SignResp element not found';
                return $result;
            }

            $result['status'] = (int) $signResp->getAttribute('status');
            $result['errorCode'] = $signResp->getAttribute('errorCode') ?: null;
            $result['errorMessage'] = $signResp->getAttribute('errorMessage') ?: null;
            $result['respCode'] = $signResp->getAttribute('respCode') ?: null;
            $result['transactionId'] = $signResp->getAttribute('txn');

            $docSignatures = $signResp->getElementsByTagName('docSignature');
            foreach ($docSignatures as $docSig) {
                $result['docSignatures'][] = [
                    'id' => $docSig->getAttribute('id'),
                    'errorCode' => $docSig->getAttribute('errorCode') ?: null,
                    'errorMessage' => $docSig->getAttribute('errorMessage') ?: null,
                    'pkcs7Data' => trim($docSig->textContent),
                ];
            }

            $x509Cert = $signResp->getElementsByTagName('X509Certificate')->item(0);
            if ($x509Cert) {
                $result['x509Certificate'] = trim($x509Cert->textContent);
            }

            $signature = $signResp->getElementsByTagName('Signature')->item(0);
            if ($signature) {
                $result['signature'] = $signature->ownerDocument->saveXML($signature);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('EMDHA API: XML parse error', ['message' => $e->getMessage()]);

            return [
                'status' => 0,
                'errorCode' => 'API-0004',
                'errorMessage' => 'Failed to parse XML response: ' . $e->getMessage(),
                'rawResponse' => $xml,
            ];
        }
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}