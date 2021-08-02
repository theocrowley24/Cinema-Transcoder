<?php
declare(strict_types=1);

namespace App\Models;

class Video {
    public int $id;
    public string $fileName;
    public int $userId;
    public User $user;
    public string $title;
    public string $description;
    public string $uploadDate;
    public int $inactive;
    public string $status;

    function __construct(string $fileName, int $userId, User $user, string $title, string $description, string $uploadDate, int $inactive, string $status, int $id = -1) {
        $this->id = $id;
        $this->fileName = $fileName;
        $this->userId = $userId;
        $this->user = $user;
        $this->title = $title;
        $this->description = $description;
        $this->uploadDate = $uploadDate;
        $this->inactive = $inactive;
        $this->status = $status;
    }
}