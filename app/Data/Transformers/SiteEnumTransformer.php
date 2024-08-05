<?php

namespace App\Data\Transformers;

use App\Models\Role;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;

/**
 * Responsible for outputting the generatedAppConstants.ts file.
 * RAWeb contains a lot of abstract class enums. These do not neatly map to
 * JS/TS enums. They instead need to be transformed to dictionary objects.
 * This transformer is responsible for ingesting these abstract class enums
 * and writing to the TS file using the `composer types` command.
 */
class SiteEnumTransformer implements Transformer
{
    private static array $generatedConstants = [];

    /**
     * @param ReflectionClass<object> $class
     */
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        $reflection = new ReflectionClass($class->getName());
        $constants = $reflection->getConstants();
        $excludedFields = ['created_at', 'updated_at'];

        $enumValues = array_filter(
            $constants,
            fn ($key) => !in_array($key, $excludedFields),
        );

        $this->generateTypeScriptConstant($name, $enumValues);

        // Return the original transformed type for the main process
        return TransformedType::create($class, $name, $this->getTypeScriptDefinition($enumValues));
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

    protected function generateTypeScriptConstant(string $name, array $values): void
    {
        $isIntEnum = $this->isIntEnum($values);

        $constDefinition = "export const $name = {\n";
        foreach ($values as $key => $value) {
            $constDefinition .= "    $key: " . (is_string($value) ? "'$value'" : $value) . ",\n";
        }
        $constDefinition .= "} as const;\n\n";

        self::$generatedConstants[] = $constDefinition;

        if ($isIntEnum) {
            $stringifiedConstDefinition = "export const Stringified$name = {\n";
            foreach ($values as $key => $value) {
                $stringifiedConstDefinition .= "    $key: '$value',\n";
            }
            $stringifiedConstDefinition .= "} as const;\n\n";

            self::$generatedConstants[] = $stringifiedConstDefinition;
        }
    }

    protected function getTypeScriptDefinition(array $values): string
    {
        $typeValues = array_map(
            fn ($value) => is_string($value) ? "'{$value}'" : $value,
            $values
        );

        return implode(' | ', $typeValues);
    }

    public static function getGeneratedConstants(): string
    {
        return implode("\n", self::$generatedConstants);
    }

    private function isIntEnum(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_int($value)) {
                return false;
            }
        }

        return true;
    }
}
