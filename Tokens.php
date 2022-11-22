<?php
/*
This file attempts to load ACTokens.php from several possible locations.
ACTokens.php must define a class `ACTokens` that implements the `IAccountCaptchaTokenGenerator` interface.
*/

interface IAccountCaptchaTokenGenerator {
    public static function generateToken(string $username): string;
}

foreach ([
    "$IP/extensions/ACTokens.php",
    __DIR__ . "/../ACTokens.php",
    __DIR__ . "/../../extensions/ACTokens.php",
] as $actokens_path) {
    if (file_exists($actokens_path)) {
        require_once($actokens_path);
        break;
    }
}

if (!class_exists("ACTokens")) {
    die("AccountCaptcha configuration error: ACTokens class not defined");
}

if (!isset(class_implements("ACTokens")["IAccountCaptchaTokenGenerator"])) {
    die("AccountCaptcha configuration error: ACTokens class does not implement IAccountCaptchaTokenGenerator");
}
