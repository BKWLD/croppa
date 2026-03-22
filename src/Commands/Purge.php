<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Commands;

use Bkwld\Croppa\Storage;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Delete all crops from the crops_disk.
 */
class Purge extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'croppa:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all crops';

    /**
     * Dependency inject.
     */
    public function __construct(protected Storage $storage)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $dry = $this->input->getOption('dry-run');
        foreach ($this->storage->deleteAllCrops($this->input->getOption('filter'), $dry) as $path) {
            $this->info(sprintf('%s %s', $path, $dry ? 'not deleted' : 'deleted'));
        }
    }

    /**
     * Get the console command options.
     *
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption('filter', null, InputOption::VALUE_REQUIRED, 'A regex pattern that whitelists matching crop paths'),
            new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Only return the crops that would be deleted'),
        ];
    }
}
