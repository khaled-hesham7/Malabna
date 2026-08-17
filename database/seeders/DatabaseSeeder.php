<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Pitch;
use App\Models\PitchPricingRule;
use App\Models\PitchSlot;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء الأدوار الأساسية (Spatie Roles)
        $superAdminRole  = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $tenantOwnerRole = Role::firstOrCreate(['name' => 'tenant_owner', 'guard_name' => 'web']);
        $tenantStaffRole = Role::firstOrCreate(['name' => 'tenant_staff', 'guard_name' => 'web']);
        $playerRole      = Role::firstOrCreate(['name' => 'player', 'guard_name' => 'web']);

        // 2. إنشاء السوبر أدمن (Super Admin)
        $admin = User::create([
            'name'      => 'Super Admin',
            'email'     => 'admin@malabna.com',
            'phone'     => '01000000000',
            'password'  => Hash::make('password'),
            'user_type' => 'super_admin',
            'status'    => 'active',
        ]);
        $admin->assignRole($superAdminRole);

        // 3. إنشاء نادي الزمالك - فرع ميت عقبة (Tenant)
        $tenant = Tenant::create([
            'name'                    => 'نادي الزمالك - فرع ميت عقبة',
            'slug'                    => 'zamalek-meet-oqba',
            'company_name'            => 'شركة نادي الزمالك للاستثمار الرياضي',
            'city'                    => 'الجيزة',
            'phone'                   => '01199998888',
            'address'                 => 'شارع جامعة الدول العربية، ميت عقبة، المهندسين، الجيزة',
            'latitude'                => 30.0583,
            'longitude'               => 31.2014,
            'subscription_price'      => 2000.00,
            'subscription_expires_at' => now()->addYear(),
            'commission_type'         => 'percentage',
            'commission_rate'         => 10.00,
            'status'                  => 'active',
        ]);

        // 4. إنشاء مدير/مالك المجمع (Tenant Owner)
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'كابتن إسماعيل المالك',
            'email'     => 'owner@zamalek.com',
            'phone'     => '01012345678',
            'password'  => Hash::make('password'),
            'user_type' => 'tenant_owner',
            'status'    => 'active',
        ]);
        $owner->assignRole($tenantOwnerRole);

        // 5. إنشاء المسؤول عن إدارة الملعب (Tenant Staff)
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'عم إبراهيم (مسؤول الملعب)',
            'email'     => 'staff@zamalek.com',
            'phone'     => '01155554444',
            'password'  => Hash::make('password'),
            'user_type' => 'tenant_staff',
            'status'    => 'active',
        ]);
        $staff->assignRole($tenantStaffRole);

        // 6. إنشاء الملاعب داخل نادي الزمالك
        $footballPitch = Pitch::create([
            'tenant_id'    => $tenant->id,
            'name'         => 'ملعب حلمي زامورا (خماسي)',
            'sport_type'   => 'football',
            'court_size'   => '5v5',
            'surface_type' => 'Artificial Grass',
            'description'  => 'ملعب خماسي نجيل صناعي ممتاز مزود بإضاءة ليد وكشافات دولية.',
            'status'       => 'active',
        ]);

        $padelPitch = Pitch::create([
            'tenant_id'    => $tenant->id,
            'name'         => 'ملعب البادل الرئيسي',
            'sport_type'   => 'padel',
            'court_size'   => 'Standard',
            'surface_type' => 'Turf',
            'description'  => 'ملعب بادل زجاجي بانورامي مغطى بالكامل.',
            'status'       => 'active',
        ]);

        // 7. إضافة قواعد التسعير (تم إضافة day_of_week)
        PitchPricingRule::create([
            'pitch_id'           => $footballPitch->id,
            'name'               => 'تسعير المساء (ذروة)',
            'day_of_week'        => 5, // الجمعة مثلاً (0-6)
            'start_time'         => '18:00:00',
            'end_time'           => '02:00:00',
            'price_per_hour'     => 350.00,
            'min_deposit_type'   => 'percentage',
            'min_deposit_amount' => 20.00,
            'status'             => 'active',
        ]);

        // 8. توليد ساعات حجز تجريبية
        for ($i = 17; $i < 22; $i++) {
            PitchSlot::create([
                'pitch_id'          => $footballPitch->id,
                'date'              => now()->toDateString(),
                'start_time'        => sprintf('%02d:00:00', $i),
                'end_time'          => sprintf('%02d:00:00', $i + 1),
                'price'             => 350.00,
                'status'            => 'available',
                'is_visible_online' => ($i !== 19),
            ]);
        }

        // 9. إنشاء عميل / لاعب تجريبي
        $player = User::create([
            'name'      => 'كابتن محمود العميل',
            'email'     => 'player@gmail.com',
            'phone'     => '01200000000',
            'password'  => Hash::make('password'),
            'user_type' => 'customer',
            'status'    => 'active',
        ]);
        $player->assignRole($playerRole);
    }
}
