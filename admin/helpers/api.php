<?php

/**
 * @version
 * @package
 * @copyright   @copyright CiviCRM LLC (c) 2004-2014
 * @license		GNU/GPL v2 or later
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Component Helper
jimport('joomla.application.component.helper');
class CivicrmHelperApi {
  static function civiInit() {
    if (!defined('CIVICRM_SETTINGS_PATH')) {
      define('CIVICRM_SETTINGS_PATH', JPATH_BASE . '/components/com_civicrm/civicrm.settings.php');
    }
    require_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();
  }

  static function civiimport($path) {
    self::civiInit();

    global $civicrm_root;
    return JLoader::import($path, $civicrm_root, '');
  }
}

