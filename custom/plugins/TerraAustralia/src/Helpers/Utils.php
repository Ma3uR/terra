<?php declare(strict_types=1);

if ( !function_exists('_d') ) {
    function _d($vals)
    {
        die('<pre>'.print_r($vals, true).'</pre>');
    }
}
