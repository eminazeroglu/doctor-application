<?php

namespace App\Traits\Controller;

use Illuminate\Http\JsonResponse;

trait HasAuthorizesRequests
{
    protected ?string $permission = null;
    protected string $forbiddenMessage = 'You are not authorized to do this operation.';

    /**
     * İcazə identifikatorunu təyin edir
     *
     * @param string|null $permission
     * @return $this
     */
    public function setPermission(?string $permission): self
    {
        $this->permission = $permission;
        return $this;
    }

    /**
     * İcazə olmadıqda göstəriləcək mesajı təyin edir
     *
     * @param string $message
     * @return $this
     */
    public function setForbiddenMessage(string $message): self
    {
        $this->forbiddenMessage = $message;
        return $this;
    }

    /**
     * İcazə yoxlaması metodu
     *
     * @param string $ability
     * @return bool
     */
    public function authorizeAction(string $ability): bool
    {
        if (!$this->permission) {
            return true; // İcazə təyin edilməyibsə, həmişə icazə veririk
        }

        return request()->user()->hasPermission($this->permission . '_' . $ability);
    }

    /**
     * İcazə xətası qaytarır
     *
     * @return JsonResponse
     */
    public function forbidden(): JsonResponse
    {
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
