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

namespace core\local\redis;

use core\exception\coding_exception;

/**
 * Trait helper for Redis.
 *
 * @package    core
 * @copyright  2024 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait redis_helper_trait {

    /**
     * Connect to the Redis server using non cluster mode.
     * This will check the installed PHPRedis extension and connect to the Redis server using suitable options.
     *
     * @param string $host The Redis server host.
     * @param int $port The Redis server port.
     * @param int $timeout The connection timeout.
     * @param int $retry_interval The interval to wait before retrying to connect.
     * @param int $read_timeout The read timeout.
     * @param array|null $context The context for the connection.
     * @return \Redis|null The Redis connection object.
     */
    public function handle_redis_connection_non_cluster_mode(
        string $host,
        int $port,
        int $timeout,
        int $retry_interval,
        int $read_timeout,
        ?array $context,
    ): ?\Redis {
        // Get the defined variables in the current scope.
        $definedvars = get_defined_vars();
        // Get the allowed parameters for the connect method.
        $refmethod =  new \ReflectionMethod(\Redis::class, 'connect');
        $params = $refmethod->getParameters();
        // Map the parameter names.
        $allowedparamnames = array_map(function($item) {
            return $item->getName();
        }, $params);

        $processedargs = $this->process_and_map_arguments($allowedparamnames, $definedvars);
        $connection = new \Redis();
        call_user_func_array([$connection, 'connect'], $processedargs);
        return $connection;
    }

    /**
     * Connect to the Redis server using  cluster mode.
     * This will check the installed PHPRedis extension and connect to the Redis server using suitable options.
     *
     * @param string|null $name The name of the connection.
     * @param array $seeds The seeds of the Redis cluster.
     * @param int $timeout The connection timeout.
     * @param int $readTimeout The read timeout.
     * @param bool $persistent Whether to use a persistent connection.
     * @param string $auth The authentication password.
     * @param array|null $context The context for the connection.
     * @return \RedisCluster|null The Redis connection object.
     */
    public function handle_redis_connection_cluster_mode(
        ?string $name,
        array $seeds,
        int $timeout,
        int $readTimeout,
        bool $persistent,
        string $auth,
        ?array $context,
    ): ?\RedisCluster {
        // Get the defined variables in the current scope.
        $definedvars = get_defined_vars();
        // Get the allowed parameters for the constructor.
        $ref = new \ReflectionClass(\RedisCluster::class);
        $params = $ref->getConstructor()->getParameters();
        // Map the parameter names.
        $allowedparamnames = array_map(function($item) {
            return $item->getName();
        }, $params);

        $processedargs = $this->process_and_map_arguments($allowedparamnames, $definedvars);
        if (!isset($processedargs['name'])) {
            $processedargs['name'] = null;
        }
        $connection = new \RedisCluster(...$processedargs);
        return $connection;
    }

    /**
     * Process and map the arguments.
     *
     * @param array $allowedparamnames The allowed parameter names.
     * @param array $definedvars The defined variables.
     * @return array The processed arguments.
     */
    private function process_and_map_arguments(
        array $allowedparamnames,
        array $definedvars,
    ): array {
        $processedargs = [];
        foreach ($allowedparamnames as $allowedparamname) {
            if (isset($definedvars[$allowedparamname])) {
                // Add the parameter to the processed arguments.
                $processedargs[$allowedparamname] = $definedvars[$allowedparamname];
            }
        }
        return $processedargs;
    }
}
