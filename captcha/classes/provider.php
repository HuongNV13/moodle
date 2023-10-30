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

namespace core_captcha;

class provider {
    private captcha_provider|null $provider = null;
    public function __construct(
        private string $component,
    ) {
        $providerclass = $this->get_classname_for_provider($this->component);
        if (!class_exists($providerclass)) {
            throw new \moodle_exception('captchaproviderclassnotfound', 'core_captcha', '', $providerclass);
        }
        $this->provider = $providerclass::get_provider($this);
    }

    private function get_classname_for_provider(string $component): string {
        return "{$component}\\provider";
    }

    public function get_output_html(): string {
        return $this->provider->get_output_html();
    }

    public function verify_response($response): array {
        return $this->provider->verify_response($response);
    }
}
