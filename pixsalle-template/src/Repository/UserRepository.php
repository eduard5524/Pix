<?php

declare(strict_types=1);

namespace Salle\PixSalle\Repository;

use Salle\PixSalle\Model\User;

interface UserRepository
{
    public function createUser(User $user): void;
    public function getUserByEmail(string $email);
}
