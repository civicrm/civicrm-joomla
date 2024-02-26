<?php
//////////////////////////////////////////////////
// CiviCRM Front-end Profile - Logic Layer
//////////////////////////////////////////////////

defined('_JEXEC') or die('No direct access allowed');

use Joomla\CMS\Factory;

define('CIVICRM_SETTINGS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'civicrm.settings.php');
include_once CIVICRM_SETTINGS_PATH;

civicrm_invoke();

/**
 * This was the original name of the initialization function and is
 * retained for backward compatibility
 */
function civicrm_init() {
  return civicrm_initialize();
}

/**
 * Initialize CiviCRM. Call this function from other modules too if
 * they use the CiviCRM API.
 */
function civicrm_initialize() {
  // Check for php version and ensure its greater than minPhpVersion
  $minPhpVersion = '7.4.0';
  if (version_compare(PHP_VERSION, $minPhpVersion) < 0) {
    echo "CiviCRM requires PHP version $minPhpVersion or greater. You are running PHP version " . PHP_VERSION . "<p>";
    exit();
  }

  require_once 'CRM/Core/ClassLoader.php';
  CRM_Core_ClassLoader::singleton()->register();

  require_once 'PEAR.php';
  $config = CRM_Core_Config::singleton();

  // Set the time zone in both PHP and database
  $joomlaUserTimezone = CRM_Core_Config::singleton()->userSystem->getTimeZoneString();
  date_default_timezone_set($joomlaUserTimezone);
  CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();

  // this is the front end, so let others know
  $config->userFrameworkFrontend = 1;
}

function civicrm_invoke() {
  civicrm_initialize();

  $app = Factory::getApplication();
  $input = $app->input;
  $itemId = $input->getInt('Itemid', 0);

  if ($itemId) {
    $args = array('task', 'id', 'gid', 'pageId', 'action', 'csid', 'component');
    $view = $input->getString('view');
    if ($view) {
      $args[] = 'reset';
    }

    //look for menu item config in both request and params (backwards compatibility)
    foreach ($args as $arg) {
      $val = $input->getString($arg, NULL);
      $_GET[$arg] = $_REQUEST[$arg] = $val; // $_GET and $_REQUEST are used by CiviCRM all over the place
    }
  }

  $task = $_REQUEST['task'];
  $args = explode('/', trim($task));

  CRM_Core_Resources::singleton()->addCoreResources();

  $user = $app->getIdentity();
  CRM_Core_BAO_UFMatch::synchronize($user, FALSE, 'Joomla', 'Individual', TRUE);

  define('CIVICRM_UF_HEAD', TRUE);
  CRM_Core_Invoke::invoke($args);
}
