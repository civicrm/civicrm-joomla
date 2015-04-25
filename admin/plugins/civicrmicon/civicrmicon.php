<?php

/**
 * @copyright	Copyright (C) 2005 - 2014 CiviCRM LLC All rights reserved.
 * @license		GNU Affero General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * CiviCRM QuickIcon plugin
 */
class plgQuickiconCivicrmicon extends JPlugin {

  /**
   * Constructor
   *
   * @param       object  $subject The object to observe
   * @param       array   $config  An array that holds the plugin configuration
   *
   * @since       2.5
   */
  public function __construct(&$subject, $config) {
    parent::__construct($subject, $config);
    $this->loadLanguage();
  }

  /**
   * This method is called when the Quick Icons module is constructing its set
   * of icons. You can return an array which defines a single icon and it will
   * be rendered right after the stock Quick Icons.
   *
   * @param  $context  The calling context
   *
   * @return array A list of icon definition associative arrays, consisting of the
   *				 keys link, image, text and access.
   *
   * @since       2.5
   */
  public function onGetIcons($context) {
    jimport('joomla.environment.uri');
    $icon = array(
      array(
        'link' => 'index.php?option=com_civicrm',
        'image' => JURI::base() . 'components/com_civicrm/civicrm/i/smallLogo.png',
        'text' => 'CiviCRM',
        'id' => 'plg_quickicon_civicrmicon',
      )
    );

    //image must be handled via css class in J3.0
    if (version_compare(JVERSION, '3.0', 'ge')) {
      $img = JURI::root() . 'plugins/quickicon/civicrmicon/smallLogo14.png';
      $css = '
        .icon-civicrm, .icon-civicrm-open {
          background-image:url("'.$img.'");
        }
      ';
      $document = JFactory::getDocument();
      $document->addStyleDeclaration( $css );
      $icon[0]['image'] = 'civicrm';
    } else {
      $icon[0]['image'] = JURI::base() . 'components/com_civicrm/civicrm/i/smallLogo.png';
    }

    return $icon;
  }
}

