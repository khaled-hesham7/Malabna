<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * تعريف الصلاحيات المتاحة في النظام لفرق العمل والموظفين
     */
    public function run(): void
    {
        // إعادة تعيين كاش الصلاحيات
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // 1. صلاحيات جدول الساعات (Slots)
            'slots.view'               => 'عرض جدول الساعات والمواعيد',
            'slots.toggle_status'      => 'قفل أو فتح ساعة معينة',
            'slots.toggle_day'         => 'قفل أو فتح يوم كامل للملعب',
            'slots.update_price'       => 'تعديل أسعار الساعات',

            // 2. صلاحيات الحجوزات (Bookings)
            'bookings.view'            => 'عرض الحجوزات',
            'bookings.manual_create'   => 'تسجيل حجز كاش/يدوي مباشر من الملعب',
            'bookings.cancel'          => 'إلغاء حجز قائم',

            // 3. صلاحيات الحجوزات الأسبوعية الثابتة (Recurring Schedules)
            'recurring.view'           => 'عرض الحجوزات الثابتة',
            'recurring.create'         => 'تثبيت حجز أسبوعي جديد',
            'recurring.update'         => 'تعديل بيانات الحجز الثابت',
            'recurring.pause'          => 'إيقاف مؤقت أو استئناف الحجز الثابت',
            'recurring.release_single' => 'اعتذار عن أسبوع واحد لحجز ثابت',
            'recurring.cancel'         => 'إلغاء الحجز الثابت نهائياً',

            // 4. صلاحيات الملاعب (Pitches)
            'pitches.view'             => 'عرض الملاعب',
            'pitches.manage'           => 'إضافة وتعديل وحذف الملاعب',

            // 5. صلاحيات التقارير والماليات (Reports & Financials)
            'reports.financial_view'   => 'الاطلاع على التقارير المالية والأرباح',

            // 6. صلاحيات إدارة الموظفين (Staff Management)
            'staff.manage'             => 'إدارة حسابات فريق العمل والموظفين',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // إعطاء جميع الصلاحيات لمالك المنشأة (tenant_owner)
        $ownerRole = Role::firstOrCreate(['name' => 'tenant_owner', 'guard_name' => 'web']);
        $ownerRole->syncPermissions(array_keys($permissions));

        // إعطاء دور موظف المنشأة (tenant_staff) كقالب فارغ تماماً - الصلاحيات تمنح فقط بشكل مخصص لكل موظف
        Role::firstOrCreate(['name' => 'tenant_staff', 'guard_name' => 'web']);
    }
}

