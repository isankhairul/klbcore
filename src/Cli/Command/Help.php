<?php


namespace KlbV2\Core\Cli\Command;


use KlbV2\Core\Task;

class Help extends Task
{
    /**
     * Task name
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * The description
     *
     * @var string
     */
    protected $description = 'Returns a list of commands and helpful guide to running them....';
    /**
     * The main action
     *
     * @Action
     * @return void
     */
    public function main()
    {
        // App Details
        $this->printApplicationDetails();

        // Instructions
        $this->printApplicationInstructions();

        // Command List
        $this->listCommands();
    }
    /**
     * Prints the app name and version
     *
     * @return void
     */
    public function printApplicationDetails()
    {
        $this->getOutput()->writeln($this->console->getName());

        if (!is_null($this->console->getVersion())) {
            $this->getOutput()->writeln('version '.$this->console->getVersion());
        }

        // New line padding
        $this->getOutput()->writeln('');
    }

    /**
     * Prints the application instructions
     *
     * @return void
     */
    public function printApplicationInstructions()
    {
        $this->getOutput()->writeln($this->getColoredString('Usage:', 'brown'));
        $this->getOutput()->writeln("\tphp cli command [arguments] [options]");

        // Padding
        $this->getOutput()->writeln('');
    }

    /**
     * Lists out command names and descriptions
     *
     * @return void
     */
    public function listCommands()
    {
        $this->getOutput()->writeln($this->getColoredString("Available commands:\n", 'brown'));
        $commands = $this->library->getAll();
        ksort($commands);
        foreach ($commands as $name => $details) {
            if($name === 'help') continue;
            $this->getOutput()->write($this->getColoredString(" " . $name, 'brown'));
            if(!empty($details['description'])) {
                $this->getOutput()->writeln(" => " . $this->getColoredString($details['description'], 'purple'));
            } else {
                $this->getOutput()->writeln('');
            }

            foreach ($details['actions'] as $action) {
                $this->getOutput()->writeln(
                    sprintf(
                        $this->getColoredString("  %s%s", 'green'),
                        $name,
                        ($action !== 'main' ? ":$action" : '')
                    )
                );
            }

            // Just for padding
            $this->getOutput()->writeln('');
        }
    }
}
