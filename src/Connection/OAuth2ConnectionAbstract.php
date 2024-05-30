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

use Fusio\Adapter\SdkFabric\Introspection\TypeHubIntrospector;
use Fusio\Engine\Connection\IntrospectableInterface;
use Fusio\Engine\Connection\Introspection\IntrospectorInterface;
use Fusio\Engine\Connection\OAuth2ConnectionAbstract as EngineOAuth2ConnectionAbstract;
use PSX\Http\Client\ClientInterface;

/**
 * OAuth2ConnectionAbstract
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
abstract class OAuth2ConnectionAbstract extends EngineOAuth2ConnectionAbstract implements IntrospectableInterface
{
    private ClientInterface $httpClient;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getIntrospector(mixed $connection): IntrospectorInterface
    {
        return new TypeHubIntrospector($this->httpClient, strtolower((new \ReflectionClass(static::class))->getShortName()));
    }
}
