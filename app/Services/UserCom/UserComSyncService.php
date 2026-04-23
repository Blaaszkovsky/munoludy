<?php

namespace App\Services\UserCom;

use App\Models\Participant;

class UserComSyncService
{
    public function __construct(private UserComClient $client) {}

    public function sync(Participant $participant): bool
    {
        $edition = $participant->edition;
        $attrs = [
            'email' => $participant->email,
            $edition->user_com_link_field => url('/glosowanie/'.$participant->link_hash),
            $edition->user_com_code_field => $participant->access_code,
            $edition->user_com_type_field => $participant->type->value,
        ];

        $existing = $this->client->findUserByEmail($participant->email);
        if ($existing) {
            $this->client->updateUser($existing['id'], $attrs);
            $userId = $existing['id'];
        } else {
            $created = $this->client->createUser($attrs);
            if (!$created) return false;
            $userId = $created['id'];
        }

        $participant->user_com_user_id = (string) $userId;
        $participant->save();

        return $this->client->subscribeToList((string) $userId, $edition->user_com_list_id);
    }
}
