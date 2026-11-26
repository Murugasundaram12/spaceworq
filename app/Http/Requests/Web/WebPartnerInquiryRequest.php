<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Public\StorePartnerInquiryRequest;

class WebPartnerInquiryRequest extends StorePartnerInquiryRequest
{
    protected function prepareForValidation(): void
    {
        $inquiryType = (string) $this->input('inquiry_type', '');

        $entityName = $this->input('entity_name')
            ?? $this->input('centreName')
            ?? $this->input('bldgName')
            ?? $this->input('projName')
            ?? '';

        $cityName = $this->input('city_name')
            ?? $this->input('providerCity')
            ?? $this->input('bldgCity')
            ?? $this->input('projCity')
            ?? '';

        $locality = $this->input('locality')
            ?? $this->input('bldgLocality')
            ?? $this->input('projMicroMarket');

        $contactName = $this->input('contact_name')
            ?? $this->input('contactPerson')
            ?? $this->input('bldgContact')
            ?? $this->input('projContact')
            ?? '';

        $contactPhone = $this->input('contact_phone')
            ?? $this->input('contactPhone')
            ?? $this->input('bldgPhone')
            ?? $this->input('projPhone')
            ?? '';

        $contactEmail = $this->input('contact_email')
            ?? $this->input('contactEmail')
            ?? $this->input('bldgEmail')
            ?? $this->input('projEmail')
            ?? '';

        $this->merge([
            'inquiry_type' => $inquiryType,
            'entity_name' => trim((string) $entityName),
            'city_name' => trim((string) $cityName),
            'locality' => $locality !== null ? trim((string) $locality) : null,
            'total_desks' => $this->input('total_desks', $this->input('totalDesks')),
            'desk_price' => $this->input('desk_price', $this->input('deskPrice')),
            'total_area_sqft' => $this->input('total_area_sqft', $this->input('bldgArea', $this->input('projTotalArea'))),
            'floor_plate_sqft' => $this->input('floor_plate_sqft', $this->input('bldgFloorPlate', $this->input('projFloorPlate'))),
            'partnership_model' => $this->input('partnership_model', $this->input('bldgModel')),
            'construction_stage' => $this->input('construction_stage', $this->input('projStage')),
            'expected_completion' => $this->input('expected_completion', $this->input('projDate')),
            'indicative_rent' => $this->input('indicative_rent', $this->input('bldgRent', $this->input('projRent'))),
            'developer_brand' => $this->input('developer_brand', $this->input('projDevBrand')),
            'contact_name' => trim((string) $contactName),
            'contact_phone' => trim((string) $contactPhone),
            'contact_email' => strtolower(trim((string) $contactEmail)),
        ]);
    }
}
