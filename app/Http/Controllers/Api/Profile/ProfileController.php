<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\Profile\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * جلب الملف الشخصي الكامل للمستخدم الحالي
     * GET /api/v1/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'permissions', 'tenant.media', 'media']);

        return response()->json([
            'success' => true,
            'data'    => new ProfileResource($user),
        ]);
    }

    /**
     * تحديث البيانات الأساسية للملف الشخصي (الاسم، الهاتف، البريد)
     * PUT/PATCH /api/v1/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->only(['name', 'phone', 'email']));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البيانات الشخصية بنجاح.',
            'data'    => new ProfileResource($user->fresh(['roles', 'permissions', 'tenant.media', 'media'])),
        ]);
    }

    /**
     * تغيير كلمة المرور بعد التحقق من كلمة المرور الحالية
     * PUT /api/v1/profile/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }

    /**
     * رفع أو استبدال الصورة الشخصية للمستخدم (Avatar)
     * POST /api/v1/profile/avatar
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        // حذف الصورة القديمة واستبدالها بالجديدة
        $user->clearMediaCollection('avatar');
        $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');

        return response()->json([
            'success' => true,
            'message' => 'تم رفع وتحديث الصورة الشخصية بنجاح.',
            'data'    => [
                'avatar_url' => $user->fresh()->getFirstMediaUrl('avatar'),
            ],
        ]);
    }
}
