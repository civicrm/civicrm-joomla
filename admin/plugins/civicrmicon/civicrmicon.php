<?php

/**
 * @copyright    Copyright (C) 2005 - 2014 CiviCRM LLC All rights reserved.
 * @license        GNU Affero General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * CiviCRM QuickIcon plugin
 */
class plgQuickiconCivicrmicon extends JPlugin {

  /**
   * This method is called when the Quick Icons module is constructing its set
   * of icons. You can return an array which defines a single icon and it will
   * be rendered right after the stock Quick Icons.
   *
   * @param  $context  The calling context
   *
   * @return array A list of icon definition associative arrays, consisting of the
   *                 keys link, image, text and access.
   *
   * @since       2.5
   */
  public function onGetIcons($context) {
    // exclude CiviCRM quick icons from notification and system block
    if ($context == 'system_quickicon' || $context == 'update_quickicon') {
      return [];
    }

    if (version_compare(JVERSION, '4.0', 'ge')) {
      $baseUri = \Joomla\CMS\Uri\Uri::base();
    }
    else {
      jimport('joomla.environment.uri');
      $baseUri = JURI::base();
    }

    $icon = array(
      array(
        'link' => 'index.php?option=com_civicrm',
        'image' => $baseUri . 'components/com_civicrm/civicrm/i/smallLogo.png',
        'text' => 'CiviCRM',
        'id' => 'plg_quickicon_civicrmicon',
        'class' => $context == 'mod_quickicon' ? 'success' : '',
      ),
    );

    //image must be handled via css class in J3.0+
    if (version_compare(JVERSION, '4.0', 'ge')) {
      $img = $icon[0]['image'];
      $css = "
        .icon-civicrm, .icon-civicrm-open {
          height:50px;width:50px;
          background-image:url(\"{$img}\");
          background-repeat: no-repeat;
        }
      ";
      $document = \Joomla\CMS\Factory::getApplication()->getDocument();
      $document->addStyleDeclaration($css);
      $icon[0]['image'] = 'icon-civicrm';
    }
    elseif (version_compare(JVERSION, '3.0', 'ge')) {
      $img = JURI::root() . 'plugins/quickicon/civicrmicon/smallLogo14.png';
      $css = "
        .icon-civicrm, .icon-civicrm-open {
          background-image:url(\"{$img}\");
          background-repeat: no-repeat;
        }
      ";
      $document = JFactory::getDocument();
      $document->addStyleDeclaration($css);
      $icon[0]['image'] = 'civicrm';
    }

    return $icon;
  }

}
