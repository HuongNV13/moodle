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

namespace captcha_mtcaptcha;

use core\http_client;
use core_captcha\captcha_provider;
use GuzzleHttp\Psr7\Request;
use html_writer;
use moodle_url;

/**
 * Provider.
 *
 * @package    captcha_mtcaptcha
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements captcha_provider {

    private static string $publickey;
    private static string $privatekey;
    private const SITE_VERIFY_URL = 'https://hcaptcha.com/siteverify';

    public static function get_provider($provider): captcha_provider {
        self::$publickey = get_config('captcha_mtcaptcha', 'sitekey');
        self::$privatekey = get_config('captcha_mtcaptcha', 'privatekey');
        return new self($provider);
    }

    public static function get_output_html(): string {
        $pubkey = self::$publickey;
        $jscode = "
        var mtcaptchaConfig = {
            'sitekey': '$pubkey'
        }
        (function(){var mt_service = document.createElement('script');mt_service.async = true;mt_service.src = 'https://service.mtcaptcha.com/mtcv1/client/mtcaptcha.min.js';(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(mt_service);
        var mt_service2 = document.createElement('script');mt_service2.async = true;mt_service2.src = 'https://service2.mtcaptcha.com/mtcv1/client/mtcaptcha2.min.js';(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(mt_service2);}) ();
        ";

        //$return = html_writer::script($jscode, '');

        $apicode = "\n<script>";
        $apicode .= $jscode;
        $apicode .= "</script>\n";

        $return = html_writer::div('', 'mtcaptcha');
        $return .= $apicode;

        return $return;
    }

    public static function verify_response($response): array {
        $client = new http_client();
        $response = $client->request(
            method: 'POST',
            uri: self::SITE_VERIFY_URL,
            options: [
                'query' => [
                    'secret' => self::$privatekey,
                    'response' => $response,
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
            $checkresponse['error'] = ['Robot verification failed, please try again.'];
        }

        return $checkresponse;
    }
}
