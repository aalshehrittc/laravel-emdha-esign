# tcc/laravel-emdha-esign

Laravel package for **EMDHA eSign** digital signature integration — Saudi Arabia's government-grade digital signing platform.

## Requirements

- PHP 8.1+
- Laravel 9.x / 10.x / 11.x / 12.x
- Java JRE 8+ installed and available in `PATH`
- EMDHA SDK JAR files (provided by EMDHA CA)

## Installation

```bash
composer require tcc/laravel-emdha-esign
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=emdha-config
```

This creates `config/emdha.php` in your application.

## SDK Setup

The EMDHA SDK consists of Java JAR files that must be available on your server. They are **not** included in this package.

### 1. Create a secure directory

```bash
mkdir -p storage/app/emdha/sdk
```

Add to your `.gitignore`:

```
/storage/app/emdha/
```

### 2. Copy SDK files

Place these files from your EMDHA SDK distribution into `storage/app/emdha/sdk/`:

| File | Description |
|------|-------------|
| `emdhaCLI.jar` | Main SDK CLI |
| `eaCore.jar` | Core library |
| `gson-2.8.5.jar` | Google Gson dependency |
| `*.lics` | License file (e.g., `UAT-GOV-TCC01_JAVA.lics`) |
| `*.pfx` | PFX certificate (e.g., `ClientSDKDEV.pfx`) |

### 3. Set directory permissions

```bash
chmod -R 750 storage/app/emdha/sdk
```

## Configuration

Add the following to your `.env` file:

```env
EMDHA_BASE_URL=https://esign-dev.emdha.sa/eSign/SignDoc
EMDHA_SIP_ID=your-sip-id
EMDHA_LICENSE_PATH=/absolute/path/to/storage/app/emdha/sdk/UAT-GOV-TCC01_JAVA.lics
EMDHA_PFX_PATH=/absolute/path/to/storage/app/emdha/sdk/ClientSDKDEV.pfx
EMDHA_PFX_PASSWORD=your-pfx-password
EMDHA_PFX_ALIAS=your-pfx-alias
EMDHA_SDK_JAR_PATH=/absolute/path/to/storage/app/emdha/sdk/emdhaCLI.jar
EMDHA_SDK_CLASSPATH=
EMDHA_ENV=sandbox
EMDHA_CONTENT_ESTIMATED=40000
EMDHA_SIGN_ALGORITHM=ECC
EMDHA_HASH_ALGORITHM=SHA256
EMDHA_RESPONSE_SIG_TYPE=PKCS7
EMDHA_KYC_ID_PROVIDER=nid
EMDHA_SIP_IS_RKA=true
EMDHA_TIMEOUT=120
EMDHA_TEMP_PATH=
```

### Configuration Reference

| Key | Default | Description |
|-----|---------|-------------|
| `EMDHA_BASE_URL` | `https://esign-dev.emdha.sa/eSign/SignDoc` | EMDHA API endpoint |
| `EMDHA_SIP_ID` | `''` | Your SIP ID (provided by EMDHA CA) |
| `EMDHA_LICENSE_PATH` | `''` | Absolute path to your `.lics` license file |
| `EMDHA_PFX_PATH` | `''` | Absolute path to your `.pfx` certificate |
| `EMDHA_PFX_PASSWORD` | `''` | Password for the PFX certificate |
| `EMDHA_PFX_ALIAS` | `''` | Alias within the PFX keystore |
| `EMDHA_SDK_JAR_PATH` | `''` | Absolute path to `emdhaCLI.jar` |
| `EMDHA_SDK_CLASSPATH` | `''` | Java classpath (colon-separated). If set, uses `java -cp` instead of `java -jar` |
| `EMDHA_ENV` | `sandbox` | Environment: `sandbox` or `production` |
| `EMDHA_CONTENT_ESTIMATED` | `40000` | Estimated content size for the SDK |
| `EMDHA_SIGN_ALGORITHM` | `ECC` | Signing algorithm |
| `EMDHA_HASH_ALGORITHM` | `SHA256` | Hash algorithm |
| `EMDHA_RESPONSE_SIG_TYPE` | `PKCS7` | Response signature type |
| `EMDHA_KYC_ID_PROVIDER` | `nid` | KYC ID provider. Use `nid` (National ID) |
| `EMDHA_SIP_IS_RKA` | `true` | Whether SIP is RKA |
| `EMDHA_TIMEOUT` | `120` | Timeout in seconds for SDK and API calls |
| `EMDHA_TEMP_PATH` | `storage_path('app/emdha/temp')` | Directory for SDK temp files |

### Using Classpath (Multiple JARs)

When `EMDHA_SDK_CLASSPATH` is set, the package uses `java -cp <classpath> com.emdha.cli.Main` instead of `java -jar`. This is needed when `eaCore.jar` and `gson-2.8.5.jar` must be on the classpath alongside `emdhaCLI.jar`:

```env
EMDHA_SDK_CLASSPATH="/path/to/emdhaCLI.jar:/path/to/eaCore.jar:/path/to/gson-2.8.5.jar"
```

## Quick Start

```php
use Tcc\EmdhaEsign\Facades\Emdha;
use Tcc\EmdhaEsign\DTO\SignerInfo;
use Tcc\EmdhaEsign\DTO\DocOptions;

// Read your PDF and base64-encode it
$docBase64 = base64_encode(file_get_contents(storage_path('app/documents/contract.pdf')));

// Define signer information
$signerInfo = new SignerInfo(
    kycId: '2515534888',
    englishName: 'Ahmed Al-Rashid',
    email: 'ahmed@example.com',
    arabicName: 'أحمد الراشد',
    mobile: '556654362',
);

// Define signing options
$docOptions = new DocOptions(
    reason: 'Contract Approval',
    location: 'Riyadh',
    signatureAppearanceType: 'emdhaLogo',
);

// Sign the document
$result = Emdha::signDocument($docBase64, $signerInfo, $docOptions);

if ($result->success) {
    // Save the signed PDF
    file_put_contents(storage_path('app/documents/signed_contract.pdf'), $result->getSignedPdf());
} else {
    // Handle error
    logger()->error('EMDHA signing failed', [
        'code' => $result->errorCode,
        'message' => $result->errorMessage,
    ]);
}
```

## API Reference

### Facade: `Emdha`

The `Emdha` facade provides the primary interface to the EMDHA signing service.

#### `signDocument()`

Signs a single PDF document.

```php
$result = Emdha::signDocument(
    string $docBase64,           // Base64-encoded PDF content
    SignerInfo $signerInfo,      // Signer identity information
    ?DocOptions $docOptions,    // Signing options (optional)
    ?string $transactionId,     // Custom transaction ID (optional, auto-generated if omitted)
): EmdhaSigningResult
```

#### `signMultipleDocuments()`

Signs multiple PDF documents in a single transaction (shared signer info).

```php
$result = Emdha::signMultipleDocuments(
    array $docsBase64,           // Array of base64-encoded PDFs
    SignerInfo $signerInfo,      // Signer identity (shared across all docs)
    array $docOptionsList,       // Array of DocOptions arrays (one per doc)
    ?string $transactionId,     // Custom transaction ID (optional)
): EmdhaSigningResult
```

#### `generateTransactionId()`

Generates a unique 26-character transaction ID.

```php
$txId = Emdha::generateTransactionId();
```

### DTO: `SignerInfo`

```php
$signerInfo = new SignerInfo(
    kycId: '2515534888',        // Required — National ID / KYC ID
    englishName: 'Ahmed',       // Required — English name
    email: 'ahmed@test.com',    // Required — Email address
    arabicName: 'أحمد',        // Optional — Arabic name
    mobile: '556654362',       // Optional — Mobile number
    address: 'Riyadh',         // Optional — Street address
    regionProvince: 'Riyadh',  // Optional — Region/province
    country: 'SA',             // Optional — Country code (default: SA)
);
```

**Create from array:**

```php
// Accepts both camelCase and snake_case keys
$signerInfo = SignerInfo::fromArray([
    'kycId' => '2515534888',
    // or 'national_id' => '2515534888',
    'englishName' => 'Ahmed',
    // or 'english_name' => 'Ahmed',
    'email' => 'ahmed@test.com',
]);
```

### DTO: `DocOptions`

```php
$docOptions = new DocOptions(
    reason: 'Document Signing',              // Signing reason
    location: 'Saudi Arabia',                // Signing location
    coordinates: 'bottomRight',              // Signature position on page
    pageTobeSigned: 'all',                   // Which page(s) to sign
    signatureAppearanceType: 'emdhaLogo',    // Appearance type
    appearanceContent: '',                   // Custom appearance text
    fontSize: 7,                             // Font size for appearance
    appearanceRunDirection: 'ltr',           // Text direction: 'ltr' or 'rtl'
    appearanceBackgroundImage: '',           // Base64 image for customLogo
    signatureImagePosition: 'leftOfText',    // Image position: 'leftOfText' or 'rightOfText'
    coSign: true,                            // Co-sign (append to existing sig)
);
```

**Positions:** `bottomRight`, `bottomLeft`, `bottomMiddle`, `topRight`, `topLeft`, `topMiddle`

**Pages:** `all`, `Last`, `First`, or specific page coordinates like `1,50,50,170,110`

### DTO: `EmdhaSigningResult`

The result object returned from all signing operations.

```php
$result->success;              // bool — whether signing succeeded
$result->signedPdfBase64;      // ?string — base64-encoded signed PDF (null on failure)
$result->errorCode;            // ?string — EMDHA error code (null on success)
$result->errorMessage;         // ?string — human-readable error message
$result->transactionId;        // ?string — transaction ID used
$result->requestXml;           // ?string — raw request XML sent to API
$result->responseXml;          // ?string — raw response XML from API

$result->getSignedPdf();       // ?string — decoded signed PDF binary (null on failure)
$result->getErrorDescription(); // string — full EMDHA error description
```

## Signature Appearance Types

### No Image

```php
$docOptions = new DocOptions(signatureAppearanceType: 'noImage');
```

Minimal text-only signature appearance.

### EMDHA Logo (default)

```php
$docOptions = new DocOptions(signatureAppearanceType: 'emdhaLogo');
```

Displays the EMDHA logo alongside signature text.

### Custom Logo

```php
$signatureImageBase64 = base64_encode(file_get_contents(storage_path('app/signature.png')));

$docOptions = new DocOptions(
    signatureAppearanceType: 'customLogo',
    appearanceBackgroundImage: $signatureImageBase64,
    signatureImagePosition: 'rightOfText',
);
```

Displays your custom image alongside the signature. Pass the image as a base64-encoded string in `appearanceBackgroundImage`.

## Multi-Document Signing

Sign multiple PDFs in a single EMDHA transaction:

```php
$docs = [
    base64_encode(file_get_contents(storage_path('app/docs/contract.pdf'))),
    base64_encode(file_get_contents(storage_path('app/docs/appendix.pdf'))),
];

$docOptionsList = [
    ['reason' => 'Contract Approval', 'coordinates' => 'bottomRight'],
    ['reason' => 'Appendix Approval', 'coordinates' => 'bottomLeft', 'signatureAppearanceType' => 'customLogo'],
];

$result = Emdha::signMultipleDocuments($docs, $signerInfo, $docOptionsList);
```

All documents share the same signer info and transaction ID.

## Error Handling

### Error Codes

| Code | Description |
|------|-------------|
| `ESIGN-1001` | Invalid Request Format |
| `ESIGN-1003` | Invalid Version |
| `ESIGN-1004` | Invalid SIP Access Key Hash — check PFX, password, alias, license, SIP ID |
| `ESIGN-1005` | Invalid Transaction ID |
| `ESIGN-1006` | Invalid SIP ID |
| `ESIGN-1008` | Request exceeds maximum number of documents |
| `ESIGN-1009` | Invalid Timestamp |
| `ESIGN-1011` | Invalid KYC XML data — check signer fields |
| `ESIGN-1012` | Duplicate Transaction ID |
| `ESIGN-1014` | Invalid character in the name |
| `ESIGN-1999` | Unknown error from OSP |
| `ESIGN-2001` | Invalid Document Hash |
| `ESIGN-2002` | Invalid response signature type |
| `ESIGN-2003` | Invalid hash algorithm |
| `ESIGN-2013` | Invalid Signature Algorithm |
| `ESIGN-2036` | XML Signature validation failed |
| `ESIGN-2038` | Insufficient counter at SIP |
| `ESIGN-2047` | CN Length Validation Failed |
| `SDK-0001` | SDK JAR file not found |
| `SDK-0002` | SDK process execution failed |
| `SDK-0003` | SDK output file not created |
| `SDK-0004` | Invalid JSON response from SDK |
| `SDK-0005` | SDK process exception |
| `API-0001` | HTTP error from EMDHA API |
| `API-0002` | EMDHA API request failed |
| `API-0003` | Invalid response: SignResp element not found |
| `API-0004` | Failed to parse XML response |
| `VAL-0001` | KYC ID (National ID) is required |
| `VAL-0002` | English Name is required |
| `SYS-0001` | System error during signing |

### Error Lookup

```php
use Tcc\EmdhaEsign\Services\EmdhaErrorCode;

$message = EmdhaErrorCode::getMessage('ESIGN-1004');
// "Invalid SIP Access Key Hash — verify your PFX certificate..."

$allErrors = EmdhaErrorCode::getAllErrors();
// Returns the full error map as an array
```

### Handling Errors in Signing

```php
$result = Emdha::signDocument($docBase64, $signerInfo, $docOptions);

if (!$result->success) {
    $errorCode = $result->errorCode;         // e.g., 'ESIGN-1004'
    $errorMessage = $result->errorMessage;    // e.g., 'Invalid SIP Access Key Hash'
    $fullDescription = $result->getErrorDescription(); // Full mapped description

    // Log or display the error
    Log::error('EMDHA signing failed', [
        'code' => $errorCode,
        'message' => $fullDescription,
        'transaction_id' => $result->transactionId,
    ]);
}
```

## Integration Guide

### Example: Blade Application

**Migration:**

```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('original_filename');
    $table->string('original_path');
    $table->string('signed_path')->nullable();
    $table->string('status')->default('pending');
    $table->string('transaction_id')->unique();
    $table->text('doc_base64')->nullable();
    $table->string('signed_by')->nullable();
    $table->json('signer_info')->nullable();
    $table->string('signature_image_path')->nullable();
    $table->timestamps();
});
```

**Controller:**

```php
use Tcc\EmdhaEsign\Facades\Emdha;
use Tcc\EmdhaEsign\DTO\SignerInfo;
use Tcc\EmdhaEsign\DTO\DocOptions;

class DocumentController extends Controller
{
    public function sign(Document $document, Request $request)
    {
        $signerInfo = new SignerInfo(
            kycId: $request->input('national_id'),
            englishName: $request->input('english_name'),
            email: $request->input('email'),
        );

        $docOptions = new DocOptions(
            reason: $request->input('reason', 'Document Signing'),
            signatureAppearanceType: $request->input('appearance_type', 'emdhaLogo'),
        );

        // Handle signature image for custom logo
        if ($request->hasFile('signature_image')) {
            $imageBase64 = base64_encode($request->file('signature_image')->getContent());
            $docOptions = new DocOptions(
                reason: $docOptions->reason,
                signatureAppearanceType: 'customLogo',
                appearanceBackgroundImage: $imageBase64,
            );
        }

        $result = Emdha::signDocument(
            $document->doc_base64,
            $signerInfo,
            $docOptions,
            $document->transaction_id
        );

        if ($result->success) {
            $signedPdf = $result->getSignedPdf();
            $signedPath = 'documents/signed/' . $document->transaction_id . '.pdf';
            Storage::put($signedPath, $signedPdf);

            $document->update([
                'status' => 'signed',
                'signed_path' => $signedPath,
                'signed_by' => $signerInfo->englishName,
            ]);

            return redirect()->back()->with('success', 'Document signed!');
        }

        return redirect()->back()->with('error', 'Signing failed: ' . $result->errorMessage);
    }
}
```

### Example: Inertia + React Application

The package works the same way — use the `Emdha` facade in your controllers and return Inertia responses:

```php
public function sign(Document $document, Request $request)
{
    $result = Emdha::signDocument(
        $document->doc_base64,
        SignerInfo::fromArray($request->input('signer_info')),
        DocOptions::fromArray($request->input('doc_options')),
    );

    if ($result->success) {
        Storage::put('signed/' . $document->transaction_id . '.pdf', $result->getSignedPdf());
        $document->update(['status' => 'signed']);
    }

    return redirect()->back()->with(
        $result->success ? 'success' : 'error',
        $result->success ? 'Signed!' : $result->errorMessage
    );
}
```

### Example: API-Only (Sanctum)

```php
Route::middleware('auth:sanctum')->post('/documents/{document}/sign', function (Document $document, Request $request) {
    $result = Emdha::signDocument(
        $document->doc_base64,
        SignerInfo::fromArray($request->input('signer')),
        DocOptions::fromArray($request->input('options')),
    );

    if ($result->success) {
        Storage::put('signed/' . $document->transaction_id . '.pdf', $result->getSignedPdf());
        $document->update(['status' => 'signed']);
    }

    return response()->json([
        'success' => $result->success,
        'error_code' => $result->errorCode,
        'error_message' => $result->errorMessage,
        'transaction_id' => $result->transactionId,
    ]);
});
```

## Artisan Commands

### `emdha:check`

Verifies your EMDHA SDK setup:

```bash
php artisan emdha:check
```

Checks:
- Java runtime availability
- SDK JAR file existence
- Classpath validity (if configured)
- Required configuration values (SIP ID, license, PFX, password, alias)
- API base URL
- Temp directory writability

## How It Works

The EMDHA signing flow consists of three steps:

1. **Generate Request XML** — The SDK JAR (`GenerateSignDocRequestXML`) creates a digitally-signed XML request containing the PDF hash, signer identity, and signing parameters.

2. **Send to EMDHA API** — The request XML is sent to the EMDHA gateway via HTTP POST (`application/x-www-form-urlencoded`). The API verifies the signer's identity against the Saudi KYC system and returns a signed response.

3. **Append Response** — The SDK JAR (`AppendSignDocResponse`) merges the API's digital signature back into the original PDF, producing a fully-signed document.

```
┌──────────┐     ┌────────────────┐     ┌───────────┐
│ Your App │────▶│ emdhaCLI.jar   │────▶│ EMDHA    │
│ (Laravel)│     │ GenerateSignDoc│     │ API      │
│          │◀────│ AppendSignDoc  │◀────│ (Saudi)  │
└──────────┘     └────────────────┘     └───────────┘
     │
     └─▶ Signed PDF saved to storage
```

### Important Technical Notes

- **API Content-Type**: Must be `application/x-www-form-urlencoded` with URL-encoded decoded XML. NOT `application/xml` and NOT raw XML.
- **PFX Certificate**: Uses RC2-40-CBC legacy encryption. PHP OpenSSL 3.x cannot read it — only the Java SDK can. Do not attempt to parse the PFX with PHP.
- **KYC ID Provider**: Use `nid` (lowercase), not `SELF-NID`.
- **Base URL**: The `EMDHA_BASE_URL` should include `/SignDoc` in the path. The package guards against double `/SignDoc`.

## Troubleshooting

### "SDK JAR file not found" (SDK-0001)

- Verify `EMDHA_SDK_JAR_PATH` in `.env` points to the absolute path of `emdhaCLI.jar`
- Ensure the file exists and is readable: `ls -la /path/to/emdhaCLI.jar`

### "Invalid SIP Access Key Hash" (ESIGN-1004)

- Verify your PFX certificate, password, alias, and license file all match the SIP ID registered with EMDHA CA
- Check that `EMDHA_PFX_PASSWORD` and `EMDHA_PFX_ALIAS` are correct
- Ensure `EMDHA_LICENSE_PATH` points to the correct `.lics` file

### "java: command not found"

- Install Java JRE 8+: `sudo apt install default-jre` (Ubuntu) or `brew install openjdk` (macOS)
- Verify: `java -version`

### Classpath Issues

If you see class loading errors, set the classpath to include all JARs:

```env
EMDHA_SDK_CLASSPATH="/path/to/emdhaCLI.jar:/path/to/eaCore.jar:/path/to/gson-2.8.5.jar"
```

On Windows, use `;` as the separator instead of `:`.

### "Invalid KYC XML data" (ESIGN-1011)

- Ensure `kycId` is a valid Saudi National ID
- Ensure `englishName` does not contain special characters
- Check that all required signer fields are populated

### Temp Directory Issues

If the SDK cannot write temp files:

```bash
mkdir -p storage/app/emdha/temp
chmod 755 storage/app/emdha/temp
```

Or set a custom path:

```env
EMDHA_TEMP_PATH=/tmp/emdha
```

## Security Considerations

- **Never commit** PFX certificates, license files, or passwords to version control
- Add `storage/app/emdha/` to your `.gitignore`
- Store the SDK JARs outside the public web directory
- Use environment-specific `.env` files for sandbox vs. production credentials
- The PFX password is stored in `.env` — ensure proper file permissions (`chmod 600 .env`)

## Changelog

### v1.0.0

- Initial release
- `Emdha` facade with `signDocument()` and `signMultipleDocuments()`
- `SignerInfo`, `DocOptions`, `EmdhaSigningResult` DTOs
- `EmdhaSdkService` — Java CLI JAR bridge with classpath support
- `EmdhaApiService` — HTTP client with form-urlencoded + URL-encoded XML
- `EmdhaXmlService` — SDK input JSON builder for single and multi-doc signing
- `EmdhaErrorCode` — centralized error code map
- `EmdhaServiceProvider` — Laravel service provider with singleton bindings
- `php artisan emdha:check` — setup verification command

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.