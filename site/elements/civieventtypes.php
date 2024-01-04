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

// Retrieve list of CiviCRM events
// Active, current or future

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
class JFormFieldCiviEventTypes extends JFormField
{

  /**
   * Element name
   *
   * @access  protected
   * @var     string
   */
  var $type = 'CiviEventTypes';

  protected function getInput()
  {
    $value = $this->value;
    $name = $this->name;

    // Initiate CiviCRM
    define('CIVICRM_SETTINGS_PATH', JPATH_ROOT . '/' . 'administrator/components/com_civicrm/civicrm.settings.php');
    require_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();

    $groupIdForEventTypes = $this->getGroupIdForEventTypes();

    $eventTypes = $this->getEventTypes($groupIdForEventTypes);

    $options = array();
    $options[] = JHTML::_('select.option', '', ''); // Add an empty first option
    foreach ($eventTypes as $eventType) {
      $options[] = JHTML::_('select.option', $eventType['value'], $eventType['label']);
    }

    return JHTML::_('select.genericlist', $options, $name, NULL, 'value', 'text', $value);
  }

    private function getEventTypes($groupIdForEventTypes)
    {
        $eventTypeResults = \Civi\Api4\OptionValue::get(TRUE)
          ->addWhere('option_group_id', '=', $groupIdForEventTypes)
          ->execute();
        return iterator_to_array($eventTypeResults);
    }

    private function getGroupIdForEventTypes()
    {
        $groupIdResults = \Civi\Api4\OptionGroup::get(TRUE)
          ->addSelect('id')
          ->addWhere('name', '=', 'event_type')
          ->execute();
        $groupIdForEventTypes = current(iterator_to_array($groupIdResults))['id'];

        return $groupIdForEventTypes;
    }
}
