<?php
namespace think\console\output\descriptor;
use think\Console as ThinkConsole;
use think\console\Command;
class Console
{
    const GLOBAL_NAMESPACE = '_global';
    private $console;
    private $namespace;
    private $namespaces;
    private $commands;
    private $aliases;
    public function __construct(ThinkConsole $console, $namespace = null)
    {
        $this->console   = $console;
        $this->namespace = $namespace;
    }
    public function getNamespaces()
    {
        if (null === $this->namespaces) {
            $this->inspectConsole();
        }
        return $this->namespaces;
    }
    public function getCommands()
    {
        if (null === $this->commands) {
            $this->inspectConsole();
        }
        return $this->commands;
    }
    public function getCommand($name)
    {
        if (!isset($this->commands[$name]) && !isset($this->aliases[$name])) {
            throw new \InvalidArgumentException(sprintf('Command %s does not exist.', $name));
        }
        return isset($this->commands[$name]) ? $this->commands[$name] : $this->aliases[$name];
    }
    private function inspectConsole()
    {
        $this->commands   = [];
        $this->namespaces = [];
        $all = $this->console->all($this->namespace ? $this->console->findNamespace($this->namespace) : null);
        foreach ($this->sortCommands($all) as $namespace => $commands) {
            $names = [];
            foreach ($commands as $name => $command) {
                if (is_string($command)) {
                    $command = new $command();
                }
                if (!$command->getName()) {
                    continue;
                }
                if ($command->getName() === $name) {
                    $this->commands[$name] = $command;
                } else {
                    $this->aliases[$name] = $command;
                }
                $names[] = $name;
            }
            $this->namespaces[$namespace] = ['id' => $namespace, 'commands' => $names];
        }
    }
    private function sortCommands(array $commands)
    {
        $namespacedCommands = [];
        foreach ($commands as $name => $command) {
            $key = $this->console->extractNamespace($name, 1);
            if (!$key) {
                $key = self::GLOBAL_NAMESPACE;
            }
            $namespacedCommands[$key][$name] = $command;
        }
        ksort($namespacedCommands);
        foreach ($namespacedCommands as &$commandsSet) {
            ksort($commandsSet);
        }
        unset($commandsSet);
        return $namespacedCommands;
    }
}
