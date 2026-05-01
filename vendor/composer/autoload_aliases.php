<?php

// Functions and constants

namespace {
}

namespace Otto {
    class AliasAutoloader
    {
        private string $includeFilePath;

        private array $autoloadAliases = [
            "ByteKit\\Models\\Model" => [
                "type" => "class",
                "classname" => "Model",
                "isabstract" => true,
                "namespace" => "ByteKit\\Models",
                "extends" => "Otto\\ByteKit\\Models\\Model",
                "implements" => [],
            ],
            "ByteKit\\Models\\Post" => [
                "type" => "class",
                "classname" => "Post",
                "isabstract" => true,
                "namespace" => "ByteKit\\Models",
                "extends" => "Otto\\ByteKit\\Models\\Post",
                "implements" => [],
            ],
            "ByteKit\\Models\\Query" => [
                "type" => "class",
                "classname" => "Query",
                "isabstract" => false,
                "namespace" => "ByteKit\\Models",
                "extends" => "Otto\\ByteKit\\Models\\Query",
                "implements" => [],
            ],
            "ByteKit\\Models\\Relations\\BelongsTo" => [
                "type" => "class",
                "classname" => "BelongsTo",
                "isabstract" => false,
                "namespace" => "ByteKit\\Models\\Relations",
                "extends" => "Otto\\ByteKit\\Models\\Relations\\BelongsTo",
                "implements" => [],
            ],
            "ByteKit\\Models\\Relations\\BelongsToMany" => [
                "type" => "class",
                "classname" => "BelongsToMany",
                "isabstract" => false,
                "namespace" => "ByteKit\\Models\\Relations",
                "extends" => "Otto\\ByteKit\\Models\\Relations\\BelongsToMany",
                "implements" => [],
            ],
            "ByteKit\\Models\\Relations\\HasMany" => [
                "type" => "class",
                "classname" => "HasMany",
                "isabstract" => false,
                "namespace" => "ByteKit\\Models\\Relations",
                "extends" => "Otto\\ByteKit\\Models\\Relations\\HasMany",
                "implements" => [],
            ],
            "ByteKit\\Models\\Relations\\HasOne" => [
                "type" => "class",
                "classname" => "HasOne",
                "isabstract" => false,
                "namespace" => "ByteKit\\Models\\Relations",
                "extends" => "Otto\\ByteKit\\Models\\Relations\\HasOne",
                "implements" => [],
            ],
            "ByteKit\\Models\\Relations\\Relation" => [
                "type" => "class",
                "classname" => "Relation",
                "isabstract" => true,
                "namespace" => "ByteKit\\Models\\Relations",
                "extends" => "Otto\\ByteKit\\Models\\Relations\\Relation",
                "implements" => [],
            ],
            "ByteKit\\Admin\\Flash" => [
                "type" => "class",
                "classname" => "Flash",
                "isabstract" => false,
                "namespace" => "ByteKit\\Admin",
                "extends" => "Otto\\ByteKit\\Admin\\Flash",
                "implements" => [],
            ],
            "ByteKit\\Admin\\Notices" => [
                "type" => "class",
                "classname" => "Notices",
                "isabstract" => false,
                "namespace" => "ByteKit\\Admin",
                "extends" => "Otto\\ByteKit\\Admin\\Notices",
                "implements" => [],
            ],
            "ByteKit\\Plugin" => [
                "type" => "class",
                "classname" => "Plugin",
                "isabstract" => true,
                "namespace" => "ByteKit",
                "extends" => "Otto\\ByteKit\\Plugin",
                "implements" => [
                    0 => "ByteKit\\Interfaces\\Pluginable",
                ],
            ],
            "ByteKit\\Scripts" => [
                "type" => "class",
                "classname" => "Scripts",
                "isabstract" => false,
                "namespace" => "ByteKit",
                "extends" => "Otto\\ByteKit\\Scripts",
                "implements" => [
                    0 => "ByteKit\\Interfaces\\Scriptable",
                ],
            ],
            "ByteKit\\Services" => [
                "type" => "class",
                "classname" => "Services",
                "isabstract" => false,
                "namespace" => "ByteKit",
                "extends" => "Otto\\ByteKit\\Services",
                "implements" => [
                    0 => "ArrayAccess",
                ],
            ],
            "ByteKit\\Models\\Traits\\HasAttributes" => [
                "type" => "trait",
                "traitname" => "HasAttributes",
                "namespace" => "ByteKit\\Models\\Traits",
                "use" => [
                    0 => "Otto\\ByteKit\\Models\\Traits\\HasAttributes",
                ],
            ],
            "ByteKit\\Models\\Traits\\HasMetaData" => [
                "type" => "trait",
                "traitname" => "HasMetaData",
                "namespace" => "ByteKit\\Models\\Traits",
                "use" => [
                    0 => "Otto\\ByteKit\\Models\\Traits\\HasMetaData",
                ],
            ],
            "ByteKit\\Models\\Traits\\HasRelations" => [
                "type" => "trait",
                "traitname" => "HasRelations",
                "namespace" => "ByteKit\\Models\\Traits",
                "use" => [
                    0 => "Otto\\ByteKit\\Models\\Traits\\HasRelations",
                ],
            ],
            "ByteKit\\Traits\\HasPlugin" => [
                "type" => "trait",
                "traitname" => "HasPlugin",
                "namespace" => "ByteKit\\Traits",
                "use" => [
                    0 => "Otto\\ByteKit\\Traits\\HasPlugin",
                ],
            ],
            "ByteKit\\Interfaces\\Pluginable" => [
                "type" => "interface",
                "interfacename" => "Pluginable",
                "namespace" => "ByteKit\\Interfaces",
                "extends" => [
                    0 => "Otto\\ByteKit\\Interfaces\\Pluginable",
                ],
            ],
            "ByteKit\\Interfaces\\Scriptable" => [
                "type" => "interface",
                "interfacename" => "Scriptable",
                "namespace" => "ByteKit\\Interfaces",
                "extends" => [
                    0 => "Otto\\ByteKit\\Interfaces\\Scriptable",
                ],
            ],
        ];

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . "/autoload_alias.php";
        }

        public function autoload($class)
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]["type"]) {
                case "class":
                    $this->load(
                        $this->classTemplate($this->autoloadAliases[$class]),
                    );
                    break;
                case "interface":
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class],
                        ),
                    );
                    break;
                case "trait":
                    $this->load(
                        $this->traitTemplate($this->autoloadAliases[$class]),
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile)
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) &&
                unlink($this->includeFilePath);
        }

        private function classTemplate(array $class): string
        {
            $abstract = $class["isabstract"] ? "abstract " : "";
            $classname = $class["classname"];
            if (isset($class["namespace"])) {
                $namespace = "namespace {$class["namespace"]};";
                $extends = "\\" . $class["extends"];
                $implements = empty($class["implements"])
                    ? ""
                    : " implements \\" . implode(", \\", $class["implements"]);
            } else {
                $namespace = "";
                $extends = $class["extends"];
                $implements = !empty($class["implements"])
                    ? ""
                    : " implements " . implode(", ", $class["implements"]);
            }
            return <<<EOD
            <?php
            $namespace
            $abstract class $classname extends $extends $implements {}
            EOD;
        }

        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface["interfacename"];
            $namespace = isset($interface["namespace"])
                ? "namespace {$interface["namespace"]};"
                : "";
            $extends = isset($interface["namespace"])
                ? "\\" . implode("\\ ,", $interface["extends"])
                : implode(", ", $interface["extends"]);
            return <<<EOD
            <?php
            $namespace
            interface $interfacename extends $extends {}
            EOD;
        }
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait["traitname"];
            $namespace = isset($trait["namespace"])
                ? "namespace {$trait["namespace"]};"
                : "";
            $uses = isset($trait["namespace"])
                ? "\\" . implode(";" . PHP_EOL . "    use \\", $trait["use"])
                : implode(";" . PHP_EOL . "    use ", $trait["use"]);
            return <<<EOD
            <?php
            $namespace
            trait $traitname {
                use $uses;
            }
            EOD;
        }
    }

    spl_autoload_register([new AliasAutoloader(), "autoload"]);
}
