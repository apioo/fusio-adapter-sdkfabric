<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2024 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Adapter\SdkFabric\Connection;

use Fusio\Engine\ParametersInterface;
use SdkFabric\Twitter\Client;

/**
 * Twitter
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class Twitter extends OAuth2ConnectionAbstract
{
    public function getName(): string
    {
        return 'Twitter';
    }

    public function getConnection(ParametersInterface $config): Client
    {
        return Client::build($this->getAccessToken($config));
    }

    public function getAuthorizationUrl(ParametersInterface $config): string
    {
        return 'https://twitter.com/i/oauth2/authorize';
    }

    public function getTokenUrl(ParametersInterface $config): string
    {
        return 'https://api.twitter.com/2/oauth2/token';
    }

    public function getRedirectUriParameters(string $redirectUri, string $state, ParametersInterface $config): array
    {
        $params = parent::getRedirectUriParameters($redirectUri, $state, $config);
        $params['code_challenge'] = $this->generateChallenge();
        $params['code_challenge_method'] = 'S256';
        return $params;
    }

    private function generateChallenge(): string
    {
        $verifier = substr(sha1(random_bytes(128)), 0, 64);
        $hash = hash('sha256', $verifier, true);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }
}
