<?php

declare(strict_types=1);

namespace Clubhuesli\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Given;

final class FeatureContext implements Context
{
    #[Given('the test environment is set up')]
    public function theTestEnvironmentIsSetUp(): void
    {
    }
}
