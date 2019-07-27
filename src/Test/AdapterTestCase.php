<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2019 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Engine\Test;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Fusio\Engine\AdapterInterface;
use PHPUnit\Framework\TestCase;
use PSX\Schema\SchemaManager;
use PSX\Schema\SchemaTraverser;

/**
 * AdapterTestCase
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org
 */
abstract class AdapterTestCase extends TestCase
{
    public function testDefinition()
    {
        $class = $this->getAdapterClass();

        $this->assertTrue(class_exists($class));

        /** @var AdapterInterface $adapter */
        $adapter = new $class();

        $this->assertInstanceOf(AdapterInterface::class, $adapter);

        $path = $adapter->getDefinition();
        if (!is_file($path)) {
            $this->fail('Adapter definition file ' . $path . ' does not exist');
        }

        $data = json_decode(file_get_contents($path));

        $this->validateSchema($data);
        $this->validateClassExistence($data);
    }

    private function validateSchema(\stdClass $data)
    {
        $manager = new SchemaManager(new SimpleAnnotationReader());
        $schema  = $manager->getSchema(__DIR__ . '/definition_schema.json');

        $traverser = new SchemaTraverser();
        $traverser->traverse($data, $schema);
    }

    private function validateClassExistence(\stdClass $data)
    {
        $types = ['action', 'connection', 'user', 'payment'];

        foreach ($types as $type) {
            $key = $type . 'Class';
            if (isset($data->{$key})) {
                foreach ($data->{$key} as $class) {
                    if (!class_exists($class)) {
                        $this->fail('Defined ' . $key . ' class ' . $class . ' does not exist');
                    }
                }
            }
        }
    }

    /**
     * Returns the adapter class name
     * 
     * @return string
     */
    abstract protected function getAdapterClass();
}
