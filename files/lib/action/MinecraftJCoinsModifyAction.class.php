<?php

namespace wcf\action;

use Laminas\Diactoros\Response\JsonResponse;
use wcf\data\user\minecraft\MinecraftUserList;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\flood\FloodControl;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;

/**
 * MinecraftJCoinsModifyAction action class
 *
 * @author   xXSchrandXx
 * @license  Apache License 2.0 (https://www.apache.org/licenses/LICENSE-2.0)
 * @package  WoltLabSuite\Core\Action
 */
class MinecraftJCoinsModifyAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public $neededModules = ['MINECRAFT_JCOINS_ENABLED','MINECRAFT_JCOINS_KEY', 'MODULE_JCOINS'];

    private string $d = 'de.xxschrandxx.wsc.minecraftJCoins.verify';

    private string $uuid = '';

    private int $amount = 0;

    /**
     * @inheritDoc
     */
    public function __run()
    {
        if (empty($_SERVER['HTTPS'])) {
            return $this->send('SSL Certificate Required', 496, false);
        }

        // Flood control
        if (MINECRAFT_JCOINS_FLOODGATE_MAXREQUESTS > 0) {
            FloodControl::getInstance()->registerContent($this->d);

            $secs = MINECRAFT_JCOINS_FLOODGATE_RESETTIME * 60;
            $time = \ceil(TIME_NOW / $secs) * $secs;
            $data = FloodControl::getInstance()->countContent($this->d, new \DateInterval('PT' . MINECRAFT_JCOINS_FLOODGATE_RESETTIME . 'M'), $time);
            if ($data['count'] > MINECRAFT_JCOINS_FLOODGATE_MAXREQUESTS) {
                return $this->send('Too Many Requests.', 429, false, $time - TIME_NOW);
            }
        }

        return parent::__run();
    }

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        // validate key
        if (!array_key_exists('key', $_POST)) {
            return $this->send('Wrong key.', 401);
        }
        if (!is_string($_POST['key'])) {
            return $this->send('Wrong key.', 401);
        }
        if (!hash_equals(MINECRAFT_JCOINS_KEY, $_POST['key'])) {
            return $this->send('Wrong key.', 401);
        }

        // validate uuid
        if (!array_key_exists('uuid', $_POST)) {
            return $this->send('Bad Request.', 400);
        }
        if (!is_string($_POST['uuid'])) {
            return $this->send('Bad Request.', 400);
        }
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $_POST['uuid'])) {
            return $this->send('Bad Request.', 400);
        }
        $this->uuid = $_POST['uuid'];

        // validate amount
        if (!array_key_exists('amount', $_POST)) {
            return $this->send('Bad Request.', 400);
        }
        $this->amount = intval($_POST['amount']);
        if ($this->amount === 0) {
            return $this->send('Bad Request.', 400);
        }

        parent::readParameters();
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            parent::execute();
        } catch (PermissionDeniedException | IllegalLinkException $e) {
            return $this->send($e->getMessage(), $e->getCode());
        }

        /** @var User */
        $user = null;
        try {
            $user = $this->getUser($this->uuid);
        } catch (UserInputException $e) {
            return $this->send($e->getMessage(), $e->getCode());
        }

        // Check if user can earn jcoins
        /** @var UserProfile */
        $userProfile = new UserProfile($user);
        if (!$userProfile->getPermission('user.jcoins.canEarn')) {
            return $this->send('User can\'t earn jCoins', 500);
        }

        if (!JCOINS_ALLOW_NEGATIVE && ($user->jCoinsAmount + $this->amount) < 0) {
            return $this->send('User can\'t go negative', 500);
        }
        $parameters = [
            'amount' => $this->amount
        ];
        UserJCoinsStatementHandler::getInstance()->create('de.xxschrandxx.wsc.minecraftJCoins.modify', $user, $parameters);
        return $this->send('OK', 200, true);
    }

    private function getUser(string $uuid): User
    {
        $minecraftUserList = new MinecraftUserList();
        $minecraftUserList->getConditionBuilder()->add('minecraftUUID = ?', [$uuid]);
        $minecraftUserList->readObjects();
        $minecraftUsers = $minecraftUserList->getObjects();
        if (empty($minecraftUsers)) {
            throw new UserInputException();
        }
        return new User(array_values($minecraftUsers)[0]->userID);
    }

    private function send($status, $statusCode, $valid = false, $retryAfter = null): JsonResponse
    {
        if ($statusCode < 100 && $statusCode > 599) {
            $statusCode = 400;
        }
        $data = [
            'status' => $status,
            'statusCode' => $statusCode,
            'valid' => $valid
        ];
        $headers = [];
        if (is_int($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
        }
        return new JsonResponse($data, $statusCode, $headers);
    }
}
