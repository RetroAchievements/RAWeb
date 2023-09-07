<?php

function IsMobileBrowser(): bool
{
    $mobile_browser = '0';

    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((mb_strpos(mb_strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') > 0)
            || (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE']))) {
            $mobile_browser++;
        }
    }

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', mb_strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $mobile_browser++;
        }

        $mobile_ua = mb_strtolower(mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = [
            'w3c ',
            'acs-',
            'alav',
            'alca',
            'amoi',
            'audi',
            'avan',
            'benq',
            'bird',
            'blac',
            'blaz',
            'brew',
            'cell',
            'cldc',
            'cmd-',
            'dang',
            'doco',
            'eric',
            'hipt',
            'inno',
            'ipaq',
            'java',
            'jigs',
            'kddi',
            'keji',
            'leno',
            'lg-c',
            'lg-d',
            'lg-g',
            'lge-',
            'maui',
            'maxo',
            'midp',
            'mits',
            'mmef',
            'mobi',
            'mot-',
            'moto',
            'mwbp',
            'nec-',
            'newt',
            'noki',
            'oper',
            'palm',
            'pana',
            'pant',
            'phil',
            'play',
            'port',
            'prox',
            'qwap',
            'sage',
            'sams',
            'sany',
            'sch-',
            'sec-',
            'send',
            'seri',
            'sgh-',
            'shar',
            'sie-',
            'siem',
            'smal',
            'smar',
            'sony',
            'sph-',
            'symb',
            't-mo',
            'teli',
            'tim-',
            'tosh',
            'tsm-',
            'upg1',
            'upsi',
            'vk-v',
            'voda',
            'wap-',
            'wapa',
            'wapi',
            'wapp',
            'wapr',
            'webc',
            'winw',
            'winw',
            'xda ',
            'xda-',
        ];

        if (in_array($mobile_ua, $mobile_agents)) {
            $mobile_browser++;
        }
    }

    // if (strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {
    //     $mobile_browser++;
    // }

    if (isset($_SERVER['HTTP_USER_AGENT']) && (mb_strpos(mb_strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') > 0)) {
        $mobile_browser = 0;
    }

    if ($mobile_browser > 0) {
        // do something
        return true;
    }

    // do something else
    return false;
}
