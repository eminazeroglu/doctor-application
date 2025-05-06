<?php

namespace App\Repositories\Module;

use App\Enums\ComplaintStatusEnum;
use App\Enums\ComplaintTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Exceptions\BaseException;
use App\Helpers\Helper;
use App\Models\Complaint;
use App\Repositories\BaseRepository;
use App\Services\Filter\ComplaintsFilter;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class ComplaintsRepository extends BaseRepository
{
    public function __construct(Complaint $model)
    {
        parent::__construct($model);
        $this->setFilter(new ComplaintsFilter(request()));
        $this->withCount = ['messages'];
        $this->with = ['user', 'complaintable'];
    }

    public function filters(): array
    {
        return [
            'statuses' => Helper::enumToFilter(ComplaintStatusEnum::class),
            'types' => Helper::enumToFilter(ComplaintTypeEnum::class),
        ];
    }

    /**
     * Metodun məqsədi: Şikayəti statusunu dəyişçəkdir.=
     * @throws Exception
     */
    public function status($id, $data)
    {
        return $this->transact(function () use ($id, $data) {
            $complaint = $this->findById($id);
            $complaint->update($data);

            $notificationType = [
                ComplaintStatusEnum::InProgress => NotificationTypeEnum::COMPLAINT_PROGRESS,
                ComplaintStatusEnum::Resolved => NotificationTypeEnum::COMPLAINT_RESOLVED,
                ComplaintStatusEnum::Rejected => NotificationTypeEnum::COMPLAINT_REJECT,
                ComplaintStatusEnum::Closed => NotificationTypeEnum::COMPLAINT_CLOSED,
            ];

            $complaint->user->notify(
                type: $notificationType[$data['status']],
                data: [
                    'title' => $complaint->title,
                    'resolution_note' => $complaint->resolution_note
                ]
            );

            return $complaint;
        });
    }

    /**
     * Metodun məqsədi: Şikayətə cavab mesajı yaradır.
     * Admin və ya istifadəçi tərəfindən yazılan cavabı ComplaintMessage modelinə əlavə edir.
     * Əgər cavab verilə bilməzsə, BaseException ilə xəta qaytarır.
     * @throws BaseException
     * @throws Exception
     */
    public function reply($id, $data)
    {
        return $this->transact(function () use ($id, $data) {
            $complaint = $this->findById($id);
            if (!$complaint->canReply()) {
                throw new BaseException('Bu şikayətə cavab verilə bilməz.', 403);
            }
            $data = $complaint->messages()->create($data);
            $complaint->user->notify(
                type: NotificationTypeEnum::COMPLAINT_REPLY,
                data: [
                    'title' => $complaint->title,
                ]
            );
            return $data;
        });
    }

    /**
     * Metodun məqsədi: Müəyyən istifadəçiyə aid şikayətləri tapır.
     * Statusa görə filtrləmə imkanı verir və cache ilə performans artırır.
     */
    public function findByUser(int $userId, ?string $status = null): Collection
    {
        return $this->executeWithCache("findByUser_{$userId}_{$status}", function () use ($userId, $status) {
            return $this->baseQuery()
                ->where('user_id', $userId)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->get();
        });
    }

    /**
     * Metodun məqsədi: Şikayətlərin statuslara görə statistikalarını qaytarır.
     * Cache ilə tez-tez çağırılan sorğuların performansını artırır.
     */
    public function stats(): array
    {
        return $this->executeWithCache('stats', function () {
            return [
                'total' => $this->model->count(),
                'pending' => $this->model->pending()->count(),
                'in_progress' => $this->model->inProgress()->count(),
                'resolved' => $this->model->resolved()->count(),
                'rejected' => $this->model->rejected()->count(),
                'closed' => $this->model->closed()->count(),
            ];
        });
    }

}
