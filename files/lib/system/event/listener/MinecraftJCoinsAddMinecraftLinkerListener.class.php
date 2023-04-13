<?php

namespace wcf\system\event\listener;

use wcf\data\user\User;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;

/**
 * MinecraftJCoinsAddMinecraftLinker listener class
 *
 * @author   xXSchrandXx
 * @license  Apache License 2.0 (https://www.apache.org/licenses/LICENSE-2.0)
 * @package  WoltLabSuite\Core\System\Event\Listener
 */
#[\wcf\http\attribute\DisableXsrfCheck]
class MinecraftJCoinsAddMinecraftLinkerListener implements IParameterizedEventListener
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
            /** @var \wcf\data\user\minecraft\UserToMinecraftUser */
            $userToMinecraftUser = $eventObj->getParameters()['data'];
            $userID = $userToMinecraftUser['userID'];
            $user = new User($userID);
            if (!$user->userID) {
                return;
            }
            UserJCoinsStatementHandler::getInstance()->create('de.xxschrandxx.wsc.minecraftJCoins.link.add', $user);
        }
    }
}
