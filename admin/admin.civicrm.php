<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('No direct access allowed');

// check for php version and ensure its greater than 5.
// do a fatal exit if
if ((int ) substr(PHP_VERSION, 0, 1) < 5) {
  echo "CiviCRM requires PHP Version 5.2 or greater. You are running PHP Version " . PHP_VERSION . "<p>";
  exit();
}

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
  $minPhpVersion = '5.3.3';
  if (version_compare(PHP_VERSION, $minPhpVersion) < 0) {
    echo "CiviCRM requires PHP Version $minPhpVersion or greater. You are running PHP Version " . PHP_VERSION . "<p>";
    exit();
  }

  require_once 'CRM/Core/ClassLoader.php';
  CRM_Core_ClassLoader::singleton()->register();

  require_once 'PEAR.php';
  $config = CRM_Core_Config::singleton();
}

function plugin_init() {
  //invoke plugins.
  JPluginHelper::importPlugin('civicrm');
  $app = JFactory::getApplication();
  $app->triggerEvent('onCiviLoad');

  // set page title
  JToolBarHelper::title('CiviCRM');
}

function civicrm_invoke() {
  civicrm_initialize();

  plugin_init();

  $user = JFactory::getUser();

  /* bypass synchronize if running upgrade
   * to avoid any serious non-recoverable error
   * which might hinder the upgrade process.
   */

  if (CRM_Utils_Array::value('task', $_REQUEST) != 'civicrm/upgrade') {
    CRM_Core_BAO_UFMatch::synchronize($user, FALSE, 'Joomla', 'Individual', TRUE);
  }

  // Add our standard css & js
  $resources = CRM_Core_Resources::singleton();
  $resources->addCoreResources();

  $config = CRM_Core_Config::singleton();
  if (!$config->userFrameworkFrontend) {
    $resources->addStyleFile('civicrm', 'css/joomla.css', -101, 'html-header');
  }
  else {
    $resources->addStyleFile('civicrm', 'css/joomla_frontend.css', -97, 'html-header');
  }

  if (isset($_GET['task'])) {
    $args = explode('/', trim($_GET['task']));
  }
  else {
    $_GET['task'] = 'civicrm/dashboard';
    $_GET['reset'] = 1;
    $args = array('civicrm', 'dashboard');
  }
  define('CIVICRM_UF_HEAD', TRUE);
  CRM_Core_Invoke::invoke($args);
}

