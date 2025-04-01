<?php

declare(strict_types=1);

use Duon\Development\PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/tests']);
$config = new Config();

return $config->setFinder($finder);