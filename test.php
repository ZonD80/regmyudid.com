<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function gunzip($zipped)
{
    $offset = 0;
    if (substr($zipped, 0, 2) == "\x1f\x8b")
        $offset = 2;
    if (substr($zipped, $offset, 1) == "\x08") {
        # file_put_contents("tmp.gz", substr($zipped, $offset - 2));
        return gzinflate(substr($zipped, $offset + 8));
    }
    return "Unknown Format";
}

var_dump((file_get_contents('provisions/198RegMyUDID.mobileprovision')));