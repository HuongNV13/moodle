<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace captcha_recaptchav2;

use core\http_client;
use core_captcha\captcha_provider;
use html_writer;

/**
 * Provider.
 *
 * @package    captcha_recaptchav2
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements captcha_provider {

    private static string $publickey;
    private static string $privatekey;
    private const RECAPTCHA_API_URL = 'https://www.recaptcha.net/recaptcha/api.js';
    private const RECAPTCHA_VERIFY_URL = 'https://www.recaptcha.net/recaptcha/api/siteverify';

    public static function get_provider($provider): captcha_provider {
        self::$publickey = get_config('captcha_recaptchav2', 'sitekey');
        self::$privatekey = get_config('captcha_recaptchav2', 'privatekey');
        return new self($provider);
    }

    public static function get_output_html(): string {
        $apiurl = self::RECAPTCHA_API_URL;
        $pubkey = self::$publickey;
        $compactmode = false;

        $jscode = "
        var recaptchacallback = function() {
            grecaptcha.render('recaptcha_element', {
              'sitekey' : '$pubkey'
            });
        }";
        $lang = self::recaptcha_lang(current_language());
        $apicode = "\n<script type=\"text/javascript\" ";
        $apicode .= "src=\"$apiurl?onload=recaptchacallback&render=explicit&hl=$lang\" async defer>";
        $apicode .= "</script>\n";

        $return = html_writer::script($jscode, '');
        $return .= html_writer::div('', 'recaptcha_element', [
            'id' => 'recaptcha_element',
            'data-size' => ($compactmode ? 'compact' : 'normal'),
        ]);
        $return .= $apicode;
        return $return;
    }

    public static function verify_response($response): array {
        $privkey = self::$privatekey;
        $remoteip = getremoteaddr();
        $verifyurl = self::RECAPTCHA_VERIFY_URL;
        // Check response - isvalid boolean, error string.
        $checkresponse = ['isvalid' => false, 'error' => 'check-not-started'];

        $client = new http_client();
        $response = $client->request(
            method: 'POST',
            uri: $verifyurl,
            options: [
                'query' => [
                    'secret' => $privkey,
                    'remoteip' => $remoteip,
                    'response' => $response
                ]
            ]
        );
        $responsebody = $response->getBody()->getContents();
        $decodedbody = json_decode($responsebody, false);

        if (isset($decodedbody->success) && $decodedbody->success === true) {
            $checkresponse['isvalid'] = true;
            $checkresponse['error'] = '';
        } else {
            $checkresponse['isvalid'] = false;
            $checkresponse['error'] = $decodedbody->{'error-codes'};
        }

       return $checkresponse;
    }

    private static function recaptcha_lang($lang = null): string {
        if (empty($lang)) {
            $lang = current_language();
        }

        $glang = $lang;
        switch ($glang) {
            case 'en':
                $glang = 'en-GB';
                break;
            case 'en_us':
                $glang = 'en';
                break;
            case 'zh_cn':
                $glang = 'zh-CN';
                break;
            case 'zh_tw':
                $glang = 'zh-TW';
                break;
            case 'fr_ca':
                $glang = 'fr-CA';
                break;
            case 'pt_br':
                $glang = 'pt-BR';
                break;
            case 'he':
                $glang = 'iw';
                break;
        }
        // For any language code that didn't change reduce down to the base language.
        if (($lang === $glang) and (strpos($lang, '_') !== false)) {
            list($glang, $trash) = explode('_', $lang, 2);
        }
        return $glang;
    }
}
