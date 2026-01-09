<?php
/**
 * Root redirect to public folder
 * This file should be in the root directory if your domain points to /apps/goaltracker/
 * instead of /apps/goaltracker/public/
 */

// Redirect to public folder
header('Location: /public/index.php');
exit;
