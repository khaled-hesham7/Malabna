# 📖 وثيقة الـ APIs الرسمية لمنصة ملعبنا (Malabna Platform API v1)

مرحباً بك في الدليل الرسمي للواجهات البرمجية (RESTful APIs) الخاصة بمنصة **ملعبنا (Malabna)**.
تم إعداد هذا المستند لمطوري الواجهات الأمامية (Frontend Developers - Web & Mobile) ليوضح كل مسار، طريقته، ترويساته (Headers)، البيانات المطلوبة (Payloads)، ونماذج الاستجابة (Responses).

---

## 📌 معلومات عامة وأساسية (General Architecture)

* **الرابط الأساسي (Base URL):** `http://127.0.0.1:8000/api/v1` (أو دومين السيرفر المعتمد).
* **الترويسات الثابتة (Headers):**
  * يجب إرسال `Accept: application/json` في جميع الطلبات.
  * في المسارات المحمية يجب إرسال التوكن: `Authorization: Bearer <access_token>`.
  * عند إرسال نصوص JSON: `Content-Type: application/json`.
  * عند إرسال ملفات/صور: `multipart/form-data`.

### هيكل الاستجابة القياسي (Standard JSON Response):

#### في حالة النجاح (Success - 200/201):
```json
{
  "success": true,
  "message": "تمت العملية بنجاح.",
  "data": { ... }
}
```

#### في حالة أخطاء التحقق من البيانات (Validation Errors - 422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "phone": ["رقم الهاتف مستخدم بالفعل بحساب آخر."]
  }
}
```

#### رتب المستخدمين في النظام (User Roles):
* `super_admin`: إدارة المنصة وقبول الأندية.
* `tenant_owner`: صاحب المنشأة/النادي (صلاحيات كاملة على منشأته وموظفيه وملاعبه).
* `tenant_staff`: موظف/عامل بالملعب (تحدد صلاحياته بدقة بواسطة المالك).
* `player`: العميل / اللاعب الرياضي.

---

## فهرس المحتويات (API Modules)
1. [المصادقة وتسجيل الدخول (Auth)](#1-المصادقة-وتسجيل-الدخول-auth)
2. [اشتراكات الأندية والفوترة (Tenant Subscriptions)](#2-اشتراكات-الأندية-والفوترة-tenant-subscriptions)
3. [إدارة الملاعب وجدول الساعات (Pitches & Slots)](#3-إدارة-الملاعب-وجدول-الساعات-pitches--slots)
4. [التحكم في الساعات والحجز اليدوي (Slot Actions & Ground Booking)](#4-التحكم-في-الساعات-والحجز-اليدوي-slot-actions--ground-booking)
5. [الحجوزات الأسبوعية الثابتة (Recurring Schedules)](#5-الحجوزات-الأسبوعية-الثابتة-recurring-schedules)
6. [إدارة فريق العمل والموظفين (Staff Management)](#6-إدارة-فريق-العمل-والموظفين-staff-management)
7. [الملف الشخصي للمستخدم (Profile Management)](#7-الملف-الشخصي-للمستخدم-profile-management)
8. [إدارة السوبر أدمن واعتماد الأندية (Super Admin Approval)](#8-إدارة-السوبر-أدمن-واعتماد-الأندية-super-admin-approval)
9. [استكشاف الملاعب والبحث للاعبين (Player Marketplace)](#9-استكشاف-الملاعب-والبحث-للاعبين-player-marketplace)

---

## 1. المصادقة وتسجيل الدخول (Auth)

### 1.1 تسجيل الدخول الموحد (Login)
* **المسار:** `POST /auth/login`
* **الحماية:** عام (Public)
* **الوظيفة:** تسجيل دخول موحد لأي مستخدم (أدمن، صاحب منشأة، موظف، لاعب) بواسطة الهاتف أو البريد.
* **الـ Body (JSON):**
```json
{
  "login": "01012345678", // يقبل الهاتف أو البريد الإلكتروني
  "password": "password123"
}
```
* **الـ Response الناجح (200):**
```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "user": {
      "id": 2,
      "name": "كابتن إسماعيل المالك",
      "phone": "01012345678",
      "email": "owner@zamalek.com",
      "status": "active",
      "roles": ["tenant_owner"],
      "permissions": ["..."]
    },
    "token": "1|abc123xyz...",
    "tenant": {
      "id": 1,
      "name": "نادي الزمالك - فرع ميت عقبة",
      "status": "active"
    }
  }
}
```
> **ملاحظة هامة للفرونت:** إذا كان النادي بحالة `awaiting_payment` أو `suspended`، سيعيد الـ API توكن مؤقت مع حقل `"redirect_url": "/partner/subscription/checkout"` لتوجيه المالك لصفحة سداد الاشتراك.

---

### 1.2 تسجيل لاعب جديد (Register Player)
* **المسار:** `POST /auth/register`
* **الحماية:** عام (Public)
* **الـ Body (JSON):**
```json
{
  "name": "أحمد حسام",
  "phone": "01099887766",
  "email": "player@example.com",
  "password": "password123"
}
```

---

### 1.3 تقديم طلب تسجيل منشأة/نادي جديد (Register Venue Partner)
* **المسار:** `POST /auth/tenant/register`
* **الحماية:** عام (Public)
* **النوع:** `multipart/form-data` (لدعم رفع اللوجو)
* **الحقول:**
  * `owner_name` (إجباري): اسم صاحب المنشأة.
  * `owner_phone` (إجباري): رقم هاتف المالك (فريد).
  * `owner_email` (إجباري): البريد الإلكتروني للمالك (فريد).
  * `owner_password` (إجباري): كلمة مرور حساب المالك (8 أحرف على الأقل).
  * `name` (إجباري): اسم النادي/المنشأة.
  * `city` (إجباري): المدينة (مثل "القاهرة"، "الجيزة").
  * `address` (إجباري): العنوان التفصيلي.
  * `phone` (إجباري): هاتف التواصل الخاص بالنادي.
  * `company_name` (اختياري): اسم الشركة أو السجل التجاري.
  * `description` (اختياري): وصف النادي والملاعب.
  * `latitude`, `longitude` (اختياري): الإحداثيات الجغرافية.
  * `preferred_plan` (اختياري): `subscription_only` أو `commission_only` أو `hybrid`.
  * `logo` (اختياري): ملف صورة لشعار النادي.

---

### 1.4 جلب بيانات المستخدم المسجل حالياً (Me)
* **المسار:** `GET /auth/me`
* **الحماية:** `Bearer Token`

---

### 1.5 تسجيل الخروج (Logout)
* **المسار:** `POST /auth/logout`
* **الحماية:** `Bearer Token`

---

## 2. اشتراكات الأندية والفوترة (Tenant Subscriptions)

### 2.1 استعراض بيانات الاشتراك للنادي
* **المسار:** `GET /partner/subscription`
* **الحماية:** `Bearer Token` (صاحب المنشأة `tenant_owner`)
* **الـ Response:**
```json
{
  "success": true,
  "data": {
    "subscription_price": 2000.00,
    "billing_period": "monthly",
    "currency": "EGP",
    "status": "awaiting_payment", // pending, awaiting_payment, active, suspended
    "commission_type": "percentage",
    "commission_rate": 10.00
  }
}
```

---

### 2.2 سداد الاشتراك وتفعيل النادي فوراً (Pay Subscription)
* **المسار:** `POST /partner/subscription/pay`
* **الحماية:** `Bearer Token` (`tenant_owner`)
* **الـ Body (JSON):**
```json
{
  "payment_method": "vodafone_cash", // الخيارات: visa, mastercard, vodafone_cash, fawry, bank_transfer
  "transaction_id": "TRX-998877" // اختياري
}
```

---

## 3. إدارة الملاعب وجدول الساعات (Pitches & Slots)

### 3.1 جلب قائمة ملاعب النادي (List Pitches)
* **المسار:** `GET /partner/pitches`
* **الحماية:** `Bearer Token` (`tenant_owner` أو `tenant_staff`)
* **Query Parameters (اختيارية):**
  * `sport_type`: فلترة بنوع الرياضة (مثل `football`, `padel`, `tennis`).
  * `status`: فلترة بالحالة (`active`, `maintenance`, `inactive`).

---

### 3.2 إضافة ملعب جديد (Create Pitch)
* **المسار:** `POST /partner/pitches`
* **الحماية:** `Bearer Token`
* **النوع:** `multipart/form-data`
* **الحقول المطلوبة:**
  * `name`: اسم الملعب (مثل "الملعب الخماسي 1").
  * `sport_type`: نوع الرياضة (مثل `football`).
  * `manual_day_price`: السعر النهاري اليدوي.
  * `manual_night_price`: السعر الليلي اليدوي.
  * `online_day_price`: السعر النهاري الأونلاين.
  * `online_night_price`: السعر الليلي الأونلاين.
  * `night_start_at`: توقيت بدء الفترة الليلية (مثال: `"17:00"`).
* **حقول اختيارية:**
  * `description`, `court_size` (مثل "5x5"), `surface_type` (نجيل صناعي، ترتان..), `is_indoor` (0 أو 1).
  * `opening_time` (افتراضي `"08:00"`), `closing_time` (افتراضي `"02:00"`), `slot_duration_minutes` (30, 45, 60, 90, 120).
  * `amenities[]`: مصفوفة خدمات (مثل `["parking", "ball", "vests", "shower"]`).
  * `images[]`: صور متعددة للملعب (حتى 5 ميجا للصورة).
  * `videos[]`: فيديوهات متعددة (حتى 50 ميجا للفيديو).

> **ملاحظة للنظام:** فور إضافة الملعب وهو بحالة `active`، يولد الباك إند تلقائياً جدول الساعات لمدة **30 يوماً مقدماً**.

---

### 3.3 تفاصيل ملعب محدد
* **المسار:** `GET /partner/pitches/{pitch_id}`

---

### 3.4 تعديل ملعب والوسائط (Update Pitch)
* **المسار:** `POST /partner/pitches/{pitch_id}`
* **النوع:** `multipart/form-data`
* **ملاحظات:** يقبل نفس حقول الإنشاء للتعديل، مع إضافة خيارات التحكم في الصور:
  * `replace_images`: `1` لحذف الصور القديمة واستبدالها بالجديدة.
  * `replace_videos`: `1` لحذف الفيديوهات القديمة واستبدالها.
  * `delete_media_ids[]`: مصفوفة بأرقام الـ IDs لصور أو فيديوهات معينة لحذفها.

---

### 3.5 تغيير حالة الملعب (Toggle / Change Status)
* **المسار:** `PATCH /partner/pitches/{pitch_id}/toggle-status`
* **الـ Body (JSON - اختياري):**
```json
{
  "status": "maintenance" // الخيارات: active, maintenance, inactive
}
```
*(إذا لم يُرسل حقل status يقلب السيستم تلقائياً بين active و inactive).*

---

### 3.6 حذف ملعب (Delete Pitch)
* **المسار:** `DELETE /partner/pitches/{pitch_id}`

---

### 3.7 استعراض جدول ساعات الملعب ليوم معين (Get Slots by Date)
* **المسار:** `GET /partner/pitches/{pitch_id}/slots?date=YYYY-MM-DD`
* **الـ Query Params:**
  * `date`: تاريخ اليوم المراد عرضه (مثال `2026-11-20`). أقصى فترة استعراض هي 60 يوماً مقدماً.
* **الـ Response النموذجي:**
```json
{
  "success": true,
  "data": {
    "pitch": { "id": 5, "name": "ملعب 1" },
    "date": "2026-11-20",
    "stats": {
      "total_slots": 18,
      "available_slots": 14,
      "booked_slots": 2,
      "recurring_slots": 2,
      "blocked_slots": 0,
      "day_revenue": 700.00
    },
    "slots": [
      {
        "id": 618,
        "pitch_id": 5,
        "date": "2026-11-20",
        "start_time": "20:00",
        "end_time": "21:00",
        "time_label": "20:00 - 21:00",
        "manual_price": "400.00",
        "online_price": "380.00",
        "status": "available", // available, booked, blocked_all, blocked_online, blocked_manual
        "is_locked": false,
        "is_recurring": false
      }
    ]
  }
}
```

---

### 3.8 قفل أو فتح يوم كامل للملعب بضغطة زر (Toggle Day)
* **المسار:** `POST /partner/pitches/{pitch_id}/slots/toggle-day`
* **الـ Body (JSON):**
```json
{
  "date": "2026-11-20",
  "action": "block", // block أو unblock
  "block_type": "all" // all (إغلاق كلي) أو online (إغلاق الحجز الأونلاين مع بقاء اليدوي)
}
```

---

## 4. التحكم في الساعات والحجز اليدوي (Slot Actions & Ground Booking)

### 4.1 قفل أو فتح ساعة معينة (Toggle Block Single Slot)
* **المسار:** `PATCH /partner/slots/{slot_id}/toggle-block`
* **الـ Body (JSON):**
```json
{
  "block_type": "all" // all (قفل كامل), online (قفل النت فقط), unblock (إعادة الفتح)
}
```

---

### 4.2 تعديل سعر ساعة وتثبيته للمستقبل (Update Slot Price)
* **المسار:** `PATCH /partner/slots/{slot_id}/update-price`
* **الوظيفة:** تعديل سعر الساعة لهذا اليوم، مع إمكانية تثبيت السعر لنفس الموعد كل أسبوع للأبد.
* **الـ Body (JSON):**
```json
{
  "manual_price": 350.00, // إجباري
  "online_price": 330.00, // اختياري (إن لم يُرسل يأخذ نفس اليدوي)
  "apply_to_future": true // اختياري (تثبيت السعر للأبد لهذا اليوم والتوقيت)
}
```

---

### 4.3 تسجيل حجز كاش/يدوي مباشر من الملعب (Manual Book)
* **المسار:** `POST /partner/slots/{slot_id}/manual-book`
* **قاعدة الحوكمة الصارمة:** لا يتم إرسال سعر الحجز من الفرونت إند إطلاقاً، بل يسحب السيستم السعر المعتمد للساعة تلقائياً لمنع أي تلاعب نقدي.
* **الـ Body (JSON):**
```json
{
  "customer_name": "كابتن أحمد حسام",
  "customer_phone": "01012345678",
  "deposit_amount": 100.00, // اختياري (لو مبعتوش السيستم يعتبر المبلغ مدفوع كامل، لو دفع 0 يعني حجز فون مؤجل)
  "notes": "دفع عربون 100 والمتبقي كاش في الملعب"
}
```

---

### 4.4 إلغاء حجز قائم وإعادة فتح الساعة (Cancel Booking)
* **المسار:** `POST /partner/slots/{slot_id}/cancel-booking`
* **الـ Body (JSON):**
```json
{
  "reason": "اعتذار الفريق بسبب الأمطار",
  "refund_deposit": false // هل تم رد العربون كاش للعميل أم مصادرته؟
}
```

---

## 5. الحجوزات الأسبوعية الثابتة (Recurring Schedules)

### 5.1 قائمة الحجوزات الثابتة (List Schedules)
* **المسار:** `GET /partner/recurring-schedules`
* **Query Params (اختيارية):** `pitch_id`, `day_of_week` (من 0 لـ 6), `status` (`active`, `paused`, `cancelled`).

---

### 5.2 تثبيت حجز أسبوعي مستمر للأبد (Create Recurring Schedule)
* **المسار:** `POST /partner/recurring-schedules`
* **قاعدة التسعير:** يسحب السيستم سعر الساعة المعتمد تلقائياً ويثبته.
* **الـ Body (JSON):**
```json
{
  "pitch_id": 5,
  "customer_name": "كابتن حسام - فرقة النجوم",
  "customer_phone": "01099887766",
  "day_of_week": 5, // 0 للأحد حتى 6 للسبت (5 = الجمعة)
  "start_time": "20:00", // تنسيق HH:mm
  "end_time": "21:00", // اختياري
  "start_date": "2026-11-20", // اختياري
  "end_date": null, // null يعني مستمر للأبد حتى يتم إيقافه
  "notes": "حجز ثابت كل جمعة الساعة 8 بالليل"
}
```

---

### 5.3 عرض تفاصيل الحجز الثابت والساعات القادمة
* **المسار:** `GET /partner/recurring-schedules/{schedule_id}`
*(يعيد تفاصيل الحجز بالإضافة لأول 12 ماتش/أسبوع متسكنين له في الجدول).*

---

### 5.4 تعديل بيانات الحجز الثابت (Update Schedule)
* **المسار:** `PATCH /partner/recurring-schedules/{schedule_id}`
* **الـ Body (JSON):**
```json
{
  "customer_name": "كابتن تامر حسن - فرقة الأصدقاء",
  "customer_phone": "01011223344",
  "end_date": "2026-12-31", // لو قرروا يوقفوا عند تاريخ معين
  "notes": "تعديل اسم الكابتن"
}
```

---

### 5.5 إيقاف مؤقت أو استئناف الحجز الثابت (Toggle Pause)
* **المسار:** `PATCH /partner/recurring-schedules/{schedule_id}/toggle-pause`
* **الوظيفة:**
  * **الضغطة الأولى (تجميد):** يوقف الحجز ويحرر ويفتح كل ساعاته المستقبلية في الجدول للعامة (لفترات الامتحانات والمصايف).
  * **الضغطة الثانية (استئناف):** يعيد تفعيل الحجز ويسكن ساعاته القادمة تلقائياً باسمهم.
* **الـ Body:** فارغ.

---

### 5.6 اعتذار عن أسبوع واحد بالتاريخ (Release by Date)
* **المسار:** `POST /partner/recurring-schedules/{schedule_id}/release-date`
* **الوظيفة:** يفتح ساعة الماتش للبيع في هذا الأسبوع فقط دون المساس بباقي الأسابيع.
* **الـ Body (JSON):**
```json
{
  "date": "2026-11-20" // تاريخ الأسبوع المعتذر عنه
}
```

---

### 5.7 إلغاء الحجز الثابت نهائياً (Delete Schedule)
* **المسار:** `DELETE /partner/recurring-schedules/{schedule_id}`

---

## 6. إدارة فريق العمل والموظفين (Staff Management)
> **متاح فقط لمالك المنشأة (`tenant_owner`).**

### 6.1 جلب قائمة الصلاحيات المتاحة (Permissions List)
* **المسار:** `GET /partner/staff/permissions-list`
* **الوظيفة:** يعيد قائمة الصلاحيات مصنفة ومعنونة بالعربي لتظهر كـ Checkboxes للمالك عند إنشاء/تعديل موظف.
* **الـ Response:**
```json
{
  "success": true,
  "data": [
    { "name": "slots.view", "label": "عرض جدول الساعات والمواعيد", "category": "جدول المواعيد" },
    { "name": "slots.toggle_status", "label": "قفل أو فتح ساعة معينة", "category": "جدول المواعيد" },
    { "name": "slots.toggle_day", "label": "قفل أو فتح يوم كامل للملعب", "category": "جدول المواعيد" },
    { "name": "slots.update_price", "label": "تعديل أسعار الساعات وتثبيتها", "category": "جدول المواعيد" },
    { "name": "bookings.view", "label": "عرض سجل الحجوزات", "category": "الحجوزات" },
    { "name": "bookings.manual_create", "label": "تسجيل حجز كاش/يدوي من أرض الملعب", "category": "الحجوزات" },
    { "name": "bookings.cancel", "label": "إلغاء حجز قائم وإعادة فتح الساعة", "category": "الحجوزات" },
    { "name": "recurring.view", "label": "عرض الحجوزات الأسبوعية الثابتة", "category": "الحجوزات الثابتة" },
    { "name": "recurring.create", "label": "تثبيت حجز أسبوعي جديد", "category": "الحجوزات الثابتة" },
    { "name": "recurring.update", "label": "تعديل بيانات الحجز الثابت", "category": "الحجوزات الثابتة" },
    { "name": "recurring.pause", "label": "إيقاف مؤقت أو استئناف الحجز الثابت", "category": "الحجوزات الثابتة" },
    { "name": "recurring.release_single", "label": "اعتذار عن أسبوع واحد لحجز ثابت", "category": "الحجوزات الثابتة" },
    { "name": "recurring.cancel", "label": "إلغاء الحجز الثابت نهائياً", "category": "الحجوزات الثابتة" },
    { "name": "pitches.view", "label": "عرض الملاعب", "category": "الملاعب" },
    { "name": "pitches.manage", "label": "إضافة وتعديل وحذف الملاعب", "category": "الملاعب" },
    { "name": "reports.financial_view", "label": "الاطلاع على التقارير المالية والإيرادات", "category": "التقارير المالية" }
  ]
}
```

---

### 6.2 عرض قائمة موظفي المنشأة (List Staff)
* **المسار:** `GET /partner/staff`

---

### 6.3 إنشاء حساب موظف جديد (Create Staff)
* **المسار:** `POST /partner/staff`
* **النوع:** يقبل `JSON` أو `multipart/form-data` (في حال رفع صورة `avatar`).
* **مبدأ الأمان (Zero-Trust):** الموظف يبدأ بصفر صلاحيات، ويمنح فقط ما يُرسل في مصفوفة `permissions`.
* **الـ Body:**
```json
{
  "name": "محمود الكاشير",
  "phone": "01099887755",
  "email": "cashier@zamalek.com",
  "password": "password123",
  "permissions": [
    "slots.view",
    "bookings.view",
    "bookings.manual_create"
  ]
}
```

---

### 6.4 عرض تفاصيل موظف محدد
* **المسار:** `GET /partner/staff/{staff_id}`

---

### 6.5 تعديل بيانات وصلاحيات موظف (Update Staff)
* **المسار:** `PUT/PATCH /partner/staff/{staff_id}`
* **الـ Body (JSON):**
```json
{
  "name": "محمود إبراهيم (مدير الوردية)",
  "phone": "01099887755",
  "status": "active",
  "permissions": [
    "slots.view",
    "bookings.view",
    "bookings.manual_create",
    "bookings.cancel"
  ]
}
```

---

### 6.6 تجميد أو إعادة تفعيل حساب موظف (Toggle Staff Status)
* **المسار:** `PATCH /partner/staff/{staff_id}/toggle-status`
* **الوظيفة:** تحويل الحالة بين `active` و `inactive`. عند التجميد يتم مسح جميع توكنز جلساته فوراً لمنعه من استخدام النظام.
* **الـ Body:** فارغ.

---

### 6.7 حذف حساب موظف (Delete Staff)
* **المسار:** `DELETE /partner/staff/{staff_id}`

---

## 7. الملف الشخصي للمستخدم (Profile Management)
> **متاح لجميع المستخدمين المسجلين دخولهم في المنصة.**

### 7.1 جلب بيانات الملف الشخصي (Get Profile)
* **المسار:** `GET /profile`
* **الـ Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "كابتن إسماعيل المالك",
    "email": "owner@zamalek.com",
    "phone": "01012345678",
    "status": "active",
    "avatar_url": "http://127.0.0.1:8000/storage/...",
    "roles": ["tenant_owner"],
    "permissions": ["..."],
    "tenant": {
      "id": 1,
      "name": "نادي الزمالك - فرع ميت عقبة",
      "city": "الجيزة",
      "logo_url": "http://..."
    }
  }
}
```

---

### 7.2 تعديل البيانات الشخصية (Update Profile)
* **المسار:** `PUT /profile`
* **الـ Body (JSON):**
```json
{
  "name": "كابتن إسماعيل محمد",
  "phone": "01012345678",
  "email": "ismail@zamalek.com"
}
```

---

### 7.3 تغيير كلمة المرور (Change Password)
* **المسار:** `PUT /profile/change-password`
* **الـ Body (JSON):**
```json
{
  "current_password": "password",
  "password": "NewSecretPassword123",
  "password_confirmation": "NewSecretPassword123"
}
```

---

### 7.4 رفع أو استبدال الصورة الشخصية (Upload Avatar)
* **المسار:** `POST /profile/avatar`
* **النوع:** `multipart/form-data`
* **الحقل:** `avatar` (ملف صورة jpeg, png, jpg, webp حتى 5 ميجابايت).

---

## 8. إدارة السوبر أدمن واعتماد الأندية (Super Admin Approval)
> **متاح فقط لحساب مدير المنصة (`super_admin`).**

### 8.1 استعراض طلبات النوادي والملاعب
* **المسار:** `GET /admin/tenants`
* **Query Params (اختيارية):** `status` (`pending`, `awaiting_payment`, `active`, `rejected`, `all`).
* **الـ Response:** يعيد قائمة الأندية مقسمة بصفحات (Paginated) مع إحصائيات سريعة (`counts`) لعدد الملاعب في كل حالة.

---

### 8.2 تفاصيل طلب منشأة معينة
* **المسار:** `GET /admin/tenants/{tenant_id}`

---

### 8.3 اعتماد النادي وتحديد خطة الرسوم (Approve Tenant)
* **المسار:** `POST /admin/tenants/{tenant_id}/approve`
* **الوظيفة:** موافقة السوبر أدمن على النادي وتحديد سعر الاشتراك والعمولة.
* **الـ Body (JSON):**
```json
{
  "subscription_price": 1500.00,
  "subscription_billing_period": "monthly", // monthly أو yearly
  "commission_type": "percentage", // percentage أو fixed أو none
  "commission_rate": 10.00 // نسبة مئوية (مثلاً 10%) أو مبلغ ثابت لكل حجز
}
```

---

### 8.4 رفض طلب النادي (Reject Tenant)
* **المسار:** `POST /admin/tenants/{tenant_id}/reject`
* **الـ Body (JSON):**
```json
{
  "rejection_reason": "عدم توفر السجل التجاري أو الصور غير واضحة"
}
```

---

## 9. استكشاف الملاعب والبحث للاعبين (Player Marketplace)
> **متاح بشكل عام لجميع الزوار واللاعبين (Public / No Auth Required).**

### 9.1 استكشاف والبحث في الملاعب بالأقرب مسافة والوقت المتاح (Explore Pitches)
* **المسار:** `GET /player/explore`
* **الوظيفة:** البحث الذكي في الملاعب بترتيبها من الأقرب للأبعد مسافة (GPS)، وفلترتها باليوم والساعة الفاضية.
* **الـ Query Parameters (جميعها اختيارية):**
  * `latitude`: خط العرض لموقع اللاعب (مثل: `30.0583`).
  * `longitude`: خط الطول لموقع اللاعب (مثل: `31.2014`).
  * `max_distance`: أقصى مسافة بالكيلومتر (مثال: `10` للبحث في نطاق 10 كم فقط).
  * `date`: تاريخ اليوم المراد اللعب فيه بتنسيق `YYYY-MM-DD` (مثال: `2026-11-20`).
  * `time`: وقت الماتش بالظبط بتنسيق `HH:mm` (مثال: `20:00`).
  * `time_from` & `time_to`: البحث بنطاق زمني (مثال: `time_from=19:00&time_to=22:00` لأي ساعة فاضية في هذه الفترة).
  * `sport_type`: نوع الرياضة (مثل: `football`, `padel`, `tennis`).
  * `court_size`: مقاس الملعب (مثل: `5x5`, `7x7`).
  * `surface_type`: نوع الأرضية (مثل: `artificial_turf`).
  * `is_indoor`: صالة مغطاة أم مكشوف (`1` أو `0`).
  * `city`: فلترة بالمدينة (مثل: `الجيزة`, `القاهرة`).
  * `max_price`: أقصى سعر أونلاين للساعة.
  * `search`: كلمة بحث في اسم الملعب أو اسم النادي أو العنوان.
  * `per_page`: عدد النتائج في الصفحة (افتراضي 15).
* **نموذج الاستجابة (200 OK):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "نادي الزمالك - فرع ميت عقبة",
        "slug": "nady-alzmalk-fraa-myt-aakb",
        "city": "الجيزة",
        "address": "شارع جامعة الدول العربية، ميت عقبة، المهندسين، الجيزة",
        "phone": "01199998888",
        "logo_url": "http://127.0.0.1:8000/storage/...",
        "distance_km": 0.8, // المسافة بالكيلومتر من موقع اللاعب الحالي (مرتب تصاعدياً من الأقرب للأبعد)
        "sports_available": ["football", "padel"],
        "starting_price": 280.00, // أقل سعر يبدأ به الحجز في النادي
        "pitches_count": 2,
        "pitches": [
          {
            "id": 1,
            "name": "ملعب حلمي زامورا (خماسي)",
            "sport_type": "football",
            "court_size": "5v5",
            "surface_type": "Artificial Grass",
            "is_indoor": false,
            "online_day_price": 280.00,
            "online_night_price": 320.00,
            "starting_price": 280.00,
            "primary_image_url": "http://127.0.0.1:8000/storage/..."
          },
          {
            "id": 2,
            "name": "ملعب البادل الرئيسي",
            "sport_type": "padel",
            "court_size": "Standard",
            "surface_type": "Turf",
            "is_indoor": true,
            "online_day_price": 380.00,
            "online_night_price": 420.00,
            "starting_price": 380.00,
            "primary_image_url": null
          }
        ]
      }
    ],
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

### 9.2 عرض تفاصيل ملعب كاملة للاعب (Show Pitch Details)
* **المسار:** `GET /player/pitches/{pitch_id}`
* **Query Params (اختيارية):** `latitude` و `longitude` (لحساب مسافة الملعب عن اللاعب).
* **الوظيفة:** جلب بيانات الملعب، معرض الصور والفيديوهات، الخدمات المتاحة، وساعات الفتح والإغلاق.

---

### 9.3 عرض كل الساعات المتاحة للأونلاين لملعب محدد (Available Slots)
* **المسار:** `GET /player/pitches/{pitch_id}/available-slots?date=YYYY-MM-DD`
* **الوظيفة:** عندما يختار اللاعب ملعباً ويريد استعراض الساعات المتاحة للحجز الأونلاين فقط في يوم معين.
* **الـ Response:**
```json
{
  "success": true,
  "data": {
    "pitch": {
      "id": 5,
      "name": "الملعب الرئيسي (نجيل هولندي)"
    },
    "date": "2026-11-20",
    "available_count": 8,
    "slots": [
      {
        "id": 618,
        "date": "2026-11-20",
        "start_time": "20:00",
        "end_time": "21:00",
        "time_label": "20:00 - 21:00",
        "online_price": 380.00
      }
    ]
  }
}
```

---

## 🤝 كود الأخطاء الشائعة (HTTP Status Codes)

| الكود | المعنى | متى يحدث؟ |
| :---: | :---: | :--- |
| `200` | OK | نجاح العملية وجلب أو تحديث البيانات. |
| `201` | Created | تم إنشاء مورد جديد بنجاح (حجز، ملعب، موظف...). |
| `401` | Unauthorized | المستخدم غير مسجل دخول أو التوكن منتهي. |
| `403` | Forbidden | المستخدم ليس لديه الرتبة أو الصلاحية المطلوبة. |
| `404` | Not Found | العنصر المطلوب غير موجود (ملعب، ساعة، موظف). |
| `422` | Unprocessable Entity | خطأ في الفاليديشن (بيانات ناقصة أو بصيغة غير صحيحة). |
| `500` | Server Error | خطأ غير متوقع في السيرفر. |

---
**فريق تطوير منصة ملعبنا (Malabna Backend Team) - 2026**

