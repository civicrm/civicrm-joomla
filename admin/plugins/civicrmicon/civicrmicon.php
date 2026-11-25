<?php

/**
 * @copyright    Copyright (C) 2005 - 2014 CiviCRM LLC All rights reserved.
 * @license        GNU Affero General Public License version 2 or later
 */

defined('_JEXEC') or die;

/*** delete when no longer required ***
     Legacy code for J4 and J5
*/
if (version_compare(JVERSION, '5.4.8', 'lt')) {
  /**
   * CiviCRM QuickIcon plugin
   */
  class plgQuickiconCivicrmicon extends \Joomla\CMS\Plugin\CMSPlugin {

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

      $img = \Joomla\CMS\Uri\Uri::base() . 'components/com_civicrm/civicrm/i/smallLogo.png';
      $icon = [
        [
          'link' => 'index.php?option=com_civicrm',
          'image' => 'icon-civicrm',
          'text' => 'CiviCRM',
          'id' => 'plg_quickicon_civicrmicon',
          'class' => $context == 'mod_quickicon' ? 'success' : '',
        ],
      ];
      $css = "
        .icon-civicrm, .icon-civicrm-open {
          height:50px;width:50px;
          background-image:url(\"{$img}\");
          background-repeat: no-repeat;
        }
      ";
      $document = \Joomla\CMS\Factory::getApplication()->getDocument();
      $document->addStyleDeclaration($css);

      return $icon;
    }

  }

  return;
}
/*** delete to here ***/

/**
 * CiviCRM QuickIcon plugin
 */
class plgQuickiconCivicrmicon extends \Joomla\CMS\Plugin\CMSPlugin implements \Joomla\Event\SubscriberInterface {

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return  array
   */
  public static function getSubscribedEvents(): array {
    return [
      'onGetIcons' => 'onGetIcons',
    ];
  }

  /**
   * Handler for onGetIcons event.
   *
   * This method is called when the Quick Icons module is constructing its set
   * of icons. You can provide an array which defines a single icon and it will
   * be rendered right after the stock Quick Icons.
   *
   * @param QuickIconsEvent $event
   */
  public function onGetIcons(\Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent $event): void {
    // exclude CiviCRM quick icons from notification and system block
    $context = $event->getContext();
    if ($context == 'system_quickicon' || $context == 'update_quickicon') {
      return;
    }

    $result = $event->getArgument('result', []);
    $result[] = [
      [
        'link' => 'index.php?option=com_civicrm',
        'image' => 'icon-civicrm',
        'text' => 'CiviCRM',
        'id' => 'plg_quickicon_civicrmicon',
        'class' => $context == 'mod_quickicon' ? 'success' : '',
      ],
    ];
    $event->setArgument('result', $result);

    $img = \Joomla\CMS\Uri\Uri::base() . 'components/com_civicrm/civicrm/i/smallLogo.png';
    $css = "
      .icon-civicrm, .icon-civicrm-open {
        height:50px;width:50px;
        background-image:url(\"{$img}\");
        background-repeat: no-repeat;
      }
    ";
    $document = \Joomla\CMS\Factory::getApplication()->getDocument();
    $document->addStyleDeclaration($css);
  }

}
