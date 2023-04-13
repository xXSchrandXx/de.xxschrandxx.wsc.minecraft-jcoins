<?php

namespace wcf\system\event\listener;

use BadMethodCallException;
use wcf\data\user\minecraft\UserToMinecraftUserList;
use wcf\data\user\User;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;

/**
 * MinecraftJCoinsRemoveMinecraftLinker listener class
 *
 * @author   xXSchrandXx
 * @license  Apache License 2.0 (https://www.apache.org/licenses/LICENSE-2.0)
 * @package  WoltLabSuite\Core\System\Event\Listener
 */
#[\wcf\http\attribute\DisableXsrfCheck]
class MinecraftJCoinsRemoveMinecraftLinkerListener implements IParameterizedEventListener
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
        if ($action == 'delete') {
            foreach ($eventObj->getObjects() as $object) {
                /** @var \wcf\data\user\minecraft\MinecraftUser */
                $minecraftUser = $object->getDecoratedObject();
                $userToMinecraftUserList = new UserToMinecraftUserList();
                $userToMinecraftUserList->getConditionBuilder()->add('minecraftUserID = ?', [$minecraftUser->getObjectID()]);
                $userToMinecraftUserList->readObjects();
                try {
                    /** @var \wcf\data\user\minecraft\UserToMinecraftUser */
                    $userToMinecraftUser = $userToMinecraftUserList->getSingleObject();
                } catch (BadMethodCallException $e) {
                    return;
                }
                $userID = $userToMinecraftUser->getUserID();
                $user = new User($userID);
                if (!$user->userID) {
                    continue;
                }
                UserJCoinsStatementHandler::getInstance()->create('de.xxschrandxx.wsc.minecraftJCoins.link.remove', $user);
            }
        }
    }
}
