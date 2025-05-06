<?php

namespace App\Services\App\Telegram\Traits;

trait HasFile
{
    /**
     * Send document
     */
    public function sendDocument($document, string $caption = '', array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $this->chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ], $options);

        if ($document instanceof UploadedFile) {
            $params['document'] = $document;
        } else if (filter_var($document, FILTER_VALIDATE_URL)) {
            $params['document'] = $document;
        } else if (file_exists($document)) {
            $params['document'] = fopen($document, 'r');
        } else {
            $params['document'] = $document;
        }

        return $this->request('sendDocument', $params);
    }

    /**
     * Send photo
     */
    public function sendPhoto($photo, string $caption = '', array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $this->chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ], $options);

        if ($photo instanceof UploadedFile) {
            $params['photo'] = $photo;
        } else if (filter_var($photo, FILTER_VALIDATE_URL)) {
            $params['photo'] = $photo;
        } else if (file_exists($photo)) {
            $params['photo'] = fopen($photo, 'r');
        } else {
            $params['photo'] = $photo;
        }

        return $this->request('sendPhoto', $params);
    }

    /**
     * Send video
     */
    public function sendVideo($video, string $caption = '', array $options = []): array
    {
        $params = array_merge([
            'chat_id' => $this->chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ], $options);

        if ($video instanceof UploadedFile) {
            $params['video'] = $video;
        } else if (filter_var($video, FILTER_VALIDATE_URL)) {
            $params['video'] = $video;
        } else if (file_exists($video)) {
            $params['video'] = fopen($video, 'r');
        } else {
            $params['video'] = $video;
        }

        return $this->request('sendVideo', $params);
    }

    /**
     * Get file information
     */
    public function getFile(string $fileId): array
    {
        return $this->request('getFile', [
            'file_id' => $fileId
        ]);
    }

    /**
     * Download file
     */
    public function downloadFile(string $filePath, string $localPath): bool
    {
        try {
            $fileUrl = $this->fileUrl . $this->token . '/' . $filePath;
            $content = file_get_contents($fileUrl);
            return Storage::put($localPath, $content);
        } catch (\Exception $e) {
            \Log::error('Telegram file download error: ' . $e->getMessage());
            return false;
        }
    }
}
