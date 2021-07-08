<?php
/**
 * @copyright   Copyright (C) 2005 - 2014 CiviCRM LLC All rights reserved.
 * @license     GNU Affero General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * CiviCRM User Management Plugin
 *
 * @package Joomla
 * @subpackage JFramework
 * @since 1.6
 */
class plgUserCivicrm extends JPlugin {

  /**
   * resetNavigation after user is saved
   * Method is called after user data is stored in the database
   *
   * @param array $user Holds the new user data.
   * @param bool $isnew True if a new user is stored.
   * @param bool $success True if user was successfully stored in the database.
   * @param string $msg Message.
   *
   * @return void
   * @since 1.6
   * @throws Exception on error.
   */
  function onUserAfterSave($user, $isnew, $success, $msg) {
    $app = JFactory::getApplication();
    self::civicrmResetNavigation();
  }

  /**
   * resetNavigation after group is saved (parent/child may impact acl)
   * Method is called after group is stored in the database
   *
   * @param string $var The event to trigger after saving the data.
   *
   * @return void
   * @since 1.6
   * @throws Exception on error.
   */
  function onUserAfterSaveGroup($var) {
    $app = JFactory::getApplication();
    self::civicrmResetNavigation();
  }

  /**
   * Delete uf_match record after user is deleted
   * Method is called after user is deleted from the database
   *
   * @param array $user Holds the user data.
   * @param bool $succes True if user was successfully removed from the database.
   * @param string $msg Message.
   *
   * @return void
   * @since 1.6
   * @throws Exception on error.
   */
  function onUserAfterDelete($user, $succes, $msg) {
    $app = JFactory::getApplication();

    // Instantiate CiviCRM
    require_once JPATH_ROOT . '/administrator/components/com_civicrm/civicrm.settings.php';
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();

    // Delete UFMatch
    CRM_Core_BAO_UFMatch::deleteUser($user['id']);
  }

  /**
   * Trigger navigation reset when the user logs in (admin only)
   *
   * @param object $user Joomla user object
   * @param array $options array of options to pass
   *
   * @return   void
   * @since    1.6
   */
  public function onUserLogin($user, $options = array()) {
    if (self::isAdminBackend()) {
      $jUser = JFactory::getUser();
      $jId = $jUser->get('id');
      self::civicrmResetNavigation($jId);
    }
  }

  /**
   * Reset CiviCRM user/contact navigation cache
   *
   * @param $jId - the logged in joomla ID if it exists
   *
   * @return void
   */
  public function civicrmResetNavigation($jId = NULL) {
    // Instantiate CiviCRM
    if (!class_exists('CRM_Core_Config')) {
      require_once JPATH_ROOT . '/administrator/components/com_civicrm/civicrm.settings.php';
      require_once 'CRM/Core/Config.php';
    }

    $config = CRM_Core_Config::singleton();

    $cId = NULL;

    //retrieve civicrm contact ID if joomla user ID is provided
    if ($jId) {
      $params = array(
        'version' => 3,
        'uf_id' => $jId,
        'return' => 'contact_id',
      );
      $result = civicrm_api('uf_match', 'getvalue', $params);
      // If no match is found, will return an error array
      if (is_int($result))
        $cId = $result;
    }

    // Reset Navigation
    CRM_Core_BAO_Navigation::resetNavigation($cId);
  }

  /**
   * Determine if we are in the Joomla administrator backend
   *
   * @return boolean True if in the Joomla Administrator backend otherwise false
   */
  private function isAdminBackend() {
    $app = JFactory::getApplication();

    // Determine if we are in the Joomla administrator backend
    // In Joomla 3.7+ the isClient() method is used. In earlier versions use the isAdmin() method (deprecated in Joomla 4.0).
    if (method_exists($app, 'isClient')) {
      $isAdmin = $app->isClient('administrator');
    }
    elseif (method_exists($app, 'isAdmin')) {
      $isAdmin = $app->isAdmin();
    }
    else {
      throw new \Exception("CiviCRM User Plugin error: no method found to determine Joomla client interface.");
    }

    return $isAdmin;
  }

}
