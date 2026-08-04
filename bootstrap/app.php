<?php

declare(strict_types=1);

use App\Foundation\Application;
use App\Foundation\Container;
use App\Providers\ProviderLoader;

$container = new Container();

(new ProviderLoader($container))->register();

return new Application($container);
