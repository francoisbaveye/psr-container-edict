<?php

declare(strict_types=1);

namespace IngeniozIT\Edict;

use Psr\Container\ContainerInterface;
use ReflectionException;

class Container implements ContainerInterface
{
    /** @var callable[] */
    protected array $entries = [];

    protected bool $autowiring = true;

    public function __construct()
    {
        $this->set(self::class, value($this));
        $this->set(ContainerInterface::class, alias(self::class));
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw $this->autowiring && class_exists($id) ?
                new ContainerException("Class $id cannot be autowired") :
                new NotFoundException("Entry $id does not exist");
        }

        return $this->entries[$id]($this);
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]) || ($this->autowiring && class_exists($id) && $this->autowire($id));
    }

    public function set(string $id, callable $value): void
    {
        $this->entries[$id] = $value;
    }

    /**
     * @param iterable<string, callable> $entries
     */
    public function setMany(iterable $entries): void
    {
        foreach ($entries as $entryId => $entryValue) {
            $this->set($entryId, $entryValue);
        }
    }

    /**
     * @param class-string $className
     */
    protected function autowire(string $className): bool
    {
        try {
            $this->set($className, objectValue($className));
            return true;
        } catch (ContainerException | ReflectionException) {
            return false;
        }
    }

    public function disableAutowiring(): void
    {
        $this->autowiring = false;
    }

    public function enableAutowiring(): void
    {
        $this->autowiring = true;
    }

    /**
     * @throws ContainerException when file does not exist
     */
    public function setFromFile(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new ContainerException("File $filename does not exist");
        }

        $this->setMany(require $filename);
    }
}
