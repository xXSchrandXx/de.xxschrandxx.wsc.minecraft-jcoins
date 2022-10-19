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

    private int $amount = 0;

    /**
     * @inheritDoc
     */
    public function readParameters(): ?JsonResponse
    {
        $result = parent::readParameters();

        if ($result !== null) {
            return $result;
        }

        // validate amount
        if (!array_key_exists('amount', $this->getJSON())) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. \'amount\' not set.', 400);
            } else {
                return $this->send('Bad Request.', 400);
            }
        }
        if (!is_int($this->getData('amount'))) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. \'amount\' no int.', 400);
            } else {
                return $this->send('Bad Request.', 400);
            }
        }
        $this->amount = $this->getData('amount');

        // check linked
        $this->user = MinecraftLinkerUtil::getUser($this->uuid);
        if (!isset($this->user)) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. \'uuid\' is not linked.', 400);
            } else {
                return $this->send('Bad request.', 400);
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(): ?JsonResponse
    {
        parent::execute();

        // Check if user can earn jcoins
        /** @var UserProfile */
        $userProfile = new UserProfile($this->user);
        if (!$userProfile->getPermission('user.jcoins.canEarn')) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. User can\'t earn jCoins.', 400);
            } else {
                return $this->send('Bad request.', 400);
            }
        }

        if (!JCOINS_ALLOW_NEGATIVE && ($this->user->jCoinsAmount + $this->amount) < 0) {
            if (ENABLE_DEBUG_MODE) {
                return $this->send('Bad Request. User can\'t go negative.', 400);
            } else {
                return $this->send('Bad request.', 400);
            }
        }
        $parameters = [
            'amount' => $this->amount
        ];
        UserJCoinsStatementHandler::getInstance()->create('de.xxschrandxx.wsc.minecraftJCoins.modify', $this->user, $parameters);
        return $this->send();
    }
}
