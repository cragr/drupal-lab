<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Change the directory to the Drupal root. Use SCRIPT_FILENAME rather than
// __DIR__ to allow for the case where files are symlinked into the webroot.
$root_path = dirname($_SERVER['SCRIPT_FILENAME'], 4);
chdir($root_path);

$autoloader = require_once 'autoload.php';

$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

$views = $container
  ->get('config.factory')
  ->get('statistics.settings')
  ->get('count_content_views');

if ($views) {
  $nid = filter_input(INPUT_POST, 'nid', FILTER_VALIDATE_INT);
  if ($nid) {
    $container->get('request_stack')->push(Request::createFromGlobals());
    $container->get('statistics.storage.node')->recordView($nid);
  }
}
