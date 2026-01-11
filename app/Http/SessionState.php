<?php

namespace App\Http;

final class SessionState
{
    public static function setBackUrl(string $url): void
    {
        $_SESSION['st_back_url'] = $url;
    }

    public static function setPaysiteTitle(string $title): void
    {
        $_SESSION['st_paysite_title'] = $title;
    }
}
