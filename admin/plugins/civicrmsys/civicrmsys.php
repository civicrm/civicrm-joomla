<?php

// No direct access.
defined('_JEXEC') or die;

/*** delete when no longer required ***
     Legacy code for J4 and J5
*/
if (version_compare(JVERSION, '5.4.8', 'lt')) {
  /**
   * Joomla! master extension plugin.
   *
   * @package       Civicrmsys.Plugin
   * @subpackage    System.Civicrmsys
   * @since         1.6
   */
  class plgSystemCivicrmsys extends \Joomla\CMS\Plugin\CMSPlugin {
    public $scheduled;

    public function onBeforeCompileHead() {
      global $civicrm_root;
      if (empty($civicrm_root)) {
        return;
      }
      if ($region = CRM_Core_Region::instance('html-header', FALSE)) {
        CRM_Utils_System::addHTMLHead($region->render(''));
      }
    }

    /**
     * Before extension source code has been installed
     */
    public function onExtensionBeforeInstall() {
      // called by "Upload Package" use-case
      $this->scheduleCivicrmRebuild();
    }

    /**
     * After extension source code has been installed
     *
     * @param Installer $installer Installer object
     * @param int $eid Extension Identifier
     */
    public function onExtensionAfterInstall($installer, $eid) {
      if (isset($installer->extension->folder) && ($installer->extension->folder == 'civicrm')) {
        $this->scheduleCivicrmRebuild();
      }
    }

    /**
     * After extension source code has been updated
     *
     * @param Installer $installer Installer object
     * @param int $eid Extension Identifier
     */
    public function onExtensionAfterUpdate($installer, $eid) {
      // TODO test //if (isset($installer->extension->folder) && ($installer->extension->folder == 'civicrm')) {
      $this->scheduleCivicrmRebuild();
      //}
    }

    /**
     * After extension configuration has been saved
     *
     * @param string $type Context of save operation
     * @param Extension $ext Extension table object
     */
    public function onExtensionAfterSave($type, $ext) {
      // Called by "Manage Plugins" use-case -- per-plugin forms
      if ($type == 'com_plugins.plugin' && $ext->folder == 'civicrm') {
        $this->scheduleCivicrmRebuild();
      }
    }

    /**
     * After Joomla! cache is cleaned
     *
     * @param string $defaultgroup The group targeted by the call
     * @param string $cachebase The root directory path where the cache files are stored
     */
    public function onContentCleanCache($defaultgroup, $cachebase) {
      // Called by "Manage Plugins" use-case -- both bulk operations and per-plugin forms
      if ($defaultgroup == 'com_plugins') {
        $this->scheduleCivicrmRebuild();
      }
    }

    /**
     * After extension source code has been removed
     *
     * @param Installer $installer Installer object
     * @param int $eid Extension Identifier
     * @param bool $result Whether the removal was successful
     */
    public function onExtensionAfterUninstall($installer, $eid, $result) {
      $this->scheduleCivicrmRebuild();
    }

    /**
     * Ensure that the rebuild will be done
     */
    public function scheduleCivicrmRebuild() {
      if ($this->scheduled) {
        return;
      }
      register_shutdown_function([$this, 'doCivicrmRebuild']);
      $this->scheduled = TRUE;
    }

    /**
     * Perform the actual rebuild
     */
    public function doCivicrmRebuild() {
      $this->bootstrap();
      CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
    }

    /**
     * Make sure that CiviCRM is loaded
     */
    protected function bootstrap() {
      if (defined('CIVICRM_UF')) {
        // already loaded settings
        return;
      }

      define('CIVICRM_SETTINGS_PATH',
        JPATH_ROOT . '/' . 'administrator/components/com_civicrm/civicrm.settings.php');
      require_once CIVICRM_SETTINGS_PATH;

      require_once 'CRM/Core/ClassLoader.php';
      CRM_Core_ClassLoader::singleton()->register();

      require_once 'CRM/Core/Config.php';
      $civiConfig = CRM_Core_Config::singleton();
    }

}

  return;
}
/*** delete to here ***/

/**
 * Joomla! master extension plugin.
 *
 * @package     Civicrmsys.Plugin
 * @subpackage  System.Civicrmsys
 * @since       5.4.8
 */
class plgSystemCivicrmsys extends \Joomla\CMS\Plugin\CMSPlugin implements \Joomla\Event\SubscriberInterface {
  private $scheduled;

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return  array
   */
  public static function getSubscribedEvents(): array {
    return [
      'onBeforeCompileHead' => 'onBeforeCompileHead',
      'onExtensionBeforeInstall' => 'onExtensionBeforeInstall',
      'onExtensionAfterInstall' => 'onExtensionAfterInstall',
      'onExtensionAfterUpdate' => 'onExtensionAfterUpdate',
      'onExtensionAfterSave' => 'onExtensionAfterSave',
      'onContentCleanCache' => 'onContentCleanCache',
      'onExtensionAfterUninstall' => 'onExtensionAfterUninstall',
    ];
  }

  /**
   * Handler for onBeforeCompileHead event.
   *
   * @param BeforeCompileHeadEvent $event
   */
  public function onBeforeCompileHead(\Joomla\CMS\Event\Application\BeforeCompileHeadEvent $event): void {
    global $civicrm_root;
    if (empty($civicrm_root)) {
      return;
    }
    if ($region = CRM_Core_Region::instance('html-header', FALSE)) {
      CRM_Utils_System::addHTMLHead($region->render(''));
    }
  }

  /**
   * Handler for onExtensionBeforeInstall event.
   *
   * Before extension source code has been installed
   *
   * @param BeforeInstallEvent $event
   */
  public function onExtensionBeforeInstall(\Joomla\CMS\Event\Extension\BeforeInstallEvent $event): void {
    // called by "Upload Package" use-case
    $this->scheduleCivicrmRebuild();
  }

  /**
   * Handler for onExtensionAfterInstall event.
   *
   * After extension source code has been installed
   *
   * @param AfterInstallEvent $event
   */
  public function onExtensionAfterInstall(\Joomla\CMS\Event\Extension\AfterInstallEvent $event): void {
    $installer = $event->getInstaller();
    if (($installer->extension->folder ?? '') == 'civicrm') {
      $this->scheduleCivicrmRebuild();
    }
  }

  /**
   * Handler for onExtensionAfterUpdate event.
   *
   * After extension source code has been updated
   *
   * @param AfterUpdateEvent $event
   */
  public function onExtensionAfterUpdate(\Joomla\CMS\Event\Extension\AfterUpdateEvent $event): void {
    //$installer = $event->getInstaller();
    // TODO test //if (($installer->extension->folder ?? '') == 'civicrm') {
    $this->scheduleCivicrmRebuild();
    //}
  }

  /**
   * Handler for onExtensionAfterSave event.
   *
   * After extension configuration has been saved
   *
   * @param AfterSaveEvent $event
   */
  public function onExtensionAfterSave(\Joomla\CMS\Event\Model\AfterSaveEvent $event): void {
    // Called by "Manage Plugins" use-case -- per-plugin forms
    $context = $event->getContext();
    $folder = $event->getItem()->folder ?? '';
    if ($context == 'com_plugins.plugin' && $folder == 'civicrm') {
      $this->scheduleCivicrmRebuild();
    }
  }

  /**
   * Handler for onContentCleanCache event.
   *
   * After Joomla! cache is cleaned
   *
   * @param AfterCleanCacheEvent $event
   */
  public function onContentCleanCache(\Joomla\CMS\Event\Model\AfterCleanCacheEvent $event): void {
    // Called by "Manage Plugins" use-case -- both bulk operations and per-plugin forms
    if ($event->getDefaultGroup() == 'com_plugins') {
      $this->scheduleCivicrmRebuild();
    }
  }

  /**
   * Handler for onExtensionAfterUninstall event.
   *
   * After extension source code has been removed
   *
   * @param AfterUninstallEvent $event
   */
  public function onExtensionAfterUninstall(\Joomla\CMS\Event\Extension\AfterUninstallEvent $event): void {
    $this->scheduleCivicrmRebuild();
  }

  /**
   * Ensure that the rebuild will be done
   */
  private function scheduleCivicrmRebuild(): void {
    if ($this->scheduled) {
      return;
    }
    register_shutdown_function($this->doCivicrmRebuild(...));
    $this->scheduled = TRUE;
  }

  /**
   * Perform the actual rebuild
   */
  private function doCivicrmRebuild(): void {
    $this->bootstrap();
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  /**
   * Make sure that CiviCRM is loaded
   */
  private function bootstrap(): void {
    if (defined('CIVICRM_UF')) {
      // already loaded settings
      return;
    }

    define('CIVICRM_SETTINGS_PATH',
      JPATH_ROOT . '/' . 'administrator/components/com_civicrm/civicrm.settings.php');
    require_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    require_once 'CRM/Core/Config.php';
    $civiConfig = CRM_Core_Config::singleton();
  }

}
