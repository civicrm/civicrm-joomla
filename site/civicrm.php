<?php
//////////////////////////////////////////////////
// CiviCRM Front-end Profile - Logic Layer
//////////////////////////////////////////////////

defined('_JEXEC') or die('No direct access allowed');

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

  // add all the values from the itemId param
  // overrride the GET values if conflict
  if (!empty($_REQUEST['Itemid'])) {
    $component = JComponentHelper::getComponent('com_civicrm');
    $app = JFactory::getApplication();
    $menu = $app->getMenu();
    $params = $menu->getParams($app->input->get('Itemid'));
    $args = array('task', 'id', 'gid', 'pageId', 'action', 'csid', 'component');
    $view = CRM_Utils_Array::value('view', $_REQUEST);
    if ($view) {
      $args[] = 'reset';
    }

    //look for menu item config in both request and params (backwards compatibility)
    foreach ($args as $a) {
      $val = CRM_Utils_Array::value($a, $_REQUEST, NULL);
      $valp = $params->get($a, NULL);
      if (($val !== NULL || $valp !== NULL) && $view) {
        $val = (!empty($val)) ? $val : $valp;
        $_REQUEST[$a] = $_GET[$a] = $val;
      }
    }
  }

  $task = CRM_Utils_Array::value('task', $_GET, '');
  $args = explode('/', trim($task));

  CRM_Core_Resources::singleton()->addCoreResources();

  $user = JFactory::getUser();
  CRM_Core_BAO_UFMatch::synchronize($user, FALSE, 'Joomla', 'Individual', TRUE);

  define('CIVICRM_UF_HEAD', TRUE);
  CRM_Core_Invoke::invoke($args);
}
