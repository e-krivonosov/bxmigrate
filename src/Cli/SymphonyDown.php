<?php

namespace Evk\BxMigrate\Cli;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Консольная команда для Symfony console, которая откатывает миграции.
 */
class SymphonyDown extends AbstractManagerCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('bxmigrate:down')
            ->setDescription('Sets down migration')
            ->addArgument(
                'count',
                InputArgument::OPTIONAL,
                'Count of migrations or name of migration to set down'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $input->getArgument('count') ?: null;
        $manager = $this->getOrCreateMigrateManager($input, $output);

        if (preg_match('/.*[^0-9]+.*/', $count)) {
            $manager->downByName($count);
        } else {
            $manager->down($count);
        }
    }
}
