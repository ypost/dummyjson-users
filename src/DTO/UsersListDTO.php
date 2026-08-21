<?php

namespace YPost\DummyJsonUsers\DTO;

use JsonSerializable;

/**
 * Represents a list of users with limit, skip and total users count
 */
class UsersListDTO implements JsonSerializable
{
    /**
     * @param list<UserDTO> $users
     */
    public function __construct(
        public array $users,
        public int $total,
        public int $limit,
        public int $skip,
    ) {
    }

    /**
     * @return array{
     *     users: list<array{
     *         id: int,
     *         firstName: string,
     *         lastName: string,
     *         email: string,
     *     }>,
     *     total: int,
     *     limit: int,
     *     skip: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'users' => array_map(
                fn(UserDTO $user): array => $user->toArray(),
                $this->users,
            ),
            'total' => $this->total,
            'limit' => $this->limit,
            'skip' => $this->skip,
        ];
    }

    /**
     * @return array{
     *     users: list<array{
     *         id: int,
     *         firstName: string,
     *         lastName: string,
     *         email: string,
     *     }>,
     *     total: int,
     *     limit: int,
     *     skip: int,
     * }
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
