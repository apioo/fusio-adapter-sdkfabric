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
use SdkFabric\Airtable\Client;

/**
 * Airtable
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class Airtable extends OAuth2ConnectionAbstract
{
    public function getName(): string
    {
        return 'SDK-Airtable';
    }

    public function getConnection(ParametersInterface $config): Client
    {
        return Client::build($this->getAccessToken($config));
    }

    public function getAuthorizationUrl(ParametersInterface $config): string
    {
        return 'https://airtable.com/oauth2/v1/authorize';
    }

    public function getTokenUrl(ParametersInterface $config): string
    {
        return 'https://airtable.com/oauth2/v1/token';
    }
}
