<?php

/**
 * Singleton wrapper for SP Enduser's settings (settings.php).
 */
class Settings
{
	private $settings = array();
	private $database = null;
	
	private $nodeCredentials = array();
	private $nodes = array();
	private $apiKey = null;
	private $dbCredentials = array('dns' => null);
	private $authSources = array(array('type' => 'server'));
	
	private $mailSender = null;
	private $publicURL = null;
	
	private $pageName = "Halon log server";
	private $theme = null;
	private $brandLogo = null;
	private $brandLogoHeight = null;
	private $loginText = null;
	private $forgotText = null;
	private $defaultSource = 'all';
	private $rateLimits = array();
	private $displayScores = false;
	private $displayTextlog = false;
	private $displayHistory = true;
	private $displayQueue = true;
	private $displayQuarantine = true;
	private $displayArchive = false;
	private $displayAll = true;
	private $displayBWList = true;
	private $displaySpamSettings = false;
	private $displayStats = false;
	private $displayRateLimits = false;
	private $displayDataStore = false;
	private $displayUsers = false;
	private $displayListener = array('mailserver:inbound' => "Inbound");
	private $displayTransport = array('mailtransport:outbound' => "Internet");
	private $useDatabaseLog = false;
	private $useDatabaseStats = false;
	private $quarantineFilter = array();
	private $archiveFilter = array();
	private $filterPattern = "{from} or {to}";
	private $twoFactorAuth = false;
	private $geoIP = false;
	private $geoIPDatabase = null;

	private $databaseLogCleanType = 'count';
	private $databaseLogCleanThreshold = 5000000;
	
	private $digestToAll = false;
	private $digestSecret = null;
	private $sessionName = null;
	
	/**
	 * Returns a shared Settings instance.
	 */
	public static function Get()
	{
		static $inst = null;
		if ($inst === null)
			$inst = new Settings();
		return $inst;
	}
	
	/**
	 * Private constructor; use Settings::Get().
	 */
	private function __construct()
	{
		$settings = array();
		require BASE.'/settings.php';
		
		$this->settings = $settings;
		
		$this->extract($this->nodeCredentials, 'node');
		$this->extract($this->apiKey, 'api-key');
		$this->extract($this->mailSender, 'mail.from');
		$this->extract($this->publicURL, 'public-url');
		$this->extract($this->theme, 'theme');
		$this->extract($this->brandLogo, 'brand-logo');
		$this->extract($this->brandLogoHeight, 'brand-logo-height');
		$this->extract($this->pageName, 'pagename');
		$this->extract($this->loginText, 'logintext');
		$this->extract($this->forgotText, 'forgottext');
		$this->extract($this->defaultSource, 'default-source');
		$this->extract($this->rateLimits, 'ratelimits');
		$this->extract($this->displayScores, 'display-scores');
		$this->extract($this->displayTextlog, 'display-textlog');
		$this->extract($this->displayHistory, 'display-history');
		$this->extract($this->displayQueue, 'display-queue');
		$this->extract($this->displayQuarantine, 'display-quarantine');
		$this->extract($this->displayArchive, 'display-archive');
		$this->extract($this->displayAll, 'display-all');
		$this->extract($this->displayBWList, 'display-bwlist');
		$this->extract($this->displaySpamSettings, 'display-spamsettings');
		$this->extract($this->displayStats, 'display-stats');
		$this->extract($this->displayRateLimits, 'display-ratelimits');
		$this->extract($this->displayDataStore, 'display-datastore');
		$this->extract($this->displayUsers, 'display-users');
		$this->extract($this->displayListener, 'display-listener');
		$this->extract($this->displayTransport, 'display-transport');
		$this->extract($this->useDatabaseLog, 'database-log');
		$this->extract($this->databaseLogCleanType, 'database-log-cleanup.type');
		$this->extract($this->databaseLogCleanThreshold, 'database-log-cleanup.threshold');
		$this->extract($this->useDatabaseStats, 'database-stats');
		$this->extract($this->dbCredentials, 'database');
		$this->extract($this->authSources, 'authentication');
		$this->extract($this->quarantineFilter, 'quarantine-filter');
		$this->extract($this->archiveFilter, 'archive-filter');
		$this->extract($this->filterPattern, 'filter-pattern');
		$this->extract($this->digestToAll, 'digest.to-all');
		$this->extract($this->digestSecret, 'digest.secret');
		$this->extract($this->sessionName, 'session-name');
		$this->extract($this->twoFactorAuth, 'twofactorauth');
		$this->extract($this->geoIP, 'geoip');
		$this->extract($this->geoIPDatabase, 'geoip-database');

		foreach ($this->nodeCredentials as $id => $cred) {
			$username = isset($cred['username']) ? $cred['username'] : null;
			$password = isset($cred['password']) ? $cred['password'] : null;
			$serial = isset($cred['serialno']) ? $cred['serialno'] : null;
			$tls = isset($cred['tls']) ? $cred['tls'] : array();
			$this->nodes[] = new Node($id, $cred['address'], $username, $password, $serial, $tls);
		}
		
		if(!$this->publicURL)
		{
			$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https" : "http";
			$url = $protocol."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$this->publicURL = preg_replace("#[^/]*$#", "", $url);
		}
	}
	
	/**
	 * Extracts a value from the $this->settings array.
	 */
	private function extract(&$out, $key)
	{
		$parts = explode('.', $key);
		$tmp = $this->settings;
		foreach ($parts as $part) {
			$tmp = isset($tmp[$part]) ? $tmp[$part] : null;
			if($tmp === null) return;
		}
		
		$out = $tmp;
	}
	
	/**
	 * Returns a database wrapper object.
	 */
	public function getDatabase()
	{
		if(!$this->database)
		{
			$credentials = $this->getDBCredentials();
			
			if(!$credentials['dsn'])
				return null;
			
			$dsn = $credentials['dsn'];
			$username = isset($credentials['user']) ? $credentials['user'] : null;
			$password = isset($credentials['password']) ? $credentials['password'] : null;
			$this->database = new Database($dsn, $username, $password);
		}
		
		return $this->database;
	}
	
	/**
	 * Returns the credentials for all configured nodes.
	 */
	public function getNodeCredentials()
	{
		return $this->nodeCredentials;
	}
	
	/**
	 * Returns a list of all configured nodes.
	 */
	public function getNodes()
	{
		return $this->nodes;
	}
	
	/**
	 * Returns a specific node from the list, or null if there's no such node.
	 */
	public function getNode($i)
	{
		if($i < count($this->nodes))
			return $this->nodes[$i];
		return null;
	}

	/**
	 * Returns a specific node from the list by serial, or null if there's no such node.
	 */
	public function getNodeBySerial($serial)
	{
		foreach ($this->nodes as $node)
		{
			try {
				if($node->getSerial(true) == $serial)
					return $node;
			} catch (SoapFault $e) {}
		}
		return null;
	}
	
	/**
	 * Returns the nodes' API key.
	 */
	public function getAPIKey()
	{
		return $this->apiKey;
	}
	
	/**
	 * Returns the configured database credentials.
	 */
	public function getDBCredentials()
	{
		return $this->dbCredentials;
	}
	
	/**
	 * Returns all configured authentication sources.
	 */
	public function getAuthSources()
	{
		return $this->authSources;
	}
	
	/**
	 * Returns the value for the "From:" field in outgoing emails, if any.
	 */
	public function getMailSender()
	{
		return $this->mailSender;
	}
	
	/**
	 * Returns the site's public URL (autodetected by default).
	 */
	public function getPublicURL()
	{
		return $this->publicURL;
	}

	/**
	 * Returns the theme.
	 */
	public function getTheme()
	{
		if (file_exists('themes/'.$this->theme.'/bootstrap.min.css'))
			return 'themes/'.$this->theme.'/bootstrap.min.css';
		if ($this->theme)
			return $this->theme;
		return 'vendor/twbs/bootstrap/dist/css/bootstrap.min.css';
	}

	/**
	 * Returns the brand logo
	 */
	public function getBrandLogo()
	{
		return $this->brandLogo;
	}

	/**
	 * Returns the brand logo height
	 */
	public function getBrandLogoHeight()
	{
		return $this->brandLogoHeight / 2;
	}
	
	/**
	 * Returns the page name.
	 */
	public function getPageName()
	{
		return $this->pageName;
	}
	
	/**
	 * Returns some text to display at the top of the login form, or null.
	 */
	public function getLoginText()
	{
		return $this->loginText;
	}
	
	/**
	 * Returns some text to do display at the top of the forgot form, or null.
	 */
	public function getForgotText()
	{
		return $this->forgotText;
	}
	
	/**
	 * Returns the default-selected Source.
	 */
	public function getDefaultSource()
	{
		if ($this->getUseDatabaseLog())
			return 'log';
		return $this->defaultSource;
	}

	/**
	 * Returns all configured rate limits.
	 */
	public function getRateLimits()
	{
		return $this->rateLimits;
	}
	
	/**
	 * Returns whether scores should be displayed.
	 */
	public function getDisplayScores()
	{
		return $this->displayScores;
	}
	
	/**
	 * Returns whether the text log should be displayed.
	 */
	public function getDisplayTextlog()
	{
		return $this->displayTextlog;
	}
	
	/**
	 * Returns whether the History source should be displayed.
	 */
	public function getDisplayHistory()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayHistory;
	}
	
	/**
	 * Returns whether the Queue source should be displayed.
	 */
	public function getDisplayQueue()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayQueue;
	}
	
	/**
	 * Returns whether the Quarantine source should be displayed.
	 */
	public function getDisplayQuarantine()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayQuarantine;
	}

	/**
	 * Returns whether the Archive source should be displayed.
	 */
	public function getDisplayArchive()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayArchive;
	}

	/**
	 * Returns whether the "All" (SOAP) source should be displayed.
	 */
	public function getDisplayAll()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayAll;
	}

	/**
	 * Returns whether the black/whitelist tab should be displayed.
	 */
	public function getDisplayBWList()
	{
		if (!isset($this->dbCredentials['dsn']))
			return false;
		return $this->displayBWList;
	}

	/**
	 * Returns whether the spam settings tab should be displayed.
	 */
	public function getDisplaySpamSettings()
	{
		if (!isset($this->dbCredentials['dsn']))
			return false;
		return $this->displaySpamSettings;
	}

	/**
	 * Returns whether the stats tab should be displayed.
	 */
	public function getDisplayStats()
	{
		return $this->displayStats;
	}

	/**
	 * Returns whether the rate limits tab should be displayed.
	 */
	public function getDisplayRateLimits()
	{
		return $this->displayRateLimits;
	}

	/**
	 * Returns whether the data store tab should be displayed.
	 */
	public function getDisplayDataStore()
	{
		if (!isset($this->dbCredentials['dsn']))
			return false;
		return $this->displayDataStore;
	}

	/**
	 * Returns whether the database users tab should be displayed.
	 */
	public function getDisplayUsers()
	{
		if (!isset($this->dbCredentials['dsn']))
			return false;
		return $this->displayUsers;
	}

	/**
	 * ???
	 */
	public function getDisplayListener()
	{
		return $this->displayListener;
	}
	
	/**
	 * ???
	 */
	public function getDisplayTransport()
	{
		return $this->displayTransport;
	}
	
	/**
	 * Returns whether or not database logging is enabled.
	 */
	public function getUseDatabaseLog()
	{
		return $this->useDatabaseLog;
	}

	/**
	 * Returns whether or not database stats is enabled.
	 */
	public function getUseDatabaseStats()
	{
		return $this->useDatabaseStats;
	}
	
	/**
	 * Returns a list of which quarantines should be visible, or an empty array
	 * if they should all be visible.
	 */
	public function getQuarantineFilter()
	{
		return $this->quarantineFilter;
	}

	/**
	 * Returns a list of which archives should be visible, or an empty array
	 * if they should all be visible.
	 */
	public function getArchiveFilter()
	{
		return $this->archiveFilter;
	}

	/**
	 * Returns the pattern for creating additional inbound/outbound
	 * restrictions.
	 */
	public function getFilterPattern()
	{
		return $this->filterPattern;
	}
	
	/**
	 * Returns whether digest emails should be sent to everyone.
	 */
	public function getDigestToAll()
	{
		return $this->digestToAll;
	}
	
	/**
	 * Returns the secret key used to generate a "direct release" link in
	 * digest emails.
	 */
	public function getDigestSecret()
	{
		return $this->digestSecret;
	}
	
	/**
	 * Returns the custom session name, if any.
	 */
	public function getSessionName()
	{
		return $this->sessionName;
	}

	/**
	 * Returns the rrd graph path.
	 */
	public function getGraphPath()
	{
		return BASE . '/../rrd';
	}

	/**
	 * Returns whether or not two-factor authentication is enabled
	 */
	public function getTwoFactorAuth()
	{
		if (!isset($this->dbCredentials['dsn']))
			return false;
		return $this->twoFactorAuth;
	}

	/**
	 * Returns whether or not geoip is enabled
	 */
	public function getGeoIP()
	{
		if (!class_exists("GeoIp2\Database\Reader"))
			return false;
		return $this->geoIP;
	}

	/**
	 * Returns path to geoip database
	 */
	public function getGeoIPDatabase()
	{
		return $this->geoIPDatabase;
	}

	/**
	 * Returns the database partition type
	 */
	public function getPartitionType()
	{
		if (isset($this->dbCredentials['partitiontype']))
			return $this->dbCredentials['partitiontype'];
		return 'integer';
	}

	/**
	 * Returns the messagelog table based on a userid
	 */
	function getMessagelogTable($userid)
	{
		if (isset($this->dbCredentials['partitions']) && $this->dbCredentials['partitions'] > 1)
		{
			if ($userid == '')
				return 'messagelog1';
			$userid = $this->getPartitionType() == 'string' ? crc32($userid) : $userid;
			return 'messagelog' .(($userid % $this->dbCredentials['partitions']) + 1);
		} else {
			return 'messagelog';
		}
	}

	/**
	 * Returns a list of all messagelog tables
	 */
	function getMessagelogTables()
	{
		$tables = array();
		if (isset($this->dbCredentials['partitions']) && $this->dbCredentials['partitions'] > 1)
			for ($i = 1; $i <= $this->dbCredentials['partitions']; $i++)
				$tables[] = 'messagelog'.$i;
		else
			$tables[] = 'messagelog';
		return $tables;
	}

	/**
	 * Returns the configured method for cleaning out message log tables
	 */
	public function getDatabaseLogCleanType()
	{
		return $this->databaseLogCleanType;
	}

	/**
	 * Returns the configured threshold to use when cleaning out message log tables
	 */
	public function getDatabaseLogCleanThreshold()
	{
		return $this->databaseLogCleanThreshold;
	}
}
