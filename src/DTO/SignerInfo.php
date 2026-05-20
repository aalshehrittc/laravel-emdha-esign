<?php

namespace Tcc\EmdhaEsign\DTO;

class SignerInfo
{
    public function __construct(
        public readonly string $kycId,
        public readonly string $englishName,
        public readonly string $email,
        public readonly string $arabicName = '',
        public readonly string $mobile = '',
        public readonly string $address = '',
        public readonly string $regionProvince = '',
        public readonly string $country = 'SA',
    ) {}

    public function toArray(): array
    {
        return [
            'kycId' => $this->kycId,
            'englishName' => $this->englishName,
            'email' => $this->email,
            'arabicName' => $this->arabicName,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'regionProvince' => $this->regionProvince,
            'country' => $this->country,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            kycId: $data['kycId'] ?? $data['national_id'] ?? '',
            englishName: $data['englishName'] ?? $data['english_name'] ?? '',
            email: $data['email'] ?? '',
            arabicName: $data['arabicName'] ?? $data['arabic_name'] ?? '',
            mobile: $data['mobile'] ?? '',
            address: $data['address'] ?? '',
            regionProvince: $data['regionProvince'] ?? $data['region_province'] ?? '',
            country: $data['country'] ?? 'SA',
        );
    }
}