<?php

namespace App\Transformers;

use App\Models\Role;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;

class SiteEnumTransformer implements Transformer
{
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        $reflection = new ReflectionClass($class->getName());

        $constants = $reflection->getConstants();
        $excludedFields = ['created_at', 'updated_at'];

        $enumValues = array_filter(
            $constants,
            fn ($key) => !in_array($key, $excludedFields),
        );

        if ($this->areAllValuesNumeric($enumValues)) {
            return $this->toEnum($class, $name, $enumValues);
        }

        $typeValues = array_map(
            fn ($value) => is_string($value) ? "'{$value}'" : $value,
            $enumValues
        );

        return TransformedType::create($class, $name, implode(' | ', $typeValues));
    }

    public function canTransform(string $className): bool
    {
        $reflection = new ReflectionClass($className);

        return
            $className === Role::class
            || $reflection->isSubclassOf('App\\Enums\\BaseEnum')
            || $reflection->isSubclassOf('App\\Community\\Enums\\BaseEnum')
            || $reflection->isSubclassOf('App\\Platform\\Enums\\BaseEnum');
    }

    protected function areAllValuesNumeric(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    protected function toEnum(ReflectionClass $class, string $name, array $values): TransformedType
    {

        $enumMembers = array_map(
            fn ($key, $value) => "{$key} = {$value}",
            array_keys($values),
            $values
        );

        $enumDefinition = implode(", ", $enumMembers);

        return TransformedType::create(
            $class,
            $name,
            $enumDefinition,
            keyword: 'enum',
        );
    }
}
