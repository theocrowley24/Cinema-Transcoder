<?php
declare(strict_types=1);

namespace App\Models;

class User {
    public int $id;
    public string $username;
    public string $email;
    public string $password;

    function __construct(string $username, string $email, string $password, int $id = -1) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
    }
}