<?php

$local = __DIR__ . '/database.local.php';

return is_file($local) ? require $local : null;
