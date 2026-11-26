<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 5                                                  |
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

// Retrieve list of CiviCRM event types

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/*
 * Custom list field for Event Types.
 */
class JFormFieldCiviEventTypes extends \Joomla\CMS\Form\Field\ListField {

  /**
   * Element type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'CiviEventTypes';

  /**
   * Get list options.
   *
   * @return array
   */
  public function getOptions(): array {
    // Initiate CiviCRM
    define('CIVICRM_SETTINGS_PATH', JPATH_ROOT . '/' . 'administrator/components/com_civicrm/civicrm.settings.php');
    require_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();

    $eventTypes = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value', 'label')
      ->addJoin('OptionGroup AS option_group', 'INNER', ['option_group_id:name', '=', '"event_type"'])
      ->execute();
    // allow nothing to be selected (no filtering on event type)
    $options = ['' => ''];

    foreach ($eventTypes as $eventType) {
      $options[$eventType['value']] = $eventType['label'];
    }

    return $options;
  }
}
