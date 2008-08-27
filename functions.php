<?php
/*

SQL Buddy - Web based MySQL administration
http://www.sqlbuddy.com/

functions.php
- collection of functions for use in other files

MIT license

2008 Calvin Lough <http://calv.in>

*/

if (!session_id())
	session_start();

include "config.php";
include "includes/types.php";
include "includes/gettextreader.php";

define("VERSION_NUMBER", "1.2.9");
define("PREVIEW_CHAR_SIZE", 65);

$cookieLength = time() + (60*24*60*60);

$langList['de_DE'] = "Deutsch";
$langList['en_US'] = "English";
$langList['es_ES'] = "Español";
$langList['it_IT'] = "Italiano";
$langList['nl_NL'] = "Nederlands";
$langList['pl_PL'] = "Polski";
$langList['pt_BR'] = "Português (Brasil)";
$langList['ru_RU'] = "Русский";
$langList['sv_SE'] = "Svenska";
$langList['tl_PH'] = "Tagalog";
$langList['zh_CN'] = "中文 (简体)";
$langList['zh_TW'] = "中文 (繁體)";

if (isset($_COOKIE['sb_lang']) && array_key_exists($_COOKIE['sb_lang'], $langList))
{
	$lang = preg_replace("/[^a-z0-9_]/i", "", $_COOKIE['sb_lang']);
}
else
{
	$lang = "en_US";
}

if ($lang != "en_US")
{
	// extend the cookie length
	setcookie("sb_lang", $lang, $cookieLength);
}
else if (isset($_COOKIE['sb_lang']))
{
	// cookie not needed for en_US
	setcookie("sb_lang", "", time() - 10000);
}

$themeList["classic"] = "Classic";
$themeList["bittersweet"] = "Bittersweet";

if (isset($_COOKIE['sb_theme']))
{
	$currentTheme = preg_replace("/[^a-z0-9_]/i", "", $_COOKIE['sb_theme']);
	
	if (array_key_exists($currentTheme, $themeList))
	{
		$theme = $currentTheme;
		
		// extend the cookie length
		setcookie("sb_theme", $theme, $cookieLength);
	}
	else
	{
		$theme = "bittersweet";
		setcookie("sb_theme", "", time() - 10000);
	}
}
else
{
	$theme = "bittersweet";
}

$gt = new GetTextReader($lang . ".pot");

$dbConnection = getDatabaseConnection();

// unique identifer for this session, to validate ajax requests.
// document root is included because it is likely a difficult value
// for potential attackers to guess
$requestKey = substr(sha1(session_id() . $_SERVER["DOCUMENT_ROOT"]), 0, 16);

if ($dbConnection)
{
	if (isset($_GET['db']))
		$db = mysql_real_escape_string($_GET['db']);
	
	if (isset($_GET['table']))
		$table = mysql_real_escape_string($_GET['table']);
	
	$charsetSql = mysql_query("SHOW CHARACTER SET");
	if (@mysql_num_rows($charsetSql))
	{
		while ($charsetRow = mysql_fetch_assoc($charsetSql))
		{
			$charsetList[] = $charsetRow['Charset'];
		}
	}
	
	$collationSql = mysql_query("SHOW COLLATION");
	if (@mysql_num_rows($collationSql))
	{
		while ($collationRow = mysql_fetch_assoc($collationSql))
		{
			$collationList[$collationRow['Collation']] = $collationRow['Charset'];
		}
	}
}

// undo magic quotes, if necessary
if (get_magic_quotes_gpc())
{
	$_GET = stripslashesFromArray($_GET);
	$_POST = stripslashesFromArray($_POST);
	$_COOKIE = stripslashesFromArray($_COOKIE);
	$_REQUEST = stripslashesFromArray($_REQUEST);
}

function stripslashesFromArray($value)
{
    $value = is_array($value) ?
                array_map('stripslashesFromArray', $value) :
                stripslashes($value);

    return $value;
}

function loginCheck($validateReq = true)
{
	if (!isset($_SESSION['SB_LOGIN'])){
		if (isset($_GET['ajaxRequest']))
			redirect("login.php?timeout=1");
		else
			redirect("login.php");
		exit;
	}
	if (isset($validateReq))
	{
		if (!validateRequest())
		{
			exit;
		}
	}
	
	startOutput();
}

function redirect($url)
{
	if (isset($_GET['ajaxRequest']) || headers_sent())
	{
		global $requestKey;
		?>
		<script type="text/javascript" authkey="<?php echo $_GET['requestKey']; ?>">
		
		document.location = "<?php echo $url; ?>" + window.location.hash;
		
		</script>
		<?php
	}
	else
	{
		header("Location: $url");
	}
	exit;
}

function validateRequest()
{
	global $requestKey;
	if (isset($_GET['requestKey']) && $_GET['requestKey'] != $requestKey)
	{
		return false;
	}
	return true;
}

function startOutput()
{
	if (!headers_sent())
	{
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-type: text/html; charset=UTF-8");
	}
}

function outputPage($title = "")
{

global $requestKey;
global $sbconfig;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/REC-html40/strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" version="-//W3C//DTD XHTML 1.1//EN" xml:lang="en">
	<head>
		<title>SQL Buddy</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<link type="text/css" rel="stylesheet" href="<?php echo smartCaching("css/common.css"); ?>" />
		<link type="text/css" rel="stylesheet" href="<?php echo smartCaching("css/navigation.css"); ?>" />
		<link type="text/css" rel="stylesheet" href="<?php echo outputThemeFile("css/main.css"); ?>" />
		<!--[if lte IE 7]>
    		<link type="text/css" rel="stylesheet" href="<?php echo outputThemeFile("css/ie.css"); ?>" />
		<![endif]-->
		<script type="text/javascript" src="<?php echo smartCaching("js/mootools-1.2-core.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo smartCaching("js/helpers.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo smartCaching("js/core.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo smartCaching("js/animation.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo smartCaching("js/columnsize.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo smartCaching("js/drag.js"); ?>"></script>
		<script type="text/javascript" src="<?php echo smartCaching("js/resize.js"); ?>"></script>
	</head>
	<body>
	<div id="container">
	<div id="header">
		<div id="headerlogo">
		<a href="#page=home" onclick="sideMainClick('home.php', 0); return false;"><img src="images/logo.png" /></a>
		</div>
		<div id="toptabs"><ul></ul></div>
		<div id="headerinfo">
		<span id="load" style="display: none"><?php echo __("Loading..."); ?></span>
		<?php
		
		// if set to auto login, providing a link to logout wouldnt be much good
		if (!isset($sbconfig['DefaultPass']))
			echo '<a href="logout.php">' . __("Logout") . '</a>';
		
		?>
		</div>
		<div class="clearer"></div>
	</div>
	
	<div id="bottom">
	
	<div id="leftside">
		<div id="sidemenu">
		<div class="dblist">
		<ul>
		<li id="sidehome"><a href="#page=home" onclick="sideMainClick('home.php', 0); return false;"><div class="menuicon">&gt;</div><div class="menutext"><?php echo __("Home"); ?></div></a></li>
		<li id="sideusers"><a href="#page=users&topTab=1" onclick="sideMainClick('users.php', 1); return false;"><div class="menuicon">&gt;</div><div class="menutext"><?php echo __("Users"); ?></div></a></li>
		<li id="sidequery"><a href="#page=query&topTab=2" onclick="sideMainClick('query.php', 2); return false;"><div class="menuicon">&gt;</div><div class="menutext"><?php echo __("Query"); ?></div></a></li>
		<li id="sideimport"><a href="#page=import&topTab=3" onclick="sideMainClick('import.php', 3); return false;"><div class="menuicon">&gt;</div><div class="menutext"><?php echo __("Import"); ?></div></a></li>
		<li id="sideexport"><a href="#page=export&topTab=4" onclick="sideMainClick('export.php', 4); return false;"><div class="menuicon">&gt;</div><div class="menutext"><?php echo __("Export"); ?></div></a></li>
		</ul></div>
		
		<div class="dblistheader"><?php echo __("Databases"); ?></div>
		<div class="dblist" id="databaselist"><ul></ul></div>
		</div>
	</div>
	<div id="rightside">
		
		<div id="content">
			<div class="corners"><div class="tl"></div><div class="tr"></div></div>
			<div id="innercontent"></div>
			<div class="corners"><div class="bl"></div><div class="br"></div></div>
		</div>
		
		</div>
		
	</div>
	</div>
	
	</body>
	<script type="text/javascript">
	<?php
	
	if (isset($requestKey))
	{
		echo 'var requestKey = "' . $requestKey . '";';
		echo "\n";
	}
	
	// javascript translation strings
	echo "\t\tvar getTextArr = {";
	echo '"Home":"' . __("Home") . '", ';
	echo '"Users":"' . __("Users") . '", ';
	echo '"Query":"' . __("Query") . '", ';
	echo '"Import":"' . __("Import") . '", ';
	echo '"Export":"' . __("Export") . '", ';
	
	echo '"Overview":"' . __("Overview") . '", ';
	
	echo '"Browse":"' . __("Browse") . '", ';
	echo '"Structure":"' . __("Structure") . '", ';
	echo '"Insert":"' . __("Insert") . '", ';
	
	echo '"Your changes were saved to the database.":"' . __("Your changes were saved to the database.") . '", ';
	
	echo '"delete this row":"' . __("delete this row") . '", ';
	echo '"delete these rows":"' . __("delete these rows") . '", ';
	echo '"empty this table":"' . __("empty this table") . '", ';
	echo '"empty these tables":"' . __("empty these tables") . '", ';
	echo '"drop this table":"' . __("drop this table") . '", ';
	echo '"drop these tables":"' . __("drop these tables") . '", ';
	echo '"delete this column":"' . __("delete this column") . '", ';
	echo '"delete these columns":"' . __("delete these columns") . '", ';
	echo '"delete this index":"' . __("delete this index") . '", ';
	echo '"delete these indexes":"' . __("delete this indexes") . '", ';
	echo '"delete this user":"' . __("delete this user") . '", ';
	echo '"delete these users":"' . __("delete this users") . '", ';
	echo '"Are you sure you want to":"' . __("Are you sure you want to") . '", ';
	
	echo '"The following query will be run:":"' . __("The following query will be run:") . '", ';
	echo '"The following queries will be run:":"' . __("The following queries will be run:") . '", ';
	
	echo '"Confirm":"' . __("Confirm") . '", ';
	echo '"Are you sure you want to empty the \'%s\' table? This will delete all the data inside of it. The following query will be run:":"' . __("Are you sure you want to empty the '%s' table? This will delete all the data inside of it. The following query will be run:") . '", ';
	echo '"Are you sure you want to drop the \'%s\' table? This will delete the table and all data inside of it. The following query will be run:":"' . __("Are you sure you want to drop the '%s' table? This will delete the table and all data inside of it. The following query will be run:") . '", ';
	echo '"Are you sure you want to drop the database \'%s\'? This will delete the database, the tables inside the database, and all data inside of the tables. The following query will be run:":"' . __("Are you sure you want to drop the database '%s'? This will delete the database, the tables inside the database, and all data inside of the tables. The following query will be run:") . '", ';
	
	echo '"Successfully saved changes.":"' . __("Successfully saved changes.") . '", ';
	
	echo '"New field":"' . __("New field") . '", ';
	
	echo '"Full Text":"' . __("Full Text") . '", ';
	
	echo '"Loading...":"' . __("Loading...") . '", ';
	echo '"Redirecting...":"' . __("Redirecting...") . '", ';
	
	echo '"Okay":"' . __("Okay") . '", ';
	echo '"Cancel":"' . __("Cancel") . '", ';
	
	echo '"Error":"' . __("Error") . '", ';
	echo '"There was an error receiving data from the server.":"' . __("There was an error receiving data from the server.") . '"';
	
	echo '};';
	
	echo "\n";
	
	$listsql = mysql_query("SHOW DATABASES");
	
	$output = 'var menujson = {"menu": [';
	
	if (@mysql_num_rows($listsql))
	{
		while ($row = mysql_fetch_row($listsql))
		{
			$output .= '{"name": "' . $row[0] . '"';
			
			mysql_select_db($row[0]);
			$tableSql = mysql_query("SHOW TABLES");
			
			if (@mysql_num_rows($tableSql))
			{
				$output .= ',"items": [';
				while ($tableRow = mysql_fetch_row($tableSql))
				{
					$countSql = mysql_query("SELECT COUNT(*) AS `RowCount` FROM `" . $tableRow[0] . "`");
					$rowCount = (int)(@mysql_result($countSql, 0, "RowCount"));
					$output .= '{"name":"' . $tableRow[0] . '","rowcount":' . $rowCount . '},';
				}
				$output = substr($output, 0, -1);
				$output .= ']';
			}
			$output .= '},';
		}
		$output = substr($output, 0, -1);
	}
	
	$output .= ']};';
	echo $output;
	
	?>
	</script>
</html>
<?php
}

function getDatabaseConnection()
{
	if (isset($_SESSION['SB_LOGIN_HOST']) && isset($_SESSION['SB_LOGIN_USER']) && isset($_SESSION['SB_LOGIN_PASS']))
	{
		$dbconn = mysql_connect($_SESSION['SB_LOGIN_HOST'], $_SESSION['SB_LOGIN_USER'], $_SESSION['SB_LOGIN_PASS']);
		//@mysql_query("SET NAMES 'utf8'");
		//@mysql_query("SET CHARACTER SET utf8");
		return $dbconn;
	}
}

function requireDatabaseAndTableBeDefined()
{
	global $db, $table;
	
	if (!isset($db))
	{
		?>
		
		<div class="errorpage">
		<h4><?php echo __("Oops"); ?></h4>
		<p><?php echo __("For some reason, the database parameter was not included with your request."); ?></p>
		</div>
		
		<?php
		exit;
	}
	
	if (!isset($table))
	{
		?>
		
		<div class="errorpage">
		<h4><?php echo __("Oops"); ?></h4>
		<p><?php echo __("For some reason, the table parameter was not included with your request."); ?></p>
		</div>
		
		<?php
		exit;
	}
	
}

function formatForOutput($text)
{
	$text = nl2br(htmlentities($text, ENT_QUOTES, 'UTF-8'));
	if (utf8_strlen($text) > PREVIEW_CHAR_SIZE)
	{
		$text = utf8_substr($text, 0, PREVIEW_CHAR_SIZE) . " <span class=\"toBeContinued\">[...]</span>";
	}
	return $text;
}

function formatDataForExport($text)
{
	// replace line endings with character representations
	while ($text != str_replace("\r\n", "\\r\\n", $text))
	{
		$text = str_replace("\r\n", "\\r\\n", $text);
	}
	
	while ($text != str_replace("\r", "\\r", $text))
	{
		$text = str_replace("\r", "\\r", $text);
	}
	
	while ($text != str_replace("\n", "\\n", $text))
	{
		$text = str_replace("\n", "\\n", $text);
	}
	
	// escape single quotes
	$text = str_replace("'", "\'", $text);
	return $text;
}

function formatDataForCSV($text)
{
	$text = str_replace('"', '""', $text);
	return $text;
}

function splitQueryText($query)
{
	// the regex needs a trailing semicolon
	$query = trim($query);
	
	if (substr($query, -1) != ";")
		$query .= ";";
	
	// i spent 3 days figuring out this line
	preg_match_all("/(?>[^;']|(''|(?>'([^']|\\')*[^\\\]')))+;/ixU", $query, $matches, PREG_SET_ORDER);
	
	$querySplit = "";
	
	foreach ($matches as $match)
	{
		// get rid of the trailing semicolon
		$querySplit[] = substr($match[0], 0, -1);
	}
	
	return $querySplit;
}

function memoryFormat($bytes)
{
	if ($bytes < 1024)
		$dataString = $bytes . " B";
	else if ($bytes < (1024 * 1024))
		$dataString = round($bytes / 1024) . " KB";
	else if ($bytes < (1024 * 1024 * 1024))
		$dataString = round($bytes / (1024 * 1024)) . " MB";
	else
		$dataString = round($bytes / (1024 * 1024 * 1024)) . " GB";
	
	return $dataString;
}

function outputThemeFile($filename)
{
	global $theme;
	return "themes/" . $theme . "/" . smartCaching($filename);
}

function smartCaching($filename)
{
	return $filename . "?ver=" . str_replace(".", "_", VERSION_NUMBER);
}

function __($t)
{
	global $gt;
	return $gt->getTranslation($t);
}

function __p($singular, $plural, $count)
{
	global $gt;
	if ($count == 1)
	{
		return $gt->getTranslation($singular);
	}
	else
	{
		return $gt->getTranslation($plural);
	}
}

function utf8_substr($str, $from, $len)
{
# utf8 substr
# www.yeap.lv
  return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
                       '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
                       '$1',$str);
}

function utf8_strlen($str)
{
    $i = 0;
    $count = 0;
    $len = strlen ($str);
    while ($i < $len)
    {
    $chr = ord ($str[$i]);
    $count++;
    $i++;
    if ($i >= $len)
        break;

    if ($chr & 0x80)
    {
        $chr <<= 1;
        while ($chr & 0x80)
        {
        $i++;
        $chr <<= 1;
        }
    }
    }
    return $count;
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

?>