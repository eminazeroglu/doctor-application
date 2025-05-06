<?php

namespace App\Services\Module;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Repositories\Module\PaymentRepository;
use App\Services\BaseCrudService;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseCrudService
{
    public function __construct(PaymentRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Yeni ödəniş yaradır
     *
     * @param array $data Ödəniş məlumatları
     * @return Payment
     */
    public function createPayment(array $data): Payment
    {
        $data['status'] = $data['status'] ?? PaymentStatusEnum::Pending;
        return $this->repository->create($data);
    }

    /**
     * Ödənişi tamamlanmış kimi işarələyir
     *
     * @param int $paymentId Ödəniş ID-si
     * @param array|null $transactionData Provayderdən gələn məlumatlar
     * @return Payment
     */
    public function markAsCompleted(int $paymentId, ?array $transactionData = null): Payment
    {
        return DB::transaction(function () use ($paymentId, $transactionData) {
            $payment = $this->repository->findById($paymentId);

            if ($payment->isCompleted()) {
                throw new \Exception('Ödəniş artıq tamamlanıb.');
            }

            $payment->status = PaymentStatusEnum::Completed;
            $payment->paid_at = now();
            if ($transactionData) {
                $payment->transaction = array_merge($payment->transaction ?? [], $transactionData);
            }

            $payment->save();

            return $payment;
        });
    }

    /**
     * Ödənişi uğursuz kimi işarələyir
     *
     * @param int $paymentId Ödəniş ID-si
     * @param string|null $reason Uğursuzluq səbəbi
     * @return Payment
     */
    public function markAsFailed(int $paymentId, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason) {
            $payment = $this->repository->findById($paymentId);

            if ($payment->isCompleted()) {
                throw new \Exception('Tamamlanmış ödəniş uğursuz kimi işarələnə bilməz.');
            }

            $payment->status = PaymentStatusEnum::Failed;
            if ($reason) {
                $payment->custom_fields = array_merge($payment->custom_fields ?? [], ['fail_reason' => $reason]);
            }

            $payment->save();

            return $payment;
        });
    }

    /**
     * Ödənişi geri qaytarır (refund)
     */
    public function refund(int $paymentId, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason) {
            $payment = $this->repository->findById($paymentId);

            if (!$payment->isCompleted()) {
                throw new \Exception('Yalnız tamamlanmış ödənişlər geri qaytarıla bilər.');
            }
            if ($payment->status === PaymentStatusEnum::Refunded) {
                throw new \Exception('Ödəniş artıq geri qaytarılıb.');
            }

            $payment->status = PaymentStatusEnum::Refunded;
            if ($reason) {
                $payment->custom_fields = array_merge($payment->custom_fields ?? [], ['refund_reason' => $reason]);
            }

            $payment->save();
            // TODO: Burada real ödəniş provayderinə geri qaytarma sorğusu göndərilə bilər
            return $payment;
        });
    }

    /**
     * Uğursuz ödənişi yenidən cəhd edir
     */
    public function retry(int $paymentId): Payment
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = $this->repository->findById($paymentId);

            if ($payment->status !== PaymentStatusEnum::Failed) {
                throw new \Exception('Yalnız uğursuz ödənişlər yenidən cəhd edilə bilər.');
            }

            $payment->status = PaymentStatusEnum::Pending;
            $payment->custom_fields = array_merge($payment->custom_fields ?? [], [
                'retry_count' => ($payment->custom_fields['retry_count'] ?? 0) + 1,
                'last_retry_at' => now()->toDateTimeString(),
            ]);

            $payment->save();
            // TODO: Burada ödəniş provayderinə yenidən sorğu göndərilə bilər
            return $payment;
        });
    }

    /**
     * İstifadəçinin ümumi ödəniş məbləğini hesablayır
     */
    public function calculateTotalByUser(int $userId, string $status = null): float
    {
        $query = $this->repository->findWhere(['user_id' => $userId]);
        if ($status) {
            $query->where('status', $status);
        }
        return $query->sum('amount');
    }

    /**
     * İstifadəçinin balansının ödəniş üçün kifayət edib-etmədiyini yoxlayır
     * (Qeyd: Bu metod balans sisteminə bağlıdırsa, dəyişə bilər)
     */
    public function hasEnoughBalance(int $userId, float $requiredAmount): bool
    {
        $totalCompleted = $this->calculateTotalByUser($userId, PaymentStatusEnum::Completed);
        $totalRefunded = $this->calculateTotalByUser($userId, PaymentStatusEnum::Refunded);
        $balance = $totalCompleted - $totalRefunded;

        return $balance >= $requiredAmount;
    }
}
