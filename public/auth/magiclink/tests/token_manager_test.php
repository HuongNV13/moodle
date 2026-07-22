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

namespace auth_magiclink;

/**
 * Tests for magic link token manager.
 *
 * @package     auth_magiclink
 * @copyright   2026 Moodle Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \auth_magiclink\token_manager
 */
final class token_manager_test extends \advanced_testcase {
    /**
     * Test that generate_token() returns a 64-char hex string and creates
     * a database row with the correct tokenhash.
     */
    public function test_generate_token_returns_raw_token_and_stores_hash(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        // Generate a token.
        $rawtoken = token_manager::generate_token($user);

        // Verify the raw token is 64 hex characters.
        $this->assertIsString($rawtoken);
        $this->assertEquals(64, strlen($rawtoken));
        $this->assertTrue(ctype_xdigit($rawtoken), 'Token should be a valid hex string');

        // Verify a row was inserted into the database.
        $record = $DB->get_record('auth_magiclink_token', ['userid' => $user->id]);
        $this->assertNotNull($record);

        // Verify the tokenhash is the SHA-256 hash of the raw token.
        $expectedhash = hash('sha256', $rawtoken);
        $this->assertEquals($expectedhash, $record->tokenhash);

        // Verify the raw token is NOT stored in the database.
        $this->assertNotEquals($rawtoken, $record->tokenhash);
    }

    /**
     * Test that validate_and_consume() with a valid raw token returns
     * the correct user record and marks the token as consumed.
     */
    public function test_validate_and_consume_returns_user_and_marks_consumed(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        // Generate a token.
        $rawtoken = token_manager::generate_token($user);

        // Validate and consume the token.
        $result = token_manager::validate_and_consume($rawtoken);

        // Verify the returned user record matches.
        $this->assertIsObject($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->username, $result->username);

        // Verify the token is now marked as consumed in the database.
        $record = $DB->get_record_select(
            'auth_magiclink_token',
            'tokenhash = ?',
            [hash('sha256', $rawtoken)]
        );
        $this->assertEquals(1, $record->consumed);
    }

    /**
     * Test that validate_and_consume() returns null on second call
     * (single-use enforcement).
     */
    public function test_validate_and_consume_single_use(): void {
        $this->resetAfterTest(true);

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        // Generate a token.
        $rawtoken = token_manager::generate_token($user);

        // First call should succeed.
        $result1 = token_manager::validate_and_consume($rawtoken);
        $this->assertNotNull($result1);

        // Second call with the same token should return null.
        $result2 = token_manager::validate_and_consume($rawtoken);
        $this->assertNull($result2);
    }

    /**
     * Test that validate_and_consume() returns null for an expired token.
     */
    public function test_validate_and_consume_with_expired_token(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        // Manually insert an expired token record.
        $rawtoken = bin2hex(random_bytes(32));
        $tokenhash = hash('sha256', $rawtoken);
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->tokenhash = $tokenhash;
        $record->timecreated = time() - 3600;  // 1 hour ago.
        $record->expires = time() - 1;  // Expired 1 second ago.
        $record->consumed = 0;
        $record->requestip = null;
        $DB->insert_record('auth_magiclink_token', $record);

        // Attempt to validate the expired token.
        $result = token_manager::validate_and_consume($rawtoken);

        // Should return null because the token is expired.
        $this->assertNull($result);
    }

    /**
     * Test that validate_and_consume() returns null for an unknown/garbage token.
     */
    public function test_validate_and_consume_with_garbage_token(): void {
        $this->resetAfterTest(true);

        // Try to validate a random token that was never generated.
        $garbagetoken = bin2hex(random_bytes(32));
        $result = token_manager::validate_and_consume($garbagetoken);

        // Should return null because no matching token exists.
        $this->assertNull($result);
    }

    /**
     * Test rate limiting: calling generate_token() twice in immediate succession
     * for the same user causes the second call to return null.
     */
    public function test_rate_limiting_one_per_minute(): void {
        $this->resetAfterTest(true);

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        // First token generation should succeed.
        $token1 = token_manager::generate_token($user);
        $this->assertIsString($token1);

        // Second token generation immediately after should be rate limited.
        $token2 = token_manager::generate_token($user);
        $this->assertNull($token2);
    }

    /**
     * Test rate limiting: calling generate_token() more than 5 times within
     * a 60-minute window returns null on the 6th attempt.
     */
    public function test_rate_limiting_five_per_hour(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a test user.
        $user = $this->getDataGenerator()->create_user();

        // Manually insert 5 token records spread throughout the hour (outside the 60-second window).
        $now = time();
        for ($i = 0; $i < 5; $i++) {
            $record = new \stdClass();
            $record->userid = $user->id;
            $record->tokenhash = hash('sha256', "token_$i");
            $record->timecreated = $now - (120 + ($i * 100));  // Spaced beyond 60 seconds apart.
            $record->expires = $now + 3600;
            $record->consumed = 0;
            $record->requestip = null;
            $DB->insert_record('auth_magiclink_token', $record);
        }

        // Now attempt to generate a new token; should be rate limited (5 per hour).
        $token = token_manager::generate_token($user);
        $this->assertNull($token);
    }

    /**
     * Test that validate_and_consume() returns null if the user record
     * no longer exists (deleted account).
     */
    public function test_validate_and_consume_missing_user(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a test user and generate a token.
        $user = $this->getDataGenerator()->create_user();
        $rawtoken = token_manager::generate_token($user);

        // Delete the user account.
        $DB->delete_records('user', ['id' => $user->id]);

        // Attempt to validate the token.
        $result = token_manager::validate_and_consume($rawtoken);

        // Should return null because the user no longer exists.
        $this->assertNull($result);
    }
}
