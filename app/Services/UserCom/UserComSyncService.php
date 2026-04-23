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
        ];

        if ($participant->consented_marketing) {
            $attrs[config('munoludy.user_com.marketing_attribute_name', 'Marketing email')] = true;
        }

        $existing = $this->client->findUserByEmail($participant->email);

        if ($existing && isset($existing['id'])) {
            $userId = (string) $existing['id'];
            $this->fillMissingAttributes($userId, $existing, $attrs);
        } else {
            $created = $this->client->createUser($attrs);
            if (!$created || !isset($created['id'])) {
                return false;
            }
            $userId = (string) $created['id'];
        }

        $participant->user_com_user_id = $userId;
        $participant->save();

        return $this->client->subscribeToList($userId, $edition->user_com_list_id);
    }

    public function tagVoted(Participant $participant, ?string $tagName = null): bool
    {
        $tagName = $tagName ?: (string) config('munoludy.user_com.voted_tag_name', 'munoludy2026_voted');

        $userId = $participant->user_com_user_id;

        if (!$userId) {
            $existing = $this->client->findUserByEmail($participant->email);
            if (!$existing || !isset($existing['id'])) {
                return false;
            }
            $userId = (string) $existing['id'];
            $participant->update(['user_com_user_id' => $userId]);
        }

        return $this->client->addTag($userId, $tagName);
    }

    private function fillMissingAttributes(string $userId, array $existing, array $desired): void
    {
        $missing = [];

        foreach ($desired as $key => $value) {
            if ($key === 'email') {
                continue;
            }
            if ($this->attributeIsEmpty($existing, $key)) {
                $missing[$key] = $value;
            }
        }

        if (!empty($missing)) {
            $this->client->updateUser($userId, $missing);
        }
    }

    private function attributeIsEmpty(array $user, string $key): bool
    {
        if (array_key_exists($key, $user) && $user[$key] !== null && $user[$key] !== '') {
            return false;
        }

        foreach (['attributes', 'custom_attributes', 'user_attributes'] as $bucket) {
            if (isset($user[$bucket]) && is_array($user[$bucket])) {
                foreach ($user[$bucket] as $attr) {
                    if (is_array($attr) && ($attr['name'] ?? $attr['key'] ?? null) === $key) {
                        $v = $attr['value'] ?? null;
                        return $v === null || $v === '';
                    }
                }
                if (isset($user[$bucket][$key]) && $user[$bucket][$key] !== null && $user[$bucket][$key] !== '') {
                    return false;
                }
            }
        }

        return true;
    }
}
