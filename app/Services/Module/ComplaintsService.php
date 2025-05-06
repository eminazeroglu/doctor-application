<?php

namespace App\Services\Module;

use App\Enums\ComplaintStatusEnum;
use App\Exceptions\BaseException;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Repositories\Module\ComplaintsRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ComplaintsService extends BaseCrudService
{
    public function __construct(ComplaintsRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Save Complaint
     * */
    public function saveComplaint($complaintable, $data)
    {
        $complaintable_type = get_class($complaintable);
        $complaintable_id = $complaintable->id;

        return $this->repository->model
            ->create([
                'complaintable_id' => $complaintable_id,
                'complaintable_type' => $complaintable_type,
                'user_id' => Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => ComplaintStatusEnum::Pending
            ]);
    }

    /**
     * ID ilə resursu tapır.
     *
     * @param int $id
     * @return Model
     */
    public function findById(int $id): Model
    {
        return $this->repository->model
            ->query()
            ->with([
                'messages.user',
                'user',
                'resolver',
            ])
            ->withCount('messages')
            ->findOrFail($id);
    }

    /**
     * Metodun məqsədi: Şikayəti həll edir və statusunu "resolved" olaraq yeniləyir.
     * Həll qeydi və həll edən adminin ID-sini qeydə alır.
     */
    public function status(int $id, array $request): Complaint
    {
        $data = [
            'status' => $request['status'],
            'resolution_note' => $request['resolution_note'] ?? null,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ];
        return $this->repository->status($id, $data);
    }

    /**
     * Metodun məqsədi: Şikayətə cavab mesajı yaradır.
     * Admin və ya istifadəçi tərəfindən yazılan cavabı ComplaintMessage modelinə əlavə edir.
     * Əgər cavab verilə bilməzsə, BaseException ilə xəta qaytarır.
     * @throws BaseException
     */
    public function reply(int $id, array $data): ComplaintMessage
    {
        $data = [
            'user_id' => Auth::id(),
            'message' => $data['message'],
            'attachments' => $data['attachments'] ?? null,
            'is_staff_reply' => Auth::user()->hasRole('admin'),
        ];

        return $this->repository->reply($id, $data);
    }

    /**
     * Metodun məqsədi: Şikayətlərin ümumi statistikalarını qaytarır.
     * Repository-dən statistik məlumatları alır.
     */
    public function stats(): array
    {
        return $this->repository->stats();
    }

    /**
     * Metodun məqsədi: Müəyyən istifadəçiyə aid şikayətləri tapır və qaytarır.
     * Front-end-də istifadəçinin öz şikayətlərini göstərmək üçün istifadə olunur.
     */
    public function findByUser(int $userId): Collection
    {
        return $this->repository->findByUser($userId);
    }
}
