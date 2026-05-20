<?php

return [
    'base_url' => env('EMDHA_BASE_URL', 'https://esign-dev.emdha.sa/eSign/SignDoc'),
    'sip_id' => env('EMDHA_SIP_ID', ''),
    'license_path' => env('EMDHA_LICENSE_PATH', ''),
    'pfx_path' => env('EMDHA_PFX_PATH', ''),
    'pfx_password' => env('EMDHA_PFX_PASSWORD', ''),
    'pfx_alias' => env('EMDHA_PFX_ALIAS', ''),
    'sdk_jar_path' => env('EMDHA_SDK_JAR_PATH', ''),
    'sdk_classpath' => env('EMDHA_SDK_CLASSPATH', ''),
    'environment' => env('EMDHA_ENV', 'sandbox'),
    'content_estimated' => (int) env('EMDHA_CONTENT_ESTIMATED', 40000),
    'sign_algorithm' => env('EMDHA_SIGN_ALGORITHM', 'ECC'),
    'hash_algorithm' => env('EMDHA_HASH_ALGORITHM', 'SHA256'),
    'response_sig_type' => env('EMDHA_RESPONSE_SIG_TYPE', 'PKCS7'),
    'kyc_id_provider' => env('EMDHA_KYC_ID_PROVIDER', 'nid'),
    'sip_is_rka' => (bool) env('EMDHA_SIP_IS_RKA', true),
    'user_consent' => true,
    'timeout' => (int) env('EMDHA_TIMEOUT', 120),
    'temp_path' => env('EMDHA_TEMP_PATH', storage_path('app/emdha/temp')),
];