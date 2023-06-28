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

function civicrm_write_file($name, &$buffer) {
  JFile::write($name, $buffer);
}

function civicrm_main() {
  global $civicrmUpgrade, $adminPath;

  $civicrmUpgrade = civicrm_detect_upgrade();

  // Check for php version and ensure its greater than minPhpVersion
  $minPhpVersion = '7.3.0';
  if (version_compare(PHP_VERSION, $minPhpVersion) < 0) {
    echo "CiviCRM requires PHP version $minPhpVersion or greater. You are running PHP version " . PHP_VERSION . "<p>";
    exit();
  }

  $adminPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_civicrm';
  civicrm_extract_code($adminPath);
  $setup = civicrm_setup_instance($adminPath, $civicrmUpgrade);

  civicrm_backend_config($setup->getModel()->settingsPath, $adminPath);
  $setup->installFiles();

  define('CIVICRM_SETTINGS_PATH', $setup->getModel()->settingsPath);
  include_once CIVICRM_SETTINGS_PATH;

  // for install case only
  if (!$civicrmUpgrade) {
    $sqlPath = $adminPath . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'sql';

    civicrm_source($sqlPath . DIRECTORY_SEPARATOR . 'civicrm.mysql');
    civicrm_source($sqlPath . DIRECTORY_SEPARATOR . 'civicrm_data.mysql');

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();

    // now also build the menu
    require_once 'CRM/Core/Menu.php';
    CRM_Core_Menu::store();
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
  civicrm_write_file($adminPath . DIRECTORY_SEPARATOR .
    'civicrm' . DIRECTORY_SEPARATOR .
    'civicrm.config.php',
    $string
  );
  return $string;
}

function civicrm_source($fileName, $lineMode = FALSE) {

  if (!defined('DB_DSN_MODE')) {
    define('DB_DSN_MODE', 'auto');
  }

  $dsn = CIVICRM_DSN;

  require_once 'DB.php';

  $db = DB::connect($dsn);
  if (PEAR::isError($db)) {
    die("Cannot open $dsn: " . $db->getMessage());
  }

  if (!$lineMode) {
    $string = file_get_contents($fileName);

    //get rid of comments starting with # and --
    $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
    $string = preg_replace("/^\-\-[^\n]*$/m", "\n", $string);

    $queries = preg_split('/;\s*$/m', $string);

    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        $res =& $db->query($query);
        if (PEAR::isError($res)) {
          die("Cannot execute $query: " . $res->getMessage());
        }
      }
    }
  }
  else {
    $fd = fopen($fileName, "r");
    while ($string = fgets($fd)) {
      $string = ereg_replace("\n#[^\n]*\n", "\n", $string);
      $string = ereg_replace("\n\-\-[^\n]*\n", "\n", $string);
      $string = trim($string);
      if (!empty($string)) {
        $res =& $db->query($string);
        if (PEAR::isError($res)) {
          die("Cannot execute $string: " . $res->getMessage());
        }
      }
    }
  }
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

  if ($civicrmUpgrade) {
    require_once $setup->getModel()->settingsPath;
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
  $jConfig = new JConfig();
  $db = JFactory::getDBO();
  $db->setQuery(' SELECT count( * )
FROM information_schema.tables
WHERE table_name LIKE "civicrm_domain"
AND table_schema = "' . $jConfig->db . '" ');

  $civicrmUpgrade = ($db->loadResult() == 0) ? FALSE : TRUE;
  return $civicrmUpgrade;
}

set_time_limit(4000); /* Ex: ZIP extraction */
civicrm_main();
