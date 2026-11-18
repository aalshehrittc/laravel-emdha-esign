<?php

namespace Tcc\EmdhaEsign\DTO;

class DocOptions
{
    public function __construct(
        public readonly string $reason = 'Document Signing',
        public readonly string $location = 'Saudi Arabia',
        public readonly string $coordinates = 'bottomRight',
        public readonly string $pageTobeSigned = 'all',
        public readonly string $signatureAppearanceType = 'emdhaLogo',
        public readonly string $appearanceContent = '',
        public readonly int $fontSize = 7,
        public readonly string $appearanceRunDirection = 'ltr',
        public readonly string $appearanceBackgroundImage = '',
        public readonly string $signatureImagePosition = 'leftOfText',
        /** Null falls back to SignerInfo englishName; '' blanks appearance for GRAPHIC mode. */
        public readonly ?string $signedBy = null,
        public readonly bool $coSign = true,
    ) {}

    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'location' => $this->location,
            'coordinates' => $this->coordinates,
            'pageTobeSigned' => $this->pageTobeSigned,
            'signatureAppearanceType' => $this->signatureAppearanceType,
            'appearanceContent' => $this->appearanceContent,
            'fontSize' => $this->fontSize,
            'appearanceRunDirection' => $this->appearanceRunDirection,
            'appearanceBackgroundImage' => $this->appearanceBackgroundImage,
            'signatureImagePosition' => $this->signatureImagePosition,
            'signedBy' => $this->signedBy,
            'coSign' => $this->coSign,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            reason: $data['reason'] ?? 'Document Signing',
            location: $data['location'] ?? 'Saudi Arabia',
            coordinates: $data['coordinates'] ?? 'bottomRight',
            pageTobeSigned: $data['pageTobeSigned'] ?? $data['page_to_be_signed'] ?? 'all',
            signatureAppearanceType: $data['signatureAppearanceType'] ?? $data['appearance_type'] ?? 'emdhaLogo',
            appearanceContent: $data['appearanceContent'] ?? '',
            fontSize: (int) ($data['fontSize'] ?? 7),
            appearanceRunDirection: $data['appearanceRunDirection'] ?? 'ltr',
            appearanceBackgroundImage: $data['appearanceBackgroundImage'] ?? $data['appearance_background_image'] ?? '',
            signatureImagePosition: $data['signatureImagePosition'] ?? 'leftOfText',
            signedBy: array_key_exists('signedBy', $data) ? $data['signedBy'] : ($data['signed_by'] ?? null),
            coSign: (bool) ($data['coSign'] ?? true),
        );
    }
}