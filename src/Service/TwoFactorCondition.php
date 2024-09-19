<?php

namespace App\Service;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;

class TwoFactorCondition implements TwoFactorConditionInterface
{
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        /** @var User $user */
        $user = $context->getUser();
        if($user->isOtpEnabled()){
            return true;
        }
        return false;
    }
}

