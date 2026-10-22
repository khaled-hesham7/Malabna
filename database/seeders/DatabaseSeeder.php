<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Pitch;
use App\Models\PitchPricingRule;
use App\Models\PitchSlot;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RecurringSchedule;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء الأدوار الأساسية (Spatie Roles)
        $superAdminRole  = Role::firstOrCreate(['name' => 'super_admin']);
        $tenantOwnerRole = Role::firstOrCreate(['name' => 'tenant_owner']);
        $tenantStaffRole = Role::firstOrCreate(['name' => 'tenant_staff']);
        $playerRole      = Role::firstOrCreate(['name' => 'player']);

        // 2. إنشاء السوبر أدمن (Super Admin)
        $admin = User::create([
            'name'      => 'Super Admin',
            'email'     => 'admin@malabna.com',
            'phone'     => '01000000000',
            'password'  => Hash::make('password'),
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
            'currency'                => 'EGP',
            'latitude'                => 30.0583,
            'longitude'               => 31.2014,
            'subscription_price'      => 2000.00,
            'subscription_starts_at'  => now()->subMonths(2),
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
            'status'    => 'active',
        ]);
        $owner->assignRole($tenantOwnerRole);
        $tenant->update(['owner_id' => $owner->id]);

        // 5. إنشاء المسؤول عن إدارة الملعب (Tenant Staff)
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'عم إبراهيم (مسؤول الملعب)',
            'email'     => 'staff@zamalek.com',
            'phone'     => '01155554444',
            'password'  => Hash::make('password'),
            'status'    => 'active',
        ]);
        $staff->assignRole($tenantStaffRole);

        // 6. إنشاء عميل / لاعب تجريبي (Customer)
        $player = User::create([
            'name'      => 'كابتن محمود العميل',
            'email'     => 'player@gmail.com',
            'phone'     => '01200000000',
            'password'  => Hash::make('password'),
            'status'    => 'active',
        ]);
        $player->assignRole($playerRole);

        // 7. مدفوعات اشتراك النادي للمنصة (Subscription Payments)
        SubscriptionPayment::create([
            'tenant_id'            => $tenant->id,
            'amount'               => 2000.00,
            'currency'             => 'EGP',
            'payment_method'       => 'bank_transfer',
            'transaction_id'       => 'SUB-ZAM-001',
            'billing_period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'billing_period_end'   => now()->startOfMonth()->toDateString(),
            'status'               => 'completed',
            'created_by_user_id'   => $admin->id,
            'notes'                => 'دفعة اشتراك الشهر الماضي بنجاح',
            'paid_at'              => now()->subMonth(),
        ]);

        // 8. إنشاء الملاعب داخل نادي الزمالك
        $footballPitch = Pitch::create([
            'tenant_id'          => $tenant->id,
            'name'               => 'ملعب حلمي زامورا (خماسي)',
            'sport_type'         => 'football',
            'court_size'         => '5v5',
            'surface_type'       => 'Artificial Grass',
            'description'        => 'ملعب خماسي نجيل صناعي ممتاز مزود بإضاءة ليد وكشافات دولية.',
            'manual_day_price'   => 300.00,
            'manual_night_price' => 350.00,
            'online_day_price'   => 280.00,
            'online_night_price' => 320.00,
            'night_start_at'     => '18:00:00',
            'is_indoor'          => false,
            'amenities'          => ['lighting', 'parking', 'changing_rooms', 'cafeteria'],
            'status'             => 'active',
        ]);

        $padelPitch = Pitch::create([
            'tenant_id'          => $tenant->id,
            'name'               => 'ملعب البادل الرئيسي',
            'sport_type'         => 'padel',
            'court_size'         => 'Standard',
            'surface_type'       => 'Turf',
            'description'        => 'ملعب بادل زجاجي بانورامي مغطى بالكامل.',
            'manual_day_price'   => 400.00,
            'manual_night_price' => 450.00,
            'online_day_price'   => 380.00,
            'online_night_price' => 420.00,
            'night_start_at'     => '18:00:00',
            'is_indoor'          => true,
            'amenities'          => ['air_conditioning', 'lighting', 'wifi', 'changing_rooms'],
            'status'             => 'active',
        ]);

        // 9. إضافة قواعد التسعير
        PitchPricingRule::create([
            'pitch_id'              => $footballPitch->id,
            'name'                  => 'تسعير المساء (ذروة الجمعة)',
            'day_of_week'           => 5, // الجمعة (0-6)
            'start_time'            => '18:00:00',
            'end_time'              => '02:00:00',
            'manual_price_per_hour' => 350.00,
            'online_price_per_hour' => 320.00,
            'min_deposit_type'      => 'fixed',
            'min_deposit_amount'    => 50.00,
            'status'                => 'active',
        ]);

        // 10. جدول حجز دائم أسبوعي (Weekly Recurring Schedule)
        $recurringSchedule = RecurringSchedule::create([
            'tenant_id'      => $tenant->id,
            'pitch_id'       => $footballPitch->id,
            'customer_name'  => 'فريق كابتن أحمد حاتم',
            'customer_phone' => '01099887766',
            'agreed_price'   => 320.00,
            'day_of_week'    => 5, // كل جمعة
            'start_time'     => '21:00:00',
            'end_time'       => '22:00:00',
            'booking_type'   => 'manual',
            'status'         => 'active',
            'start_date'     => now()->startOfMonth()->toDateString(),
            'notes'          => 'حجز ثابت أسبوعياً كل يوم جمعة الساعة 9 مساءً',
        ]);

        // 11. توليد ساعات حجز تجريبية ومحاكاة الحجوزات والمدفوعات
        $slotsData = [
            ['hour' => 17, 'status' => 'available'],
            ['hour' => 18, 'status' => 'booked'],
            ['hour' => 19, 'status' => 'blocked_online'],
            ['hour' => 20, 'status' => 'booked'],
            ['hour' => 21, 'status' => 'booked', 'recurring' => true],
        ];

        foreach ($slotsData as $data) {
            $h = $data['hour'];
            $slot = PitchSlot::create([
                'pitch_id'               => $footballPitch->id,
                'date'                   => now()->toDateString(),
                'start_time'             => sprintf('%02d:00:00', $h),
                'end_time'               => sprintf('%02d:00:00', $h + 1),
                'manual_price'           => 350.00,
                'online_price'           => 320.00,
                'status'                 => $data['status'],
                'is_recurring'           => isset($data['recurring']),
                'recurring_customer_name'=> isset($data['recurring']) ? 'فريق كابتن أحمد حاتم' : null,
                'recurring_schedule_id'  => isset($data['recurring']) ? $recurringSchedule->id : null,
            ]);

            // إضافة حجز تجريبي للساعة 18:00 (حجز مكتمل بكامل المبلغ)
            if ($h === 18) {
                $booking = Booking::create([
                    'booking_code'     => 'MLB-18001',
                    'tenant_id'        => $tenant->id,
                    'pitch_id'         => $footballPitch->id,
                    'pitch_slot_id'    => $slot->id,
                    'user_id'          => $player->id,
                    'booking_type'     => 'online',
                    'unit_price'       => 320.00,
                    'total_price'      => 320.00,
                    'deposit_amount'   => 50.00,
                    'paid_amount'      => 320.00,
                    'remaining_amount' => 0.00,
                    'commission_amount'=> 32.00, // 10% من 320
                    'status'           => 'completed',
                ]);

                Payment::create([
                    'tenant_id'      => $tenant->id,
                    'booking_id'     => $booking->id,
                    'created_by_user_id' => $player->id,
                    'payment_code'   => 'PAY-18001',
                    'amount'         => 320.00,
                    'currency'       => 'EGP',
                    'payment_method' => 'online_gateway',
                    'payment_type'   => 'full',
                    'status'         => 'completed',
                    'transaction_id' => 'TXN-ONLINE-991',
                    'notes'          => 'تم الدفع بالكامل أونلاين عبر فيزا',
                    'paid_at'        => now(),
                ]);
            }

            // إضافة حجز تجريبي للساعة 20:00 (حجز مؤكد مع عربون)
            if ($h === 20) {
                $booking = Booking::create([
                    'booking_code'     => 'MLB-20002',
                    'tenant_id'        => $tenant->id,
                    'pitch_id'         => $footballPitch->id,
                    'pitch_slot_id'    => $slot->id,
                    'user_id'          => $player->id,
                    'booking_type'     => 'online',
                    'unit_price'       => 320.00,
                    'total_price'      => 320.00,
                    'deposit_amount'   => 50.00,
                    'paid_amount'      => 50.00,
                    'remaining_amount' => 270.00,
                    'commission_amount'=> 32.00,
                    'status'           => 'confirmed',
                ]);

                Payment::create([
                    'tenant_id'      => $tenant->id,
                    'booking_id'     => $booking->id,
                    'created_by_user_id' => $player->id,
                    'payment_code'   => 'PAY-20002',
                    'amount'         => 50.00,
                    'currency'       => 'EGP',
                    'payment_method' => 'vodafone_cash',
                    'payment_type'   => 'deposit',
                    'status'         => 'completed',
                    'transaction_id' => 'TXN-VOD-882',
                    'notes'          => 'تم دفع العربون عبر فودافون كاش والمتبقي بالملعب',
                    'paid_at'        => now(),
                ]);
            }
        }
    }
}
