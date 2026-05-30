<?php

// No direct access.
defined('_JEXEC') or die;

// TODO: remove the below once JPlugin no longer referenced for J4+
if (version_compare(JVERSION, '4.0', 'ge')) {
  class _J3_to_J4_plgSystemCivicrmsys extends \Joomla\CMS\Plugin\CMSPlugin {
    // This is the base class for J4+
    // TODO: Refactor so that the class implements \Joomla\Event\SubscriberInterface
    //       and has the getSubscribedEvents() method. All of the event listeners will
    //       need to be rewritten for J6+ (once all concrete event classes are available)
    //       as both the method of retrieving parameters and returning values is changing.
    //       The legacy approach where method name = event name is supported through J5.
    //       See: https://docs.joomla.org/J4.x:Creating_a_Plugin_for_Joomla
    //            https://manual.joomla.org/docs/next/building-extensions/plugins/joomla-4-and-5-changes/
    // TODO: Tidy-up version_compare() based conditional code below as we migrate.
  }
}
else {
  class _J3_to_J4_plgSystemCivicrmsys extends JPlugin {
    // This is the base class for J3 and below.
  }
}

/**
 * Joomla! master extension plugin.
 *
 * @package    Civicrmsys.Plugin
 * @subpackage  System.Civicrmsys
 * @since    1.6
 */
class plgSystemCivicrmsys extends _J3_to_J4_plgSystemCivicrmsys {
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
   * After extension source code has been installed
   *
   * @param JInstaller $installer Installer object
   * @param int $eid Extension Identifier
   */
  public function onExtensionBeforeInstall() {
    // called by "Upload Package" use-case
    $this->scheduleCivicrmRebuild();
  }

  /**
   * After extension source code has been installed
   *
   * @param JInstaller $installer Installer object
   * @param int $eid Extension Identifier
   */
  public function onExtensionAfterInstall($installer, $eid) {
    if ($installer->extension instanceof JTableExtension && $installer->extension->folder == 'civicrm') {
      //x $args = func_get_args(); dump($args, 'onExtensionAfterInstall');
      $this->scheduleCivicrmRebuild();
    }
  }

  /**
   * After extension source code has been updated(?)
   *
   * @param JInstaller $installer Installer object
   * @param int $eid Extension Identifier
   */
  public function onExtensionAfterUpdate($installer, $eid) {
    // TODO test //if ($installer->extension instanceof JTableExtension && $installer->extension->folder == 'civicrm') {
    $this->scheduleCivicrmRebuild();
    //}
  }

  /**
   * After extension configuration has been saved
   */
  public function onExtensionAfterSave($type, $ext) {
    // Called by "Manage Plugins" use-case -- per-plugin forms
    if ($type == 'com_plugins.plugin' && $ext->folder == 'civicrm') {
      $this->scheduleCivicrmRebuild();
    }
  }

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
    register_shutdown_function(array($this, 'doCivicrmRebuild'));
    // dump(TRUE, 'scheduled');
    $this->scheduled = TRUE;
  }

  /**
   * Perform the actual rebuild
   */
  public function doCivicrmRebuild() {
    // dump($this, 'doCivicrmRebuild');
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

    if (version_compare(JVERSION, '4.0', 'lt')) {
      // before J4, this would actually try to load the app if not already loaded; it's not needed on J4+
      $app = JFactory::getApplication();
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
