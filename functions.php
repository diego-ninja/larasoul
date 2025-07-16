<?php

use Illuminate\Support\Facades\Session;

if (! function_exists('verisoul_session')) {
    function verisoul_session(): ?string
    {
        return Session::get(config('larasoul.session.verisoul_session_id'));
    }
}
