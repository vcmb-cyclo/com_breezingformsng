<?php
    /**
     *    Copyright (c) Arturas Molcanovas <a.molcanovas@gmail.com> 2016.
     *    https://github.com/Alorel/dropbox-v2-php
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

    namespace Alorel\Dropbox\Operation\Users;

    use Alorel\Dropbox\OperationKind\RPCOperation;
    use Alorel\Dropbox\Options\Option;
    use GuzzleHttp\Exception\ClientException;

    /**
     * Get information about a user's account.
     *
     * @author Art <a.molcanovas@gmail.com>
     * @see    https://www.dropbox.com/developers/documentation/http/documentation#users-get_account
     */
    class GetAuthorizeToken extends RPCOperation {

        /**
         * Perform the operation, returning a promise or raw response object
         *
         * @author Art <a.molcanovas@gmail.com>
         *
         * @param string $accountID The ID of the account we're grabbing
         *
         * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface The promise interface if
         *                                                                                  async is set to true and the
         *                                                                                  request interface if it is
         *                                                                                  set to false
         * @throws \GuzzleHttp\Exception\ClientException
         */
        public function raw($code) {

            $config_file = JPATH_SITE . '/administrator/components/com_breezingforms/libraries/dropbox/config.json';

            if(!file_exists($config_file)){

                    throw new ClientException('No config found');
            }

            $config = json_decode(file_get_contents($config_file));

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => [
                    "client_id" => $config->key,
                    "client_secret" => $config->secret,
                    "grant_type" => "authorization_code",
                    "code" => $code
                ]
            ]);

            return json_decode($response->getBody());
        }
    }