<?php
// Retrieve list of CiviCRM events
// Active, current or future, online

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class JFormFieldCiviEventsOnline extends JFormField {

  /**
   * Element name
   *
   * @access	protected
   * @var		string
   */
  var $type = 'CiviEventsOnline';

  protected function getInput() {
    $value = $this->value;
    $name = $this->name;

    // Initiate CiviCRM
    define('CIVICRM_SETTINGS_PATH', JPATH_ROOT . '/' . 'administrator/components/com_civicrm/civicrm.settings.php');
    require_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();

    $params = array(
      'is_active' => 1,
      'is_online_registration' => 1,
      'return' => array("title"),
      'start_date' => array('>=' => "today"),
      'end_date' => array('>=' => "today"),
      'options' => array('sort' => "start_date", 'limit' => 0, 'or' => array(array("start_date", "end_date"))),
    );
    $events = civicrm_api3('Event', 'get', $params);
    $options = array();
    $htmlClass = version_compare(JVERSION, '4.0', 'ge') ? '\Joomla\CMS\HTML\HTMLHelper' : 'JHtml';

    foreach ($events['values'] as $event) {
      $options[] = $htmlClass::_('select.option', $event['id'], $event['event_title']);
    }

    return $htmlClass::_('select.genericlist', $options, $name, NULL, 'value', 'text', $value);
  }
}


