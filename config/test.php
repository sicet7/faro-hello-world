<?php

use function Config\env;

return [
    'test' => 'test',
    'test55' => env('MYTEST', null),
];
