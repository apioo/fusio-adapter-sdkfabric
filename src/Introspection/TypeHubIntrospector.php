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

namespace Fusio\Adapter\SdkFabric\Introspection;

use Fusio\Engine\Connection\Introspection\Entity;
use Fusio\Engine\Connection\Introspection\IntrospectorInterface;
use Fusio\Engine\Connection\Introspection\Row;
use PSX\Api\Parser\TypeAPI;
use PSX\Api\SpecificationInterface;
use PSX\Http\Client\ClientInterface;
use PSX\Http\Client\GetRequest;
use PSX\Http\Client\PostRequest;
use PSX\Schema\DefinitionsInterface;
use PSX\Schema\Format;
use PSX\Schema\SchemaManager;
use PSX\Schema\Type\ArrayType;
use PSX\Schema\Type\MapType;
use PSX\Schema\Type\ReferenceType;
use PSX\Schema\Type\ScalarType;
use PSX\Schema\Type\StructType;
use PSX\Schema\TypeInterface;

/**
 * TypeHubIntrospector
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class TypeHubIntrospector implements IntrospectorInterface
{
    private ClientInterface $httpClient;
    private string $name;
    private ?SpecificationInterface $spec = null;

    public function __construct(ClientInterface $httpClient, string $name)
    {
        $this->httpClient = $httpClient;
        $this->name = $name;
    }

    public function getEntities(): array
    {
        $spec = $this->getSpecification();
        $operations = array_keys($spec->getOperations()->getAll());
        $types = array_keys($spec->getDefinitions()->getTypes(DefinitionsInterface::SELF_NAMESPACE));

        return array_merge($operations, $types);
    }

    public function getEntity(string $entityName): Entity
    {
        $spec = $this->getSpecification();

        if ($spec->getOperations()->has($entityName)) {
            $operation = $spec->getOperations()->get($entityName);

            $entity = new Entity($entityName, ['Key', 'Value', 'In']);
            $entity->addRow(new Row(['Description', $operation->getDescription(), '']));
            $entity->addRow(new Row(['HTTP-Method', $operation->getMethod(), '']));
            $entity->addRow(new Row(['HTTP-Path', $operation->getPath(), '']));
            $entity->addRow(new Row(['HTTP-Code', $operation->getReturn()->getCode(), '']));
            $entity->addRow(new Row(['Outgoing', $this->typeToString($operation->getReturn()->getSchema()), '']));

            foreach ($operation->getArguments()->getAll() as $argumentName => $argument) {
                $entity->addRow(new Row([$argumentName, $this->typeToString($argument->getSchema()), $argument->getIn()]));
            }

            return $entity;
        }

        if ($spec->getDefinitions()->hasType($entityName)) {
            $type = $spec->getDefinitions()->getType($entityName);
            if ($type instanceof StructType) {
                return $this->parseStruct($entityName, $type);
            }
        }

        throw new \RuntimeException('Provided entity does not exist');
    }

    private function getSpecification(): SpecificationInterface
    {
        if ($this->spec instanceof SpecificationInterface) {
            return $this->spec;
        }

        return $this->spec = (new TypeAPI(new SchemaManager()))->parse($this->exportSpecification());
    }

    private function exportSpecification(): string
    {
        $payload = [
            'format' => 'spec-typeapi',
            'version' => '',
            'namespace' => '',
        ];

        $request = new PostRequest('https://api.typehub.cloud/document/sdkfabric/' . $this->name . '/export', ['Content-Type' => 'application/json'], json_encode($payload));
        $response = $this->httpClient->request($request);

        $body = (string) $response->getBody();
        $data = \json_decode($body);
        if (!isset($data->href)) {
            throw new \RuntimeException('Could not export specification: ' . $body);
        }

        $response = $this->httpClient->request(new GetRequest($data->href));
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('Could not request specification');
        }

        return (string) $response->getBody();
    }

    private function parseStruct(string $entityName, StructType $struct): Entity
    {
        $entity = new Entity($entityName, ['Name', 'Type', 'Format']);

        foreach ($struct->getProperties() as $propertyName => $property) {
            $type = $this->typeToString($property);
            if ($type === null) {
                continue;
            }

            $entity->addRow(new Row([$propertyName, $type, $this->formatToString($property)]));
        }

        return $entity;
    }

    private function typeToString(TypeInterface $type): ?string
    {
        if ($type instanceof ScalarType) {
            $data = $type->toArray();
            return $data['type'] ?? '';
        } elseif ($type instanceof ReferenceType) {
            return $type->getRef();
        } elseif ($type instanceof ArrayType) {
            $items = $type->getItems();
            if ($items instanceof ReferenceType) {
                return 'Array<' . $this->typeToString($items) . '>';
            }
        } elseif ($type instanceof MapType) {
            $additionalProperties = $type->getAdditionalProperties();
            if ($additionalProperties instanceof ReferenceType) {
                return 'Map<string, ' . $this->typeToString($additionalProperties) . '>';
            }
        }

        return null;
    }

    private function formatToString(TypeInterface $type): ?string
    {
        if ($type instanceof ScalarType) {
            $data = $type->toArray();
            $format = $data['format'] ?? '';
            if ($format instanceof Format) {
                return $format->value;
            } else {
                return $format;
            }
        }

        return null;
    }
}
