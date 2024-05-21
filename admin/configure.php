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


// escape early if called directly
defined('_JEXEC') or die('No direct access allowed');

global $civicrmUpgrade;
$civicrmUpgrade = FALSE;

/**
 * If present, convert "admin/civicrm.zip" to "admin/civicrm/".
 *
 * @param string $adminPath
 * @return void
 */
function civicrm_extract_code(string $adminPath) {
  $archivename = $adminPath . DIRECTORY_SEPARATOR . 'civicrm.zip';

  // a bit of support for the non-alternaive joomla install
  if (file_exists($archivename)) {
    // ensure that the site has native zip, else abort
    if (
      !function_exists('zip_open') ||
      !function_exists('zip_read')
    ) {
      echo "Your PHP version is missing  zip functionality. Please ask your system administrator / hosting provider to recompile PHP with zip support.<p>";
      echo "If this is a new install, you will need to uninstall CiviCRM from the Joomla Extension Manager.<p>";
      exit();
    }

    $extractdir = $adminPath;
    $archive = new Joomla\Archive\Archive();
    $archive->extract($archivename, $extractdir);
  }
}

function civicrm_main() {
  global $civicrmUpgrade, $adminPath;

  // Check for php version and ensure its greater than minPhpVersion
  $minPhpVersion = '7.4.0';
  if (version_compare(PHP_VERSION, $minPhpVersion) < 0) {
    echo "CiviCRM requires PHP version $minPhpVersion or greater. You are running PHP version " . PHP_VERSION . "<p>";
    exit();
  }

  $adminPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_civicrm';
  civicrm_extract_code($adminPath);

  $civicrmUpgrade = civicrm_detect_upgrade();

  $setup = civicrm_setup_instance($adminPath, $civicrmUpgrade);

  civicrm_backend_config($setup->getModel()->settingsPath, $adminPath);
  $setup->installFiles();
  if (!$civicrmUpgrade) {
    $setup->installDatabase();
  }
}

/**
 * Generate a "civicrm.config.php" file in the civicrm app-root.
 * This (probably) allows backend tools like "extern/rest.php" or "bin/cli.php".
 *
 * @param string $configFile
 * @param $adminPath
 * @return string
 */
function civicrm_backend_config(string $configFile, $adminPath): string {
  $string = "
<?php
define('CIVICRM_SETTINGS_PATH', '$configFile');
\$error = @include_once( '$configFile' );
if ( \$error == false ) {
    echo \"Could not load the settings file at: {$configFile}\n\";
    exit( );
}

// Load class loader
require_once \$civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();
";

  $string = trim($string);
  JFile::write($adminPath . DIRECTORY_SEPARATOR .
    'civicrm' . DIRECTORY_SEPARATOR .
    'civicrm.config.php',
    $string
  );
  return $string;
}

/**
 * @param string $adminPath
 * @return \Civi\Setup
 * @throws \Exception
 */
function civicrm_setup_instance(string $adminPath, bool $civicrmUpgrade): \Civi\Setup {
  $civicrmCore = $adminPath . DIRECTORY_SEPARATOR . 'civicrm';

  require_once implode(DIRECTORY_SEPARATOR, [$civicrmCore, 'CRM', 'Core', 'ClassLoader.php']);
  CRM_Core_ClassLoader::singleton()->register();
  \Civi\Setup::assertProtocolCompatibility(1.0);
  \Civi\Setup::init([
    'cms' => 'Joomla',
    'srcPath' => $civicrmCore,
    'db' => ['fixme'],
    'settingsPath' => $adminPath . DIRECTORY_SEPARATOR . 'civicrm.settings.php',
  ]);
  $setup = Civi\Setup::instance();
  $model = $setup->getModel();

  $jConfig = new JConfig();
  $model->cmsBaseUrl = substr_replace(JURI::root(), '', -1, 1);
  $model->cmsDb = [
    'username' => $jConfig->user,
    'password' => $jConfig->password,
    'server' => $jConfig->host,
    'database' => $jConfig->db,
  ];
  $model->db = $model->cmsDb;
  $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [JPATH_SITE, 'media', 'civicrm', 'templates_c']);
  $model->lang = 'en_US'; /* Joomla installer historically only did `civicrm_data.mysql`. Should fix this... */

  if ($civicrmUpgrade) {
    require_once $setup->getModel()->settingsPath;
    if (defined('CIVICRM_DSN')) {
      $civiDSNParts = parse_url(CIVICRM_DSN);
      $model->db['username'] = $civiDSNParts['user'];
      $model->db['password'] = urldecode($civiDSNParts['pass']);
      $model->db['server'] = $civiDSNParts['host'];
      $model->db['database'] = substr($civiDSNParts['path'], 1);
    }
    if (defined('CIVICRM_SITE_KEY')) {
      $setup->getModel()->siteKey = CIVICRM_SITE_KEY;
    }
    if (defined('CIVICRM_CRED_KEYS')) {
      $setup->getModel()->credKeys = explode(' ', CIVICRM_CRED_KEYS);
    }
    if (defined('CIVICRM_SIGN_KEYS')) {
      $setup->getModel()->signKeys = explode(' ', CIVICRM_SIGN_KEYS);
    }
  }

  return $setup;
}

/**
 * @return bool
 *   TRUE if this installation operation is actually an upgrade.
 */
function civicrm_detect_upgrade(): bool {
  global $adminPath;
  $configFile = $adminPath . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
  $jConfig = new JConfig();
  $database = $jConfig->db;

  if (is_readable($configFile)) {
    require_once $configFile;

    if (defined("CIVICRM_DSN")) {
      $civiDSNParts = parse_url(CIVICRM_DSN);
      $database = substr($civiDSNParts['path'], 1);
    }
  }

  $db = JFactory::getDBO();
  $db->setQuery(' SELECT count( * )
FROM information_schema.tables
WHERE table_name LIKE "civicrm_domain"
AND table_schema = "' . $database . '" ');

  $civicrmUpgrade = ($db->loadResult() == 0) ? FALSE : TRUE;
  return $civicrmUpgrade;
}

set_time_limit(4000); /* Ex: ZIP extraction */
civicrm_main();
