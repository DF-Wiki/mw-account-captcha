<?php
class AccountCaptcha {
    public static function generateToken($token) {
        global $ACTokenFunctions;
        foreach ($ACTokenFunctions as $func) {
            $token = call_user_func($func, $token);
        }
        return $token;
    }
}
class AccountCaptchaHooks {
    public static function UserCreateForm(&$form) {
        $form->addInputItem( 'acToken', '', 'text', 'accountcaptcha-token-desc' );
        return true;
    }
    public static function AbortNewAccount($user, $message) {
        global $wgRequest;
        $token = $wgRequest->getText('acToken');
        $username = $wgRequest->getText('wpName');
        if (AccountCaptcha::generateToken($username) == $token) {
            return true;
        }
        else {
            $msg = wfMsg('accountcaptcha-invalid-token');
            return false;
        }
    }
}
