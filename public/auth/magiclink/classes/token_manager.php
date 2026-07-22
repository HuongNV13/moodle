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

/**
 * Token manager for magic link authentication.
 *
 * @package auth_magiclink
 * @copyright 2026 Moodle Pty Ltd
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_magiclink;

defined('MOODLE_INTERNAL') || die();

/**
 * Magic link token manager.
 *
 * Handles generation, validation, and consumption of passwordless magic-link tokens.
 *
 * @package auth_magiclink
 * @copyright 2026 Moodle Pty Ltd
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_manager {
    /**
     * Max token requests per 60-second period per user (hardcoded rate limit).
     */
    const RATE_LIMIT_PER_MINUTE = 1;

    /**
     * Max token requests per rolling 60-minute window per user (hardcoded rate limit).
     */
    const RATE_LIMIT_PER_HOUR = 5;

    /**
     * Generate a new magic link token for a user.
     *
     * First checks rate limiting; if the user has exceeded limits, returns null.
     * Otherwise, generates a cryptographically random 64-character hex token,
     * computes its SHA-256 hash, stores the hash in the database, and returns
     * the raw (unhashed) token to the caller.
     *
     * @param \stdClass $user User object with id property.
     * @return ?string The raw token (64-char hex), or null if rate limited.
     */
    public static function generate_token(\stdClass $user): ?string {
        global $DB;

        if (self::is_rate_limited($user->id)) {
            return null;
        }

        // Generate a cryptographically random raw token (64 hex chars = 32 bytes).
        $rawtoken = bin2hex(random_bytes(32));

        // Hash the token for storage.
        $tokenhash = hash('sha256', $rawtoken);

        // Get expiry minutes from config; default to 15 minutes.
        $expiryminutes = (int) get_config('auth_magiclink', 'expiryminutes');
        if (!$expiryminutes) {
            $expiryminutes = 15;
        }

        // Get the remote IP address (may be false or empty).
        $remoteip = getremoteaddr();
        if (!$remoteip) {
            $remoteip = null;
        }

        // Prepare record for insertion.
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->tokenhash = $tokenhash;
        $record->timecreated = time();
        $record->expires = time() + ($expiryminutes * 60);
        $record->consumed = 0;
        $record->requestip = $remoteip;

        // Insert into database.
        $DB->insert_record('auth_magiclink_token', $record);

        // Return the raw token.
        return $rawtoken;
    }

    /**
     * Check if a user is currently rate limited.
     *
     * Enforces two rate limits:
     * 1. Max 1 token request per 60 seconds per user.
     * 2. Max 5 token requests per rolling 60-minute window per user.
     *
     * @param int $userid The user ID to check.
     * @return bool True if rate limited, false otherwise.
     */
    public static function is_rate_limited(int $userid): bool {
        global $DB;

        $now = time();
        $oneMinuteAgo = $now - 60;
        $oneHourAgo = $now - (60 * 60);

        // Check: max 1 token per 60 seconds.
        $recentCount = $DB->count_records_select(
            'auth_magiclink_token',
            'userid = ? AND timecreated > ?',
            [$userid, $oneMinuteAgo]
        );

        if ($recentCount >= self::RATE_LIMIT_PER_MINUTE) {
            return true;
        }

        // Check: max 5 tokens per rolling 60-minute window.
        $hourlyCount = $DB->count_records_select(
            'auth_magiclink_token',
            'userid = ? AND timecreated > ?',
            [$userid, $oneHourAgo]
        );

        if ($hourlyCount >= self::RATE_LIMIT_PER_HOUR) {
            return true;
        }

        return false;
    }

    /**
     * Validate and consume a raw token, returning the associated user.
     *
     * Looks up the token by its SHA-256 hash. If found, not yet consumed,
     * and not expired, marks it as consumed and returns the user record.
     * Subsequent calls with the same token return null (single-use).
     *
     * @param string $rawtoken The raw token string (64-char hex).
     * @return ?\stdClass The user record, or null if token is invalid/expired/consumed.
     */
    public static function validate_and_consume(string $rawtoken): ?\stdClass {
        global $DB;

        // Hash the provided token.
        $tokenhash = hash('sha256', $rawtoken);

        // Find the token record.
        $record = $DB->get_record_select(
            'auth_magiclink_token',
            'tokenhash = ? AND consumed = 0 AND expires > ?',
            [$tokenhash, time()]
        );

        if (!$record) {
            return null;
        }

        // Mark the token as consumed.
        $DB->set_field('auth_magiclink_token', 'consumed', 1, ['id' => $record->id]);

        // Fetch and return the user record.
        $user = $DB->get_record('user', ['id' => $record->userid]);

        return $user ?: null;
    }
}
