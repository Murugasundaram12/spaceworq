<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\City;
use App\Models\SystemSetting;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceSubcategory;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Bengaluru', 'slug' => 'bengaluru', 'state' => 'Karnataka', 'latitude' => 12.9716000, 'longitude' => 77.5946000, 'is_featured' => true, 'sort_order' => 1],
            ['name' => 'Chennai', 'slug' => 'chennai', 'state' => 'Tamil Nadu', 'latitude' => 13.0827000, 'longitude' => 80.2707000, 'is_featured' => true, 'sort_order' => 2],
            ['name' => 'Mumbai', 'slug' => 'mumbai', 'state' => 'Maharashtra', 'latitude' => 19.0760000, 'longitude' => 72.8777000, 'is_featured' => true, 'sort_order' => 3],
            ['name' => 'Delhi NCR', 'slug' => 'delhi-ncr', 'state' => 'Delhi', 'latitude' => 28.6139000, 'longitude' => 77.2090000, 'is_featured' => true, 'sort_order' => 4],
            ['name' => 'Hyderabad', 'slug' => 'hyderabad', 'state' => 'Telangana', 'latitude' => 17.3850000, 'longitude' => 78.4867000, 'is_featured' => true, 'sort_order' => 5],
            ['name' => 'Pune', 'slug' => 'pune', 'state' => 'Maharashtra', 'latitude' => 18.5204000, 'longitude' => 73.8567000, 'is_featured' => true, 'sort_order' => 6],
        ];

        foreach ($cities as $city) {
            City::query()->updateOrCreate(
                ['slug' => $city['slug']],
                array_merge($city, ['country' => 'India', 'is_active' => true])
            );
        }

        $categories = [
            [
                'code' => 'LONG_TERM_LEASING',
                'name' => 'Long-term Leasing',
                'subtitle' => 'Stable setups for growing teams',
                'description' => 'Rent dedicated seats and private or managed office space for long-term usage.',
                'badge_label' => 'Popular',
                'default_booking_mode' => 'reserve_request',
                'sort_order' => 1,
                'subcategories' => [
                    ['code' => 'COWORKING_SPACE', 'name' => 'Coworking Space', 'description' => 'Rent dedicated seats and private cabins in fully-equipped coworking spaces.', 'price_unit_label' => 'Price per seat per month (INR)', 'supports_upcoming' => false, 'sort_order' => 1],
                    ['code' => 'MANAGED_OFFICE', 'name' => 'Managed Office', 'description' => 'Rent dedicated office space managed by a provider.', 'price_unit_label' => 'Price per seat per month (INR)', 'supports_upcoming' => false, 'sort_order' => 2],
                    ['code' => 'OFFICE_COMMERCIAL', 'name' => 'Office/Commercial', 'description' => 'Rent/Lease office space for your company.', 'price_unit_label' => 'Price per sq.ft per month (INR)', 'supports_upcoming' => false, 'sort_order' => 3],
                    ['code' => 'NEW_BUILDING', 'name' => 'New Building', 'description' => 'List a new building; mark it upcoming with a tentative availability date if not yet ready.', 'price_unit_label' => 'Tentative availability date', 'supports_upcoming' => true, 'sort_order' => 4],
                ],
            ],
            [
                'code' => 'DAY_PASS',
                'name' => 'Day Pass',
                'subtitle' => 'Flexible coworking for individuals',
                'description' => 'Flexible daily hot-desking access for individuals and remote teams on demand.',
                'badge_label' => 'On-Demand',
                'default_booking_mode' => 'instant_book',
                'sort_order' => 2,
                'subcategories' => [
                    ['code' => 'HOT_DESK_DAY_PASS', 'name' => 'Hot Desk Day Pass', 'description' => 'Open seating day passes with high-speed WiFi and lounge access.', 'price_unit_label' => 'Price per day (INR)', 'supports_upcoming' => false, 'sort_order' => 1],
                    ['code' => 'PRIVATE_CABIN_DAY_PASS', 'name' => 'Private Cabin Day Pass', 'description' => 'Book a private day cabin for focused work or small teams.', 'price_unit_label' => 'Price per cabin per day (INR)', 'supports_upcoming' => false, 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'MEETINGS_EVENTS',
                'name' => 'Meetings & Events',
                'subtitle' => 'Professional rooms for collaboration',
                'description' => 'Bookable boardrooms, conference halls, and event venues by the hour or day.',
                'badge_label' => 'Hourly',
                'default_booking_mode' => 'instant_book',
                'sort_order' => 3,
                'subcategories' => [
                    ['code' => 'MEETING_ROOM', 'name' => 'Meeting Room', 'description' => 'Hourly or daily bookable rooms equipped with display and conferencing tools.', 'price_unit_label' => 'Price per hour (INR)', 'supports_upcoming' => false, 'sort_order' => 1],
                    ['code' => 'EVENT_SPACE', 'name' => 'Conference / Event Hall', 'description' => 'Host workshops, town halls, and corporate events with flexible seating.', 'price_unit_label' => 'Price per half-day (INR)', 'supports_upcoming' => false, 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'VIRTUAL_OFFICE',
                'name' => 'Virtual Office',
                'subtitle' => 'Business presence without full office lease',
                'description' => 'Professional business address, mail handling, and compliance support without physical space.',
                'badge_label' => 'Compliance',
                'default_booking_mode' => 'reserve_request',
                'sort_order' => 4,
                'subcategories' => [
                    ['code' => 'BUSINESS_ADDRESS', 'name' => 'Business Address', 'description' => 'Use a prime commercial address for company registration and mail handling.', 'price_unit_label' => 'Price per month (INR)', 'supports_upcoming' => false, 'sort_order' => 1],
                    ['code' => 'GST_VIRTUAL_OFFICE', 'name' => 'GST & Compliance Package', 'description' => 'Complete documentation, NOC, and utility bills for GST registration.', 'price_unit_label' => 'Price per year (INR)', 'supports_upcoming' => false, 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $subcategories = $catData['subcategories'];
            unset($catData['subcategories']);

            $category = WorkspaceCategory::query()->updateOrCreate(
                ['code' => $catData['code']],
                array_merge($catData, ['is_active' => true])
            );

            foreach ($subcategories as $subData) {
                WorkspaceSubcategory::query()->updateOrCreate(
                    ['code' => $subData['code']],
                    array_merge($subData, ['category_id' => $category->id, 'is_active' => true])
                );
            }
        }

        $amenities = [
            ['name' => 'High-Speed WiFi', 'slug' => 'high-speed-wifi', 'icon_key' => 'wifi'],
            ['name' => 'Ergonomic Chairs', 'slug' => 'ergonomic-chairs', 'icon_key' => 'chair'],
            ['name' => 'Power Backup', 'slug' => 'power-backup', 'icon_key' => 'zap'],
            ['name' => 'Cafeteria', 'slug' => 'cafeteria', 'icon_key' => 'coffee'],
            ['name' => 'Meeting Rooms', 'slug' => 'meeting-rooms', 'icon_key' => 'users'],
            ['name' => '24/7 Access', 'slug' => '24-7-access', 'icon_key' => 'clock'],
            ['name' => 'Parking', 'slug' => 'parking', 'icon_key' => 'car'],
            ['name' => 'Metro Nearby', 'slug' => 'metro-nearby', 'icon_key' => 'train'],
            ['name' => 'Receptionist', 'slug' => 'receptionist', 'icon_key' => 'user-check'],
            ['name' => 'Gym', 'slug' => 'gym', 'icon_key' => 'dumbbell'],
            ['name' => 'Conference Room', 'slug' => 'conference-room', 'icon_key' => 'monitor'],
            ['name' => 'Security', 'slug' => 'security', 'icon_key' => 'shield'],
            ['name' => 'Elevator', 'slug' => 'elevator', 'icon_key' => 'arrow-up-down'],
            ['name' => 'Wheelchair Access', 'slug' => 'wheelchair-access', 'icon_key' => 'accessibility'],
            ['name' => 'Phone Booths', 'slug' => 'phone-booths', 'icon_key' => 'phone'],
            ['name' => 'Lounge Area', 'slug' => 'lounge-area', 'icon_key' => 'sofa'],
        ];

        foreach ($amenities as $idx => $amenity) {
            Amenity::query()->updateOrCreate(
                ['slug' => $amenity['slug']],
                array_merge($amenity, ['is_active' => true, 'sort_order' => $idx + 1])
            );
        }

        $defaultSettings = [
            ['key' => 'default_tax_rate', 'value' => null, 'value_type' => 'decimal', 'group' => 'tax', 'description' => 'Platform default GST/tax rate (%) awaiting business approval.', 'is_public' => false],
            ['key' => 'booking_hold_minutes', 'value' => null, 'value_type' => 'integer', 'group' => 'booking', 'description' => 'Unpaid booking inventory hold duration in minutes awaiting approval.', 'is_public' => false],
            ['key' => 'half_day_multiplier', 'value' => null, 'value_type' => 'decimal', 'group' => 'booking', 'description' => 'Half-day slot pricing multiplier awaiting business approval.', 'is_public' => false],
            ['key' => 'max_instant_booking_seats', 'value' => null, 'value_type' => 'integer', 'group' => 'booking', 'description' => 'Maximum seats per online instant booking awaiting approval.', 'is_public' => false],
            ['key' => 'cancellation_cutoff_hours', 'value' => null, 'value_type' => 'integer', 'group' => 'cancellation', 'description' => 'Free cancellation cutoff hours before start time awaiting approval.', 'is_public' => false],
            ['key' => 'pay_at_venue_enabled', 'value' => 'false', 'value_type' => 'boolean', 'group' => 'payment', 'description' => 'Pay-at-venue availability flag (disabled until approved).', 'is_public' => false],
            ['key' => 'allow_non_day_pass_instant_book', 'value' => 'false', 'value_type' => 'boolean', 'group' => 'booking', 'description' => 'Whether non-Day-Pass categories allow instant booking (requires approval).', 'is_public' => false],
        ];

        foreach ($defaultSettings as $setting) {
            SystemSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
