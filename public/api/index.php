<?php
/**
 * Front controller of the JSON API. Everything below `/api` ends up here.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

(new Clubhuesli\Http\ApiKernel())->run();
