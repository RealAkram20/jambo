<?php

// Root front controller: Apache routes non-asset requests here via
// .htaccess. This file simply delegates to public/index.php so that
// $_SERVER['SCRIPT_NAME'] ends with /index.php (not /public/index.php),
// and Laravel computes its base URL as the project folder with no
// /public segment. Paths inside public/index.php all use __DIR__, so
// they still resolve correctly.

require __DIR__.'/public/index.php';
