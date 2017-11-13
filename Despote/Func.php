<?php

function c($name)
{
    $conf = require PATH_CONFIG . 'Config.php';

    return isset($conf[$name]) ? $conf[$name] : null;
}
