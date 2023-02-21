<?php
namespace think\console;
use think\Console;
use think\console\input\Argument;
use think\console\input\Definition;
use think\console\input\Option;
class Command
{
    private $console;
    private $name;
    private $aliases = [];
    private $definition;
    private $help;
    private $description;
    private $ignoreValidationErrors          = false;
    private $consoleDefinitionMerged         = false;
    private $consoleDefinitionMergedWithArgs = false;
    private $code;
    private $synopsis = [];
    private $usages   = [];
    protected $input;
    protected $output;
    public function __construct($name = null)
    {
        $this->definition = new Definition();
        if (null !== $name) {
            $this->setName($name);
        }
        $this->configure();
        if (!$this->name) {
            throw new \LogicException(sprintf('The command defined in "%s" cannot have an empty name.', get_class($this)));
        }
    }
    public function ignoreValidationErrors()
    {
        $this->ignoreValidationErrors = true;
    }
    public function setConsole(Console $console = null)
    {
        $this->console = $console;
    }
    public function getConsole()
    {
        return $this->console;
    }
    public function isEnabled()
    {
        return true;
    }
    protected function configure()
    {
    }
    protected function execute(Input $input, Output $output)
    {
        throw new \LogicException('You must override the execute() method in the concrete command class.');
    }
    protected function interact(Input $input, Output $output)
    {
    }
    protected function initialize(Input $input, Output $output)
    {
    }
    public function run(Input $input, Output $output)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->getSynopsis(true);
        $this->getSynopsis(false);
        $this->mergeConsoleDefinition();
        try {
            $input->bind($this->definition);
        } catch (\Exception $e) {
            if (!$this->ignoreValidationErrors) {
                throw $e;
            }
        }
        $this->initialize($input, $output);
        if ($input->isInteractive()) {
            $this->interact($input, $output);
        }
        $input->validate();
        if ($this->code) {
            $statusCode = call_user_func($this->code, $input, $output);
        } else {
            $statusCode = $this->execute($input, $output);
        }
        return is_numeric($statusCode) ? (int) $statusCode : 0;
    }
    public function setCode(callable $code)
    {
        if (!is_callable($code)) {
            throw new \InvalidArgumentException('Invalid callable provided to Command::setCode.');
        }
        if (PHP_VERSION_ID >= 50400 && $code instanceof \Closure) {
            $r = new \ReflectionFunction($code);
            if (null === $r->getClosureThis()) {
                $code = \Closure::bind($code, $this);
            }
        }
        $this->code = $code;
        return $this;
    }
    public function mergeConsoleDefinition($mergeArgs = true)
    {
        if (null === $this->console
            || (true === $this->consoleDefinitionMerged
                && ($this->consoleDefinitionMergedWithArgs || !$mergeArgs))
        ) {
            return;
        }
        if ($mergeArgs) {
            $currentArguments = $this->definition->getArguments();
            $this->definition->setArguments($this->console->getDefinition()->getArguments());
            $this->definition->addArguments($currentArguments);
        }
        $this->definition->addOptions($this->console->getDefinition()->getOptions());
        $this->consoleDefinitionMerged = true;
        if ($mergeArgs) {
            $this->consoleDefinitionMergedWithArgs = true;
        }
    }
    public function setDefinition($definition)
    {
        if ($definition instanceof Definition) {
            $this->definition = $definition;
        } else {
            $this->definition->setDefinition($definition);
        }
        $this->consoleDefinitionMerged = false;
        return $this;
    }
    public function getDefinition()
    {
        return $this->definition;
    }
    public function getNativeDefinition()
    {
        return $this->getDefinition();
    }
    public function addArgument($name, $mode = null, $description = '', $default = null)
    {
        $this->definition->addArgument(new Argument($name, $mode, $description, $default));
        return $this;
    }
    public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        $this->definition->addOption(new Option($name, $shortcut, $mode, $description, $default));
        return $this;
    }
    public function setName($name)
    {
        $this->validateName($name);
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }
    public function getHelp()
    {
        return $this->help;
    }
    public function getProcessedHelp()
    {
        $name = $this->name;
        $placeholders = [
            '%command.name%',
            '%command.full_name%',
        ];
        $replacements = [
            $name,
            $_SERVER['PHP_SELF'] . ' ' . $name,
        ];
        return str_replace($placeholders, $replacements, $this->getHelp());
    }
    public function setAliases($aliases)
    {
        if (!is_array($aliases) && !$aliases instanceof \Traversable) {
            throw new \InvalidArgumentException('$aliases must be an array or an instance of \Traversable');
        }
        foreach ($aliases as $alias) {
            $this->validateName($alias);
        }
        $this->aliases = $aliases;
        return $this;
    }
    public function getAliases()
    {
        return $this->aliases;
    }
    public function getSynopsis($short = false)
    {
        $key = $short ? 'short' : 'long';
        if (!isset($this->synopsis[$key])) {
            $this->synopsis[$key] = trim(sprintf('%s %s', $this->name, $this->definition->getSynopsis($short)));
        }
        return $this->synopsis[$key];
    }
    public function addUsage($usage)
    {
        if (0 !== strpos($usage, $this->name)) {
            $usage = sprintf('%s %s', $this->name, $usage);
        }
        $this->usages[] = $usage;
        return $this;
    }
    public function getUsages()
    {
        return $this->usages;
    }
    private function validateName($name)
    {
        if (!preg_match('/^[^\:]++(\:[^\:]++)*$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Command name "%s" is invalid.', $name));
        }
    }
    protected function table(Table $table)
    {
        $content = $table->render();
        $this->output->writeln($content);
        return $content;
    }
}
