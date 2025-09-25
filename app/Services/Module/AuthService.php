<?php

namespace App\Services\Module;

use App\Enums\ActivityLogActionEnum;
use App\Enums\CredentialTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Exceptions\BaseException;
use App\Helpers\Helper;
use App\Http\Resources\Admin\AuthResource;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeEmailMail;
use App\Models\BlockedCredential;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserPreference;
use App\Repositories\Module\UserRepository;
use App\Services\App\System\DeviceDetectionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * İstifadəçi qeydiyyatı prosesini həyata keçirir
     *
     * @param array $data İstifadəçi məlumatları
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception|Throwable
     */
    public function register(array $data): void
    {
        // Activity log və digər servisleri əldə edirik
        $activityLogService = app(ActivityLogService::class);
        $blockedCredentialService = app(BlockedCredentialService::class);
        $settingService = app(SettingService::class);

        try {
            // Təhlükəsizlik parametrlərini settings-dən əldə edirik
            $securitySettings = $settingService->get('security');

            // Request meta məlumatlarını hazırlayırıq
            $metaData = [
                'email' => $data['email'],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_data' => array_diff_key($data, array_flip(['password', 'password_confirmation'])) // Həssas məlumatları çıxarırıq
            ];

            // IP blok yoxlaması
            $ipBlock = $blockedCredentialService->getBlockInfo(
                CredentialTypeEnum::IP,
                request()->ip()
            );

            if ($ipBlock?->isActive()) {
                // IP blok loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::REGISTER_BLOCKED,
                    oldData: [
                        'reason' => 'IP blocked',
                        'ip' => request()->ip()
                    ],
                    additionalData: [
                        'block_info' => $ipBlock->toArray(),
                        'meta_data' => $metaData
                    ]
                );

                throw new BaseException([
                    'message' => t('notification.ip_blocked'),
                    'remaining_time' => $ipBlock->remaining_time
                ], 422);
            }

            // Email blok yoxlaması
            $emailBlock = $blockedCredentialService->getBlockInfo(
                CredentialTypeEnum::Email,
                $data['email']
            );

            if ($emailBlock?->isActive()) {
                // Email blok loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::REGISTER_BLOCKED,
                    oldData: [
                        'reason' => 'Email blocked',
                        'email' => $data['email']
                    ],
                    additionalData: [
                        'block_info' => $emailBlock->toArray(),
                        'meta_data' => $metaData
                    ]
                );

                throw new BaseException([
                    'message' => t('notification.email_blocked'),
                    'remaining_time' => $emailBlock->remaining_time
                ], 422);
            }

            // Şifrə siyasəti yoxlaması
            $this->validatePasswordPolicy($data['password'], $securitySettings['password_policy'] ?? []);

            DB::beginTransaction();

            try {
                // İstifadəçini yaradırıq
                $createData = array_merge(
                    $data,
                    ['status' => UserStatusEnum::PendingMail]
                );

                $user = $this->userRepository->create($createData);

                $this->createUserProfile($user);

                // Qeydiyyat aktivliyini qeydə alırıq
                $activityLogService->log(
                    action: ActivityLogActionEnum::REGISTER,
                    model: $user,
                    newData: array_diff_key($user->toArray(), array_flip(['password'])), // Həssas məlumatları çıxarırıq
                    additionalData: [
                        'meta_data' => array_merge($metaData, [
                            'user_id' => $user->id,
                            'status' => $user->status
                        ])
                    ]
                );

                // Email göndəririk
                $this->sendEmail($user, 'welcome');

                // İstifadəçi preferences-lərini yaradırıq
                UserPreference::create([
                    'user_id' => $user->id,
                    'language' => app()->getLocale(),
                    'notification_settings' => [
                        'email_notifications' => true,
                        'push_notifications' => true
                    ]
                ]);

                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            // Xətanı loglayırıq
            $activityLogService->logError(
                action: ActivityLogActionEnum::REGISTER_ERROR,
                message: $e->getMessage(),
            );

            throw $e;
        }
    }

    /**
     * User tipinə görə müvafiq profil yaradır
     *
     * @param User $user
     * @return void
     * @throws Exception
     */
    private function createUserProfile(User $user): void
    {
        try {
            match ($user->user_type) {
                UserTypeEnum::Doctor => $this->createDoctorProfile($user),
                UserTypeEnum::User => $this->createPatientProfile($user),
                default => null
            };
        } catch (Exception $e) {
            Log::error('User profili yaradılarkən xəta baş verdi', [
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Doctor profili yaradır
     *
     * @param User $user
     * @return void
     */
    private function createDoctorProfile(User $user): void
    {
        $user->doctor()->create([
            'uuid' => Str::uuid(),
            'biography' => null,
            'consultation_fee' => 0,
            'consultation_duration' => 30,
            'working_days' => json_encode([]),
            'is_verified' => false,
            'is_featured' => false,
            'years_of_experience' => null,
            'practice_license_number' => null,
            'title' => null,
            'workplace' => json_encode([]),
            'social_media_links' => json_encode([]),
            'available_for_home_visit' => false,
            'available_for_online_consultation' => false,
            'home_visit_fee' => null,
            'online_consultation_fee' => null,
            'average_rating' => 0,
            'total_ratings' => 0,
            'total_patients' => 0,
        ]);
    }

    /**
     * Patient profili yaradır
     *
     * @param User $user
     * @return void
     */
    private function createPatientProfile(User $user): void
    {
        $user->patient()->create([
            'uuid' => Str::uuid(),
            'medical_history' => null,
            'allergies' => null,
            'chronic_diseases' => null,
            'current_medications' => null,
            'family_medical_history' => null,
            'additional_info' => json_encode([]),
            'blood_type' => null,
            'height' => null,
            'weight' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'emergency_contact_relation' => null,
            'insurance_provider' => null,
            'insurance_policy_number' => null,
            'insurance_expiry_date' => null,
        ]);
    }

    /**
     * Şifrə siyasətini yoxlayır
     *
     * @param string $password Şifrə
     * @param array $policy Siyasət parametrləri
     * @throws BaseException Şifrə siyasəti pozulduqda
     */
    public function validatePasswordPolicy(string $password, array $policy): void
    {
        $errors = '';

        // Minimum uzunluq
        if (isset($policy['min_length']) && strlen($password) < $policy['min_length']) {
            $errors = t('validation.password.min_length', ['length' => $policy['min_length']]);
        }

        // Böyük hərf tələbi
        if (($policy['require_uppercase'] ?? false) && !preg_match('/[A-Z]/', $password)) {
            $errors = t('validation.password.require_uppercase');
        }

        // Rəqəm tələbi
        if (($policy['require_numeric'] ?? false) && !preg_match('/[0-9]/', $password)) {
            $errors = t('validation.password.require_numeric');
        }

        // Xüsusi simvol tələbi
        if (($policy['require_special_chars'] ?? false) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors = t('validation.password.require_special_chars');
        }

        if (!empty($errors)) {
            throw new BaseException([
                'password' => $errors
            ], 422);
        }
    }

    /**
     * İstifadəçi login əməliyyatını həyata keçirir və izləyir
     *
     * @param array $credentials İstifadəçi məlumatları
     * @return array Token və istifadəçi məlumatları
     * @throws BaseException Təhlükəsizlik və autentifikasiya xətaları
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function login(array $credentials): array
    {
        // Activity log service-i əldə edirik
        $activityLogService = app(ActivityLogService::class);

        try {
            // Təhlükəsizlik parametrlərini settings-dən əldə edirik
            $securitySettings = app(SettingService::class)->get('security');
            $maxAttempts = $securitySettings['max_login_attempts'] ?? 5;
            $lockoutTime = $securitySettings['login_lockout_time'] ?? 15;

            // İstifadəçinin məlumatlarını alırıq
            $ip = request()->ip();
            $email = $credentials['email'];

            // Login cəhdi meta məlumatlarını hazırlayırıq
            $metaData = [
                'email' => $email,
                'ip_address' => $ip,
                'max_attempts' => $maxAttempts,
                'lockout_time' => $lockoutTime,
                'security_settings' => $securitySettings
            ];

            // Bloklanma yoxlaması üçün service
            $blockedCredentialService = app(BlockedCredentialService::class);

            // IP blok yoxlaması
            $ipBlock = $blockedCredentialService->getBlockInfo(CredentialTypeEnum::IP, $ip);
            if ($ipBlock?->isActive()) {
                // IP blok loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::LOGIN_BLOCKED,
                    oldData: ['reason' => 'IP blocked', 'ip' => $ip],
                    additionalData: [
                        'block_info' => $ipBlock->toArray(),
                        'meta_data' => $metaData
                    ]
                );

                throw new BaseException([
                    'email' => t('notification.ip_blocked'),
                    'remaining_time' => $ipBlock->remaining_time,
                    'blocked_until' => $ipBlock->blocked_until
                ], 422);
            }

            // Email blok yoxlaması
            $emailBlock = $blockedCredentialService->getBlockInfo(CredentialTypeEnum::Email, $email);
            if ($emailBlock?->isActive()) {
                // Email blok loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::LOGIN_BLOCKED,
                    oldData: ['reason' => 'Email bloklandı', 'email' => $email],
                    additionalData: [
                        'block_info' => $emailBlock->toArray(),
                        'meta_data' => $metaData
                    ]
                );

                throw new BaseException([
                    'email' => t('notification.email_blocked'),
                    'remaining_time' => $emailBlock->remaining_time,
                    'blocked_until' => $emailBlock->blocked_until
                ], 422);
            }

            // Cache açarını formalaşdırırıq
            $attemptsCacheKey = "login_attempts_{$email}_{$ip}";

            // Auth cəhdini yoxlayırıq
            if (!Auth::attempt($credentials)) {
                $attempts = Cache::get($attemptsCacheKey, 0) + 1;
                Cache::put($attemptsCacheKey, $attempts, now()->addMinutes($lockoutTime));

                // Uğursuz giriş cəhdi loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::LOGIN_FAILED,
                    oldData: ['email' => $email],
                    additionalData: [
                        'attempts' => $attempts,
                        'meta_data' => $metaData
                    ]
                );

                // Maksimum cəhd limitini yoxlayırıq
                if ($attempts >= $maxAttempts) {
                    Cache::forget($attemptsCacheKey);

                    $blockedUntil = now()->addMinutes($lockoutTime);

                    // IP və Email blokları yaradırıq
                    $blocks = [];
                    foreach ([
                                 ['type' => CredentialTypeEnum::IP, 'value' => $ip],
                                 ['type' => CredentialTypeEnum::Email, 'value' => $email]
                             ] as $blockData) {
                        $block = BlockedCredential::create([
                            'type' => $blockData['type'],
                            'value' => $blockData['value'],
                            'reason' => t('notification.too_many_attempts'),
                            'blocked_until' => $blockedUntil,
                            'is_active' => true
                        ]);
                        $blocks[] = $block->toArray();
                    }

                    // Bloklanma loqu
                    $activityLogService->log(
                        action: ActivityLogActionEnum::LOGIN_BLOCKED,
                        oldData: ['reason' => 'Too many attempts'],
                        newData: ['blocks' => $blocks],
                        additionalData: ['meta_data' => $metaData]
                    );

                    throw new BaseException([
                        'email' => t('notification.account_blocked'),
                        'remaining_time' => $lockoutTime,
                        'blocked_until' => $blockedUntil
                    ], 422);
                }

                throw new BaseException([
                    'email' => t('notification.user_invalid_credential'),
                    'attempts_left' => $maxAttempts - $attempts,
                    'max_attempts' => $maxAttempts
                ], 422);
            }

            // Uğurlu giriş - cache təmizləyirik
            Cache::forget($attemptsCacheKey);

            $user = Auth::user();

            // Status yoxlaması
            $statusCheck = $this->checkUserStatus($user);
            if ($statusCheck !== true) {
                // Status xətası loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::LOGIN_FAILED,
                    model: $user,
                    oldData: ['reason' => 'Invalid status', 'status' => $user->status],
                    additionalData: ['meta_data' => $metaData]
                );

                throw new BaseException($statusCheck, 422);
            }

            // Token yaradırıq
            $token = $user->createToken('auth_token')->plainTextToken;

            $ipData = Helper::getLocationFromIp($metaData['ip_address']);
            $user->loginHistory()->create([
                'ip_address' => $metaData['ip_address'],
                'user_agent' => request()->userAgent(),
                'location' => $ipData['location'],
                'meta_data' => $ipData['meta_data'] ?? [],
                'device_type' => app(DeviceDetectionService::class)->detectDevice(),
                'logged_in_at' => now(),
            ]);

            // Uğurlu giriş loqu
            $activityLogService->log(
                action: ActivityLogActionEnum::LOGIN_SUCCESS,
                model: $user,
                additionalData: [
                    'meta_data' => array_merge($metaData, [
                        'user_id' => $user->id,
                        'user_status' => $user->status
                    ])
                ]
            );

            return [
                'token' => $token,
                'user' => new AuthResource($user),
                'message' => t('notification.login_success')
            ];

        } catch (Exception $e) {
            // Xəta loqu
            $activityLogService->logError(
                action: ActivityLogActionEnum::LOGIN_ERROR,
                message: $e->getMessage(),
                model: $user ?? null
            );

            throw $e;
        }
    }

    /**
     * İstifadəçi statusunu yoxlayır
     *
     * @param User $user
     * @return true|array True və ya xəta mesajı
     */
    private function checkUserStatus(User $user): true|array
    {
        return match($user->status) {
            UserStatusEnum::PendingMail => [
                'email' => t('notification.user_status.pending_mail'),
                'status' => UserStatusEnum::PendingMail
            ],
            UserStatusEnum::Inactive => [
                'email' => t('notification.user_status.inactive'),
                'status' => UserStatusEnum::Inactive
            ],
            UserStatusEnum::Block => [
                'email' => t('notification.user_status.block'),
                'status' => UserStatusEnum::Block
            ],
            UserStatusEnum::PendingProfile => [
                'email' => t('notification.user_status.pending_profile'),
                'status' => UserStatusEnum::PendingProfile
            ],
            default => true
        };
    }

    public function logout(User $user): void
    {
        // Current timestamp ə görə ən son login qeydini tapırıq
        $lastLoginRecord = $user->loginHistory()
            ->whereNull('logged_out_at')
            ->orderBy('logged_in_at', 'desc')
            ->first();

        // Əgər aktiv login session varsa, logged_out_at sahəsini yeniləyirik
        if ($lastLoginRecord) {
            $lastLoginRecord->update([
                'logged_out_at' => now()
            ]);
        }

        $user->tokens()->delete();
        $user->logActivity(
            action: ActivityLogActionEnum::LOGOUT
        );
    }

    public function refreshToken(User $user): string
    {
        $user->tokens()->delete();
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * @throws Exception
     */
    public function sendResetLinkEmail(array $data): void
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user) {
            throw new BaseException(['email' => 'Bu email-ə sahib istifadəçi tapılmadı.'], 422);
        }

        $token = Str::random(60);
        $code = Helper::generateNumber(4);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $data['email']],
            [
                'email' => $data['email'],
                'token' => $token,
                'code' => $code,
                'created_at' => now()
            ]
        );

        $this->sendEmail($user, 'password-reset', ['token' => $token, 'code' => $code]);
    }

    /**
     * İstifadəçinin şifrəsini yeniləmə prosesini həyata keçirir
     *
     * @param array $data Şifrə yeniləmə məlumatları (token və yeni şifrə)
     * @return array Token və istifadəçi məlumatları
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function resetPassword(array $data): array
    {
        // Service-ləri əldə edirik
        $activityLogService = app(ActivityLogService::class);
        $settingService = app(SettingService::class);

        try {
            // Meta məlumatları hazırlayırıq
            $metaData = [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'code' => $data['code']
            ];

            // Reset token məlumatlarını əldə edirik
            $resetRecord = DB::table('password_reset_tokens')
                ->where('code', $data['code'])
                ->first();

            // Token yoxlaması
            if (!$resetRecord) {
                $activityLogService->log(
                    action: ActivityLogActionEnum::PASSWORD_RESET_FAILED,
                    oldData: ['reason' => 'Invalid code'],
                    additionalData: ['meta_data' => $metaData]
                );

                throw new BaseException([
                    'message' => t('notification.password_reset.invalid_token')
                ], 422);
            }

            // Token müddət yoxlaması
            if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
                // Müddəti bitmiş token-i silirik
                DB::table('password_reset_tokens')
                    ->where('email', $resetRecord->email)
                    ->delete();

                $activityLogService->log(
                    action: ActivityLogActionEnum::PASSWORD_RESET_FAILED,
                    oldData: [
                        'reason' => 'Code expired',
                        'email' => $resetRecord->email
                    ],
                    additionalData: ['meta_data' => $metaData]
                );

                throw new BaseException([
                    'message' => t('notification.password_reset.token_expired')
                ], 422);
            }

            // İstifadəçini tapırıq
            $user = User::where('email', $resetRecord->email)->first();
            if (!$user) {
                $activityLogService->log(
                    action: ActivityLogActionEnum::PASSWORD_RESET_FAILED,
                    oldData: [
                        'reason' => 'User not found',
                        'email' => $resetRecord->email
                    ],
                    additionalData: ['meta_data' => $metaData]
                );

                throw new BaseException([
                    'message' => t('notification.password_reset.user_not_found')
                ], 422);
            }

            // Şifrə siyasətini yoxlayırıq
            $securitySettings = $settingService->get('security');
            $this->validatePasswordPolicy($data['password'], $securitySettings['password_policy'] ?? []);

            DB::beginTransaction();
            try {
                // Şifrəni yeniləyirik
                $user->password = Hash::make($data['password']);
                $user->remember_token = Str::random(60);
                $user->save();

                // Reset token-i silirik
                DB::table('password_reset_tokens')
                    ->where('email', $resetRecord->email)
                    ->delete();

                // Yeni token yaradırıq
                $token = $user->createToken('auth_token')->plainTextToken;

                // Uğurlu şifrə yeniləmə loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::PASSWORD_RESET_SUCCESS,
                    model: $user,
                    additionalData: [
                        'meta_data' => array_merge($metaData, [
                            'user_id' => $user->id
                        ])
                    ]
                );

                // Password Reset eventini yayımlayırıq
                event(new PasswordReset($user));

                DB::commit();

                return [
                    'token' => $token,
                    'user' => new AuthResource($user),
                    'message' => t('notification.password_reset.success')
                ];

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            // Xəta loqu
            $activityLogService->logError(
                action: ActivityLogActionEnum::PASSWORD_RESET_ERROR,
                message: $e->getMessage(),
                model: $user ?? null
            );

            throw $e;
        }
    }

    /**
     * İstifadəçinin email ünvanını təsdiqləmə prosesini həyata keçirir
     *
     * @param Request $request Təsdiq request-i
     * @throws BaseException Email təsdiq xətaları
     * @throws Exception Sistem xətaları
     */
    public function verifyEmail(Request $request): void
    {
        // Service-ləri əldə edirik
        $activityLogService = app(ActivityLogService::class);

        try {

            if (!$request->key) {
                throw new BaseException(['key' => 'Zəhmət olmasa boş buraxmayın'], 422);
            }

            // İstifadəçi identifikatorunu deşifrə edirik
            $userId = Helper::decrypt($request->key);

            // İstifadəçini tapırıq
            $user = $this->userRepository->findById($userId);

            // Meta məlumatları hazırlayırıq
            $metaData = [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'email' => $user->email,
                'user_id' => $user->id
            ];

            // Email təsdiq statusunu yoxlayırıq
            if ($user->hasVerifiedEmail()) {
                // Artıq təsdiqlənmiş email loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::EMAIL_VERIFY_FAILED,
                    model: $user,
                    oldData: ['reason' => 'Already verified'],
                    additionalData: ['meta_data' => $metaData]
                );

                throw new BaseException([
                    'message' => t('notification.email.already_verified')
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Email-i təsdiqləyirik
                $user->update([
                    'status' => UserStatusEnum::Active,
                    'email_verified_at' => now()->timestamp
                ]);

                // Email təsdiqi loqu
                $activityLogService->log(
                    action: ActivityLogActionEnum::EMAIL_VERIFY_SUCCESS,
                    model: $user,
                    newData: [
                        'status' => UserStatusEnum::Active,
                        'email_verified_at' => now()->timestamp
                    ],
                    additionalData: ['meta_data' => $metaData]
                );

                // Email təsdiqi ilə bağlı əlavə əməliyyatlar
                $this->handlePostVerificationTasks($user);

                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            // Xəta loqu
            $activityLogService->logError(
                action: ActivityLogActionEnum::EMAIL_VERIFY_ERROR,
                message: $e->getMessage(),
                model: $user ?? null
            );

            throw $e;
        }
    }

    /**
     * Email təsdiqindən sonrakı əməliyyatları yerinə yetirir
     *
     * @param User $user
     */
    private function handlePostVerificationTasks(User $user): void
    {
        // İstifadəçi preferences-lərini yeniləyirik
        if ($user->preferences) {
            $user->preferences->update([
                'email_notifications' => true
            ]);
        }

        // Welcome emaili göndərə bilərik
        // $this->sendEmail($user, 'welcome_verified');
    }

    /**
     * @throws Exception
     */
    public function resendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new BaseException('E-mail artıq təsdiqlənib');
        }

        $this->sendEmail($user, 'welcome');
    }

    /**
     * @throws Exception
     */
    public function loginWithToken($token): array
    {
        $user = User::query()->where('uuid', Helper::decrypt($token))->first();


        if (!$user) {
            throw new BaseException('Belə bir istifadəçi mövcud deyil');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
            'user' => new AuthResource($user),
            'message' => t('notification.login_success')
        ];
    }


    public function sendEmail($user, $type, $params = []): void
    {
        $reactUrl = request()->header('Origin') ?: 'https://your-default-react-app.com';
        if ($type === 'welcome') Mail::to($user->email)->send(new WelcomeEmailMail($user, $reactUrl));
        else if ($type === 'password-reset') Mail::to($user->email)->send(new PasswordResetMail($params['token'], $params['code'], $reactUrl));
    }
}
