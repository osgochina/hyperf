<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\Phar;

use Hyperf\Command\Command as HyperfCommand;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use UnexpectedValueException;

class BuildCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('phar:build');
        $this->container = $container;
    }

    public function configure()
    {
        $this->setDescription('Pack your project into a Phar package.')
            ->addOption('name', '', InputOption::VALUE_OPTIONAL, 'This is the name of the Phar package, and if it is not passed in, the project name is used by default', null)
            ->addOption('bin', 'b', InputOption::VALUE_OPTIONAL, 'The script path to execute by default.', 'bin/hyperf.php')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Project root path, default BASE_PATH.', null)
            ->addOption('phar-version', '', InputOption::VALUE_OPTIONAL, 'The version of the project that will be compiled.', null);
    }

    public function handle()
    {
        $this->assertWritable();
        $name = $this->input->getOption('name');
        $bin = $this->input->getOption('bin');
        $path = $this->input->getOption('path');
        $version = $this->input->getOption('phar-version');
        if (empty($path)) {
            $path = BASE_PATH;
        }
        $builder = $this->getPharBuilder($path);
        if (! empty($bin)) {
            $builder->setMain($bin);
        }
        if (! empty($name)) {
            $builder->setTarget($name);
        }
        if (! empty($version)) {
            $builder->setVersion($version);
        }
        $builder->build();
    }

    /**
     * check readonly.
     */
    public function assertWritable()
    {
        if (ini_get('phar.readonly') === '1') {
            throw new UnexpectedValueException('Your configuration disabled writing phar files (phar.readonly = On), please update your configuration');
        }
    }

    public function getPharBuilder(string $path): PharBuilder
    {
        if (is_dir($path)) {
            $path = rtrim($path, '/') . '/composer.json';
        }
        if (! is_file($path)) {
            throw new InvalidArgumentException(sprintf('The given path %s is not a readable file', $path));
        }
        $pharBuilder = new PharBuilder($path, $this->container->get(LoggerInterface::class));

        $vendorPath = $pharBuilder->getPackage()->getVendorAbsolutePath();
        if (! is_dir($vendorPath)) {
            throw new RuntimeException('The project has not been initialized, please manually execute the command `composer install` to install the dependencies');
        }
        return $pharBuilder;
    }
}
