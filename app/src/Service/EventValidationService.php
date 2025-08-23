<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateEvent(Event $event): array
    {
        $errors = $this->validator->validate($event);

        if ($errors->count() > 0) {
            $messages = [];
            foreach ($errors as $violation) {
                $messages[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return [
                'isValid' => false,
                'errors' => $messages
            ];
        }

        return [
            'isValid' => true,
            'errors' => []
        ];
    }

    public function validateEventAccess(Event $event, User $user, string $permission): bool
    {
        // Cette méthode pourrait être étendue avec une logique plus complexe
        switch ($permission) {
            case 'edit':
                return $event->getOrganizer() === $user;
            case 'delete':
                return $event->getOrganizer() === $user;
            case 'view':
                return $event->getUsers()->contains($user) || $event->getOrganizer() === $user;
            default:
                return false;
        }
    }
}
