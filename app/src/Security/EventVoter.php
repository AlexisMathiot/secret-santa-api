<?php

namespace App\Security;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventVoter extends Voter
{
    const DELETE = 'delete';
    const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {

        if (!in_array($attribute, [self::DELETE, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Event) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Event $event */
        $post = $subject;

        return match ($attribute) {
            self::DELETE => $this->canEdit($post, $user),
            self::EDIT => $this->canDelete($post, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(Event $event, User $user): bool
    {

        return $this->canDelete($event, $user);

    }

    private function canDelete(Event $event, User $user): bool
    {
        return $user === $event->getOrganizer() || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}