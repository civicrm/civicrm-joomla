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


// Retrieve list of CiviCRM contribution pages
// Active
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// TODO: remove the below once JFormField no longer referenced for J4+
if (version_compare(JVERSION, '4.0', 'ge')) {
  class _J3_to_J4_JFormFieldCiviContribPages extends \Joomla\CMS\Form\FormField {
    // This is the base class for J4+
    // When we move away from J3 compatibility, there is an opportunity to
    // specialise to one of the pre-packaged classes in libraries/src/Form/Field
  }
}
else {
  class _J3_to_J4_JFormFieldCiviContribPages extends JFormField {
    // This is the base class for J3 and below.
  }
}

class JFormFieldCiviContribPages extends _J3_to_J4_JFormFieldCiviContribPages {

  /**
   * Element name
   *
   * @access  protected
   * @var     string
   */
  var $type = 'CiviContribPages';

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

    $options = array();
    $query = '
      SELECT id, title
      FROM civicrm_contribution_page
      WHERE is_active = 1
      ORDER BY title
    ';
    $dao = CRM_Core_DAO::executeQuery($query);
    $htmlClass = version_compare(JVERSION, '4.0', 'ge') ? '\Joomla\CMS\HTML\HTMLHelper' : 'JHtml';

    while ($dao->fetch()) {
      $options[] = $htmlClass::_('select.option', $dao->id, $dao->title);
    }

    return $htmlClass::_('select.genericlist', $options, $name,
      NULL, 'value', 'text', $value, $name
    );
  }
}


