<?php

namespace Tcc\EmdhaEsign\Services;

class EmdhaXmlService
{
    private array $sdkConfig;

    public function __construct(array $sdkConfig)
    {
        $this->sdkConfig = $sdkConfig;
    }

    public function buildSdkInputJson(
        string $docBase64,
        array $signerInfo,
        string $transactionId,
        array $docOptions = []
    ): array {
        $eaServiceInit = [
            'licenseFilePath' => $this->sdkConfig['license_path'] ?? '',
            'pfxFilePath' => $this->sdkConfig['pfx_path'] ?? '',
            'pfxPassword' => $this->sdkConfig['pfx_password'] ?? '',
            'pfxAlias' => $this->sdkConfig['pfx_alias'] ?? '',
            'sipId' => $this->sdkConfig['sip_id'] ?? '',
            'contentEstimated' => (int) ($this->sdkConfig['content_estimated'] ?? 40000),
        ];

        $docInput = [
            'docBase64' => $docBase64,
            'signedBy' => $signerInfo['englishName'] ?? '',
            'reason' => $docOptions['reason'] ?? 'Document Signing',
            'location' => $docOptions['location'] ?? 'Saudi Arabia',
            'appearanceContent' => $docOptions['appearanceContent'] ?? '',
            'fontSize' => (int) ($docOptions['fontSize'] ?? 7),
            'appearanceRunDirection' => $docOptions['appearanceRunDirection'] ?? 'ltr',
            'appearanceBackgroundImage' => $docOptions['appearanceBackgroundImage'] ?? '',
            'signatureAppearanceType' => $docOptions['signatureAppearanceType'] ?? 'emdhaLogo',
            'signatureImagePosition' => $docOptions['signatureImagePosition'] ?? 'leftOfText',
            'coSign' => (bool) ($docOptions['coSign'] ?? true),
            'pageTobeSigned' => $docOptions['pageTobeSigned'] ?? 'all',
            'coordinates' => $docOptions['coordinates'] ?? 'bottomRight',
        ];

        return [
            'eaServiceInit' => $eaServiceInit,
            'signerInfo' => [
                'rkaName' => ($this->sdkConfig['sip_is_rka'] ?? true) ? ($this->sdkConfig['sip_id'] ?? '') : '',
                'kycId' => $signerInfo['kycId'] ?? '',
                'englishName' => $signerInfo['englishName'] ?? '',
                'arabicName' => $signerInfo['arabicName'] ?? '',
                'mobile' => $signerInfo['mobile'] ?? '',
                'email' => $signerInfo['email'] ?? '',
                'address' => $signerInfo['address'] ?? '',
                'regionProvince' => $signerInfo['regionProvince'] ?? '',
                'country' => $signerInfo['country'] ?? 'SA',
            ],
            'docInputs' => [$docInput],
            'tempFolderPath' => $this->sdkConfig['temp_path'] ?? sys_get_temp_dir() . '/emdha',
            'transactionId' => $transactionId,
            'userConsent' => true,
            'kycIdProvider' => $this->sdkConfig['kyc_id_provider'] ?? 'nid',
        ];
    }

    public function buildMultiDocInputJson(
        array $docsBase64,
        array $signerInfo,
        string $transactionId,
        array $docOptionsList = []
    ): array {
        $eaServiceInit = [
            'licenseFilePath' => $this->sdkConfig['license_path'] ?? '',
            'pfxFilePath' => $this->sdkConfig['pfx_path'] ?? '',
            'pfxPassword' => $this->sdkConfig['pfx_password'] ?? '',
            'pfxAlias' => $this->sdkConfig['pfx_alias'] ?? '',
            'sipId' => $this->sdkConfig['sip_id'] ?? '',
            'contentEstimated' => (int) ($this->sdkConfig['content_estimated'] ?? 40000),
        ];

        $docInputs = [];
        foreach ($docsBase64 as $index => $docBase64) {
            $opts = $docOptionsList[$index] ?? [];
            $docInputs[] = [
                'docBase64' => $docBase64,
                'signedBy' => $opts['signedBy'] ?? $signerInfo['englishName'] ?? '',
                'reason' => $opts['reason'] ?? 'Document Signing',
                'location' => $opts['location'] ?? 'Saudi Arabia',
                'appearanceContent' => $opts['appearanceContent'] ?? '',
                'fontSize' => (int) ($opts['fontSize'] ?? 7),
                'appearanceRunDirection' => $opts['appearanceRunDirection'] ?? 'ltr',
                'appearanceBackgroundImage' => $opts['appearanceBackgroundImage'] ?? '',
                'signatureAppearanceType' => $opts['signatureAppearanceType'] ?? 'emdhaLogo',
                'signatureImagePosition' => $opts['signatureImagePosition'] ?? 'leftOfText',
                'coSign' => (bool) ($opts['coSign'] ?? true),
                'pageTobeSigned' => $opts['pageTobeSigned'] ?? 'all',
                'coordinates' => $opts['coordinates'] ?? 'bottomRight',
            ];
        }

        return [
            'eaServiceInit' => $eaServiceInit,
            'signerInfo' => [
                'rkaName' => ($this->sdkConfig['sip_is_rka'] ?? true) ? ($this->sdkConfig['sip_id'] ?? '') : '',
                'kycId' => $signerInfo['kycId'] ?? '',
                'englishName' => $signerInfo['englishName'] ?? '',
                'arabicName' => $signerInfo['arabicName'] ?? '',
                'mobile' => $signerInfo['mobile'] ?? '',
                'email' => $signerInfo['email'] ?? '',
                'address' => $signerInfo['address'] ?? '',
                'regionProvince' => $signerInfo['regionProvince'] ?? '',
                'country' => $signerInfo['country'] ?? 'SA',
            ],
            'docInputs' => $docInputs,
            'tempFolderPath' => $this->sdkConfig['temp_path'] ?? sys_get_temp_dir() . '/emdha',
            'transactionId' => $transactionId,
            'userConsent' => true,
            'kycIdProvider' => $this->sdkConfig['kyc_id_provider'] ?? 'nid',
        ];
    }

    public function buildAppendInputJson(
        array $eaServiceInit,
        string $tempFolderPath,
        string $apiResponseXml
    ): array {
        return [
            'eaServiceInit' => $eaServiceInit,
            'tempFilePath' => $tempFolderPath,
            'apiResponseXML' => base64_encode($apiResponseXml),
        ];
    }
}