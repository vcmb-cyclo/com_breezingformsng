<?php
    /**
 *    Copyright (c) Arturas Molcanovas <a.molcanovas@gmail.com> 2016.
 *    https://github.com/aloframework/common
 *
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 */

    namespace AloFramework\Common;

    use Traversable;

    /**
     * Static common component container
     *
     * @author Art <a.molcanovas@gmail.com>
     * @since  1.3 getUniqid(), asciiRand(), isRegularRequest() added<br/>
     *         1.2 getFingerprint(), isTraversable(), unXss() added<br/>
     *         1.1 ifundefined() added
     */
    abstract class Alo {

        /**
         * Defines the ascii charset subset as "the entire set"
         *
         * @var int
         */
        const ASCII_ALL = 0;

        /**
         * Defines the ascii charset subset as "only alphanumeric"
         *
         * @var int
         */
        const ASCII_ALPHANUM = 1;

        /**
         * Defines the ascii charset subset as "only non-alphanumeric"
         *
         * @var int
         */
        const ASCII_NONALPHANUM = 2;

        /**
         * Array of ASCII alphanumeric characters
         *
         * @var array
         */
        private static $asciiAlphanum = ['a',
                                         'b',
                                         'c',
                                         'd',
                                         'e',
                                         'f',
                                         'g',
                                         'h',
                                         'i',
                                         'j',
                                         'k',
                                         'l',
                                         'm',
                                         'n',
                                         'o',
                                         'p',
                                         'q',
                                         'r',
                                         's',
                                         't',
                                         'u',
                                         'v',
                                         'w',
                                         'x',
                                         'y',
                                         'z',
                                         'A',
                                         'B',
                                         'C',
                                         'D',
                                         'E',
                                         'F',
                                         'G',
                                         'H',
                                         'I',
                                         'J',
                                         'K',
                                         'L',
                                         'M',
                                         'N',
                                         'O',
                                         'P',
                                         'Q',
                                         'R',
                                         'S',
                                         'T',
                                         'U',
                                         'V',
                                         'W',
                                         'X',
                                         'Y',
                                         'Z',
                                         0,
                                         1,
                                         2,
                                         3,
                                         4,
                                         5,
                                         6,
                                         7,
                                         8,
                                         9];
        /**
         * The rest of the ASCII charset
         *
         * @var array
         */
        private static $asciNonAlphanum = [' ',
                                           '!',
                                           '"',
                                           '#',
                                           '$',
                                           '%',
                                           '\'',
                                           '(',
                                           ')',
                                           '*',
                                           '+',
                                           ',',
                                           '.',
                                           '/',
                                           ':',
                                           ';',
                                           '<',
                                           '=',
                                           '>',
                                           '?',
                                           '@',
                                           '[',
                                           '\\',
                                           ']',
                                           '^',
                                           '_',
                                           '`',
                                           '-',
                                           '{',
                                           '|',
                                           '}',
                                           '~'];

        /**
         * Includes a file if it exists
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $path Path to the file
         *
         * @return bool True if the file is found, false if not
         */
        public static function includeIfExists($path) {
            if (self::isIncludable($path)) {
                include $path;

                return true;
            }

            return false;
        }

        /**
         * Generates a string of random ASCII characters
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param int $length The length of the string
         * @param int $subset Which subset to use - see class' ASCII_* constants
         *
         * @return string
         * @since  1.3
         */
        public static function asciiRand($length, $subset = self::ASCII_ALL) {
            switch ($subset) {
                case self::ASCII_ALPHANUM:
                    $subset = self::$asciiAlphanum;
                    break;
                case self::ASCII_NONALPHANUM:
                    $subset = self::$asciNonAlphanum;
                    break;
                default:
                    $subset = array_merge(self::$asciiAlphanum, self::$asciNonAlphanum);
            }

            $count = count($subset) - 1;

            $r = '';

            for ($i = 0; $i < $length; $i++) {
                $r .= $subset[mt_rand(0, $count)];
            }

            return $r;
        }

        /**
         * Generates a unique identifier
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $hash      Hash algorithm
         * @param string $prefix    Prefix for the identifier
         * @param int    $entropy   Number of pseudo bytes used in entropy
         * @param bool   $rawOutput When set to true, outputs raw binary data. false outputs lowercase hexits.
         *
         * @return string
         * @see    https://secure.php.net/manual/en/function.hash.php
         * @see    https://secure.php.net/manual/en/function.openssl-random-pseudo-bytes.php
         * @since  1.3.3 Default $entropy value set to 10000, a warning is triggered if openssl_random_pseudo_bytes is
         * unable to locate a cryptographically strong algorithm.<br/>
         *         1.3
         * @codeCoverageIgnore
         */
        public static function getUniqid($hash = 'sha256', $prefix = '', $entropy = 10000, $rawOutput = false) {
            $str = mt_rand(~PHP_INT_MAX, PHP_INT_MAX) . json_encode([$_COOKIE,
                                                                     $_REQUEST,
                                                                     $_FILES,
                                                                     $_ENV,
                                                                     $_GET,
                                                                     $_POST,
                                                                     $_SERVER]) . uniqid($prefix, true) .
                   self::asciiRand($entropy, self::ASCII_ALL);

            if (function_exists('\openssl_random_pseudo_bytes')) {
                $algoStrong = null;
                $str .= \openssl_random_pseudo_bytes($entropy, $algoStrong);

                if ($algoStrong !== true) {
                    trigger_error('Please update your openssl & PHP libraries. openssl_random_pseudo_bytes was unable' .
                                  ' to locate a cryptographically strong algorithm.',
                                  E_USER_WARNING);
                }
            } else {
                trigger_error('The openssl extension is not enabled, therefore the unique ID is not ' .
                              'cryptographically secure.',
                              E_USER_WARNING);
            }

            return hash($hash, $str, $rawOutput);
        }

        /**
         * Checks if the path is includable
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $path The path
         *
         * @return bool
         */
        private static function isIncludable($path) {
            return file_exists($path) && is_file($path);
        }

        /**
         * include_once() a file if it exists
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $path Path to the file
         *
         * @return bool True if the file is found, false if not
         */
        public static function includeOnceIfExists($path) {
            if (self::isIncludable($path)) {
                include_once $path;

                return true;
            }

            return false;
        }

        /**
         * Checks if we're dealing with a CLI request
         *
         * @author Art <a.molcanovas@gmail.com>
         * @return bool
         */
        public static function isCliRequest() {
            return PHP_SAPI == 'cli' || defined('STDIN');
        }

        /**
         * Checks if the request is non-ajax and non-CLI
         *
         * @author Art <a.molcanovas@gmail.com>
         * @return bool
         * @since  1.3
         */
        public static function isRegularRequest() {
            return !self::isAjaxRequest() && !self::isCliRequest();
        }

        /**
         * Returns $var if it's set, null if it's not
         *
         * @param mixed $var Reference to the variable
         *
         * @return mixed|null $var if it's set, null if it's not
         */
        public static function get(&$var) {
            return isset($var) ? $var : null;
        }

        /**
         * Returns $var if it's set and evaluates as true (a non-empty string, non-0 int etc), null otherwise
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param mixed $var Reference to the variable
         *
         * @return mixed|null The var or null
         */
        public static function nullget(&$var) {
            return self::get($var) ? $var : null;
        }

        /**
         * Returns $var if it's set $planB if it's not
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param mixed $var        Reference to the main variable
         * @param mixed $planB      What to return if $var isn't available
         * @param bool  $useNullget If set to true, will use self::nullget(), otherwise will use self::get() to
         *                          determinewhether $var is set
         *
         * @return mixed $var if available, $planB if not
         */
        public static function ifnull(&$var, $planB, $useNullget = false) {
            $v = $useNullget ? self::nullget($var) : self::get($var);

            return $v !== null ? $v : $planB;
        }

        /**
         * Returns the value of the constant with the name of $const if it's defined, $planB if it's not
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $const Constant name
         * @param mixed  $planB What to return if $const isn't defined
         *
         * @return mixed
         * @since  1.1
         */
        public static function ifundefined($const, $planB) {
            return defined($const) ? constant($const) : $planB;
        }

        /**
         * Checks if the request was made via AJAX
         *
         * @author Art <a.molcanovas@gmail.com>
         * @return bool
         */
        public static function isAjaxRequest() {
            return self::get($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHttpRequest';
        }

        /**
         * Returns a hashed browser fingerprint
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $hashAlgo Hash algorithm to use
         *
         * @return string
         * @since  1.2
         */
        public static function getFingerprint($hashAlgo = 'sha256') {
            return hash($hashAlgo,
                        '#QramRAN7*s%6n%@x*53jVVPsnrz@5MY$49o^mhJ8HqY%3a09yJnSWg9lBl$O4CKUb&&S%EgYBjhUZEbhquw$keCjR6I%zMcA!Qr' .
                        self::get($_SERVER['HTTP_USER_AGENT']) .
                        'OE2%fWaZh4jfZPiNXKmHfUw6ok6Z0s#PInaFa8&o#xh#nVyaFaXHPUcv^2y579PnYr5AOs6Zqb!QTAZCgRR968*%QxKc^XNuYYM8' .
                        self::get($_SERVER['HTTP_DNT']) .
                        '%CwyJJ^GAooDl&o0mc%7zbWlD^6tWoNSN&m3cKxWLP8kiBqO!j2PM5wACzyOoa^t7AEJ#FlDT!BMtD$luy%2iZejMVzktaiftpg*' .
                        self::get($_SERVER['HTTP_ACCEPT_LANGUAGE']) .
                        'tep!uTwVXk1CedJq0osEI7p&XCxnC3ipGDWEpTXULEg8J!K1NJSxe4GPor$R3OOb**ZjzPN$$SOHe4ZDcQWQULdtT&XxP2!YYxZy');
        }

        /**
         * Checks if the variable is usable in a foreach loop
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param mixed $input The variable
         *
         * @return bool
         * @since  1.2
         */
        public static function isTraversable($input) {
            return is_array($input) || $input instanceof Traversable;
        }

        /**
         * Protects the input from cross-site scripting attacks
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string|array|Traversable $input The scalar input, or an array/Traversable
         *
         * @return string|array|Traversable The escaped string. If an array or traversable was passed on, the input
         * withall its applicable values escaped.
         * @since  1.3.2 ENT_SUBSTITUTE added<br/>
         *         1.2
         */
        public static function unXss($input) {
            if (self::isTraversable($input)) {
                foreach ($input as &$i) {
                    $i = self::unXss($i);
                }
            } elseif (is_scalar($input)) {
                $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE);
            }

            return $input;
        }
    }
