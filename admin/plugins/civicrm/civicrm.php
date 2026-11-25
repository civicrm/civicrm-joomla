<?php
/**
 * @copyright   Copyright (C) 2005 - 2014 CiviCRM LLC All rights reserved.
 * @license     GNU Affero General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;

/*** delete when no longer required ***
     Legacy code for J4 and J5
*/
if (version_compare(JVERSION, '5.4.8', 'lt')) {
  /**
   * CiviCRM User Management Plugin
   *
   * @package Joomla
   * @subpackage JFramework
   * @since 1.6
   */
  class plgUserCivicrm extends \Joomla\CMS\Plugin\CMSPlugin {
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
        $jUser = \Joomla\CMS\Factory::getApplication()->getIdentity();
        $jId = $jUser->id ?? 0;
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
      $app = \Joomla\CMS\Factory::getApplication();

      // Determine if we are in the Joomla administrator backend
      $isAdmin = $app->isClient('administrator');
      return $isAdmin;
    }

  }

  return;
}
/*** delete to here ***/

/**
 * CiviCRM User Management Plugin
 *
 * @package Joomla
 * @subpackage JFramework
 * @since 5.4.8
 */
class plgUserCivicrm extends \Joomla\CMS\Plugin\CMSPlugin implements \Joomla\Event\SubscriberInterface {

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return  array
   */
  public static function getSubscribedEvents(): array {
    return [
      'onUserAfterSave' => 'onUserAfterSave',
      'onUserAfterSaveGroup' => 'onUserAfterSaveGroup',
      'onUserAfterDelete' => 'onUserAfterDelete',
      'onUserAfterLogin' => 'onUserAfterLogin',
    ];
  }

  /**
   * Handler for onUserAfterSave event.
   *
   * resetNavigation after user is saved
   * Method is called after user data is stored in the database
   *
   * @param AfterSaveEvent $event
   */
  public function onUserAfterSave(\Joomla\CMS\Event\User\AfterSaveEvent $event): void {
    self::civicrmResetNavigation();
  }

  /**
   * Handler for onUserAfterSaveGroup event.
   *
   * resetNavigation after group is saved (parent/child may impact acl)
   * Method is called after group is stored in the database
   *
   * @param AfterSaveEvent $event
   */
  public function onUserAfterSaveGroup(\Joomla\CMS\Event\Model\AfterSaveEvent $event): void {
    self::civicrmResetNavigation();
  }

  /**
   * Handler for onUserAfterDelete event.
   *
   * Delete uf_match record after user is deleted
   * Method is called after user is deleted from the database
   *
   * @param AfterDeleteEvent $event
   */
  public function onUserAfterDelete(\Joomla\CMS\Event\User\AfterDeleteEvent $event): void {
    // Instantiate CiviCRM
    if (!class_exists('CRM_Core_Config')) {    
      require_once JPATH_ROOT . '/administrator/components/com_civicrm/civicrm.settings.php';
      require_once 'CRM/Core/Config.php';
    }
    $config = CRM_Core_Config::singleton();

    // Delete UFMatch
    $user = $event->getUser();
    CRM_Core_BAO_UFMatch::deleteUser($user['id']);
  }

  /**
   * Handler for onUserAfterLogin event.
   *
   * Trigger navigation reset when the user logs in (admin only)
   *
   * @param AfterLoginEvent $event
   */
  public function onUserAfterLogin(\Joomla\CMS\Event\User\AfterLoginEvent $event): void {
    if (self::isAdminBackend()) {
      $jUser = \Joomla\CMS\Factory::getApplication()->getIdentity();
      $jId = $jUser->id ?? NULL;
      self::civicrmResetNavigation($jId);
    }
  }

  /**
   * Reset CiviCRM user/contact navigation cache
   *
   * @param int|NULL $jId - the logged in joomla ID if it exists
   *
   * @return void
   */
  private function civicrmResetNavigation(?int $jId = NULL): void {
    // Instantiate CiviCRM
    if (!class_exists('CRM_Core_Config')) {
      require_once JPATH_ROOT . '/administrator/components/com_civicrm/civicrm.settings.php';
      require_once 'CRM/Core/Config.php';
    }
    $config = CRM_Core_Config::singleton();

    $cId = NULL;

    //retrieve civicrm contact ID if joomla user ID is provided
    if ($jId) {
      $cId = \Civi\Api4\UFMatch::get(FALSE)
        ->addSelect('contact_id')
        ->addWhere('uf_id', '=', $jId)
        ->execute()
        ->first()['contact_id'] ?? NULL;
    }

    // Reset Navigation
    CRM_Core_BAO_Navigation::resetNavigation($cId);
  }

  /**
   * Determine if we are in the Joomla administrator backend
   *
   * @return boolean True if in the Joomla Administrator backend otherwise false
   */
  private function isAdminBackend(): bool {
    $app = \Joomla\CMS\Factory::getApplication();

    // Determine if we are in the Joomla administrator backend
    $isAdmin = $app->isClient('administrator');
    return $isAdmin;
  }

}
