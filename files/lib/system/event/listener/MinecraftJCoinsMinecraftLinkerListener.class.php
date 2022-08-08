<?php

namespace wcf\system\event\listener;

use wcf\data\user\minecraft\MinecraftUser;
use wcf\data\user\minecraft\MinecraftUserList;
use wcf\data\user\User;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\system\WCF;

/**
 * MinecraftUser acp edit listener class
 *
 * @author   xXSchrandXx
 * @license  Apache License 2.0 (https://www.apache.org/licenses/LICENSE-2.0)
 * @package  WoltLabSuite\Core\System\Event\Listener
 */
class MinecraftJCoinsMinecraftLinkerListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!MINECRAFT_JCOINS_ENABLED) {
            return;
        }
        $action = $eventObj->getActionName();
        if ($action == 'create') {
            $minecraftUser = $eventObj->getParameters()['data'];
            $userID = $minecraftUser['userID'];
            $user = new User($userID);
        } else if ($action == 'delete') {
            foreach ($eventObj->getObjects() as $object) {
                /** @var MinecraftUser */
                $minecraftUser = $object->getDecoratedObject();
                $user = new User($minecraftUser->userID);
            }
        }
        if (!$user) {
            return;
        }
        if ($user->minecraftUUIDs > 0) {
            UserJCoinsStatementHandler::getInstance()->create('de.xxschrandxx.wsc.minecraftJCoins.link', $user);
        } else {
            UserJCoinsStatementHandler::getInstance()->revoke('de.xxschrandxx.wsc.minecraftJCoins.link', $user);
        }
    }
}
