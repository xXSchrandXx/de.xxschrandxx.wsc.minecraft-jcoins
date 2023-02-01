<?php

namespace wcf\action;

use Laminas\Diactoros\Response\JsonResponse;
use wcf\data\user\UserProfile;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\util\MinecraftLinkerUtil;

/**
 * MinecraftJCoinsModifyAction action class
 *
 * @author   xXSchrandXx
 * @license  Apache License 2.0 (https://www.apache.org/licenses/LICENSE-2.0)
 * @package  WoltLabSuite\Core\Action
 */
class MinecraftJCoinsModifyAction extends AbstractMinecraftLinkerAction
{
    /**
     * @inheritDoc
     */
    public $neededModules = ['MINECRAFT_JCOINS_ENABLED', 'MODULE_JCOINS'];

    /**
     * @inheritDoc
     */
    public function validateParameters($parameters, &$response): void
    {
        parent::validateParameters($parameters, $response);
        if ($response instanceof JsonResponse) {
            return;
        }

        // validate amount
        if (!array_key_exists('amount', $parameters)) {
            if (ENABLE_DEBUG_MODE) {
                $response = $this->send('Bad Request. \'amount\' not set.', 400);
            } else {
                $response = $this->send('Bad Request.', 400);
            }
            return;
        }
        if (!is_int($parameters['amount'])) {
            if (ENABLE_DEBUG_MODE) {
                $response = $this->send('Bad Request. \'amount\' no int.', 400);
            } else {
                $response = $this->send('Bad Request.', 400);
            }
            return;
        }
    }

    /**
     * @inheritDoc
     */
    public function execute($parameters): JsonResponse
    {
        // check linked
        $user = MinecraftLinkerUtil::getUser($parameters['uuid']);
        if (!isset($user)) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. \'uuid\' is not linked.', 400);
            } else {
                return $this->send('Bad request.', 400);
            }
        }

        // Check if user can earn jcoins
        /** @var UserProfile */
        $userProfile = new UserProfile($user);
        if (!$userProfile->getPermission('user.jcoins.canEarn')) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. User can\'t earn jCoins.', 400);
            } else {
                return $this->send('Bad request.', 400);
            }
        }

        if (!JCOINS_ALLOW_NEGATIVE && ($user->jCoinsAmount + $parameters['amount']) < 0) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. User can\'t go negative.', 400);
            } else {
                return $this->send('Bad request.', 400);
            }
        }
        $parameters = [
            'amount' => $parameters['amount']
        ];
        UserJCoinsStatementHandler::getInstance()->create('de.xxschrandxx.wsc.minecraftJCoins.modify', $user, $parameters);
        return $this->send();
    }
}
