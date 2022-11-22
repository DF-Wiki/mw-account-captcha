<?php

require_once(__DIR__ . '/Tokens.php');

class ACRand {
    public static $seed = 0;
    public static function srand($seed) {
        self::$seed = $seed;
    }
    public static function rand($min, $max) {
        self::srand(self::$seed + 452930459);
        if ($max - $min + 1 == 0) return 0;
        return self::$seed % ($max - $min + 1) + $min;
    }
}

class AccountCaptcha {
    public static function generateToken($token) {
        global $ACConfigTokenFunctions;
        foreach ($ACConfigTokenFunctions as $func) {
            $token = call_user_func($func, $token);
        }
        return $token;
    }
    public static function fuzzToken($username) {
        $len = strlen($username);
        $sum = 0;
        for ($i = 0; $i < $len; $i++) {
            // Tokens should be ASCII, so this should work
            $sum += ord(substr($username, $i, 1));
        }
        ACRand::srand($sum);
        for ($i = 0; $i < ACRand::rand(1, 3); $i++) {
            $username[ACRand::rand(0, $len - 1)] = '+';
            $username[ACRand::rand(0, $len - 1)] = '\\';
            $username[ACRand::rand(1, $len - 2)] = ' ';  // Avoid spaces at beginning or end
        }
        $username = strrev($username) . "\\";
        return $username;
    }
    public static function getFormToken($request) {
        return md5($request->getAllHeaders()['USER-AGENT']);
    }
}

use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;

class AccountCaptchaAuthenticationRequest extends AuthenticationRequest {
    public $required = self::REQUIRED;

    public $actoken;

    public function getFieldInfo() {
        return [
            'token' => [
                'type' => 'string',
                'label' => wfMessage('accountcaptcha-token-desc'),
            ],
        ];
    }
}

class AccountCaptchaPreAuthenticationProvider extends AbstractPreAuthenticationProvider
{
    public function getAuthenticationRequests($action, array $options) {
        if ($action === AuthManager::ACTION_CREATE) {
            return [new AccountCaptchaAuthenticationRequest()];
        }
        return [];
    }

    public function testForAccountCreation($user, $creator, array $reqs) {
        $username = $user->getName();
        $req = AuthenticationRequest::getRequestByClass($reqs, AccountCaptchaAuthenticationRequest::class);
        if (!$req || $req->token !== AccountCaptcha::generateToken($username)) {
            return Status::newFatal(wfMessage('accountcaptcha-invalid-token'));
        }
        return Status::newGood();
    }
}
