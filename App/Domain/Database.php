<?php
declare(strict_types=1);

namespace App\Domain;

use App\Models\User;
use App\Models\Video;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Limit;

class Database {
    protected \FaaPz\PDO\Database $database;

    function __construct()
    {
        $dsn = 'pgsql:host=localhost;port=4000;dbname=cinema_db;user=cinema_db;password=cinema_db';

        $this->database = new \FaaPz\PDO\Database($dsn);
    }

    public function getVideoToProcess() {
        $statement = $this->database->select(["*"])
            ->from("videos")->where(new Conditional("status", "=", "WAITING"));

        return $statement->execute()->fetch();
    }

    public function getUserById($userId): User {
        $statement = $this->database
            ->select(["*"])
            ->from("users")
            ->where(new Conditional("id", "=", $userId));

        $user = $statement->execute()->fetch();

        return new User($user["username"], $user["email"], $user["password"], $user["id"]);
    }

    public function markVideoAsReady($videoId) {
        $statement = $this->database
            ->update(["status" => "READY"])
            ->table("videos")
            ->where(new Conditional("id", "=", $videoId));

        $statement->execute();
    }
}