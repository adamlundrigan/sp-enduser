<?php

/*
 * This is the configuration file, in PHP format. In most cases, it's
 * ok just to edit our settings, and remove the // comments.
 */

/*
 * System "nodes" are the most important directives; they specify where
 * your Halon mail gateway(s) are, and how to access it/them.
 * 
 * If you are planning on using authentication methods other than the default
 * 'server' mode (which authenticates users directly against the server), you
 * need to specify a username and password that will be used to access the
 * server for non-server users. Otherwise, this can and should be omitted.
 * 
 * It might be a good idea to read about authentication scripts on our wiki, to
 * create specific access rights so that if this end-user web is compromised,
 * your gateways are not. In some cases, even read-only access is good enough
 * for this application.
 *
 * The default SOAP connection timeout is 5 seconds.  To change the global
 * default, set `node-default-timeout` key to the required value
 *
 *     $settings['node-default-timeout'] = 10;
 *
 * It is also possible to override the connection timeout on a per-node basis
 * by specifying it in the `timeout` key of the node definition:
 *
 *    $settings['node'][] = array(
 *        'address' => 'https://10.2.0.30/',
 *        'timeout' => 15,
 *    );
 *
 */

//$settings['node'][] = array(
//		'address' => 'https://10.2.0.30/',
//		'tls' => array('verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false),
//		);
//$settings['node'][] = array(
//		'address' => 'https://10.2.0.31/',
//		'username' => 'admin',
//		'password' => 'admin',
//		'tls' => array('verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false),
//		);

/*
 * The API key is used by the Halon mail gateways to communicate with
 * this application, such as creating database users when messages are
 * quarantined, or doing black/whitelist lookups.
 */

//$settings['api-key'] = 'secret';

/*
 * The "mail from" and "public-url" settings are used by this application
 * as self identification, for example in mail such as forgot password
 * reminders or digest lists. The default source determine what's shown
 * on the first page, or when pressing "messages" in the menu.
 */

//$settings['mail']['from'] = 'Mail quarantine <postmaster@example.org>';
//$settings['public-url'] = 'http://10.2.0.166/enduser/';
//$settings['default-source'] = 'all';
//$settings['display-scores'] = false;
//$settings['display-textlog'] = false;
//$settings['display-stats'] = false;
//$settings['display-history'] = true;
//$settings['display-queue'] = true;
//$settings['display-quarantine'] = true;
//$settings['display-archive'] = false;
//$settings['display-all'] = true;
//$settings['display-bwlist'] = true;
//$settings['display-spamsettings'] = false;
//$settings['display-ratelimits'] = false;
//$settings['display-datastore'] = false;
//$settings['display-users'] = false;
//$settings['display-listener']['mailserver:inbound'] = 'Inbound';
//$settings['display-transport']['mailtransport:outbound'] = 'Internet';

/*
 * It's possible to use this application completely without a database.
 * However, features such as local users (if SMTP or LDAP authentication
 * is not suitable) and black/whitelisting requires a database. You can use
 * most databases, such as SQLite, MySQL and PostgreSQL. Below are a few
 * examples. You should use PHP PDO format.
 */

//$settings['database']['dsn'] = 'sqlite:/tmp/foo.db';
//$settings['database']['dsn'] = 'pgsql:host=localhost;port=5432;dbname=spenduser;user=halon;password=halon';
//$settings['database']['dsn'] = 'mysql:host=localhost;port=5432;dbname=spenduser';
//$settings['database']['user'] = 'root';
//$settings['database']['password'] = '1';
//$settings['database']['partitions'] = 1;
//$settings['database']['partitiontype'] = 'string'; // or 'integer'

/*
 * Logs are normally read from the nodes directly, but for performance, you can
 * instead opt to configure your nodes to log to a central database server, as
 * described at: http://wiki.halon.se/End-user#History_log
 */
//$settings['database-log'] = false;

/*
 * Stats are normally read from the nodes directly, but for performance, you can
 * instead opt to configure your nodes to stat to a central database server, as
 * described at: http://wiki.halon.se/End-user#History_log
 * All database stats are stored either as inbound or outbound based on the
 * 'display-listener' and 'display-transport' settings. If it matches
 * a listener that is labeled 'Outbound' or a transport that is labeled 'Internet'
 * it will be stored as outbound, otherwise it will be stored as inbound.
 */
//$settings['database-stats'] = false;

/*
 * Authentication is probably the second most important configuration
 * directive, as it specifies how end-users should identify themselves.
 * 
 * You can use the following types:
 *  - LDAP, against for example an Exchange server
 *  - SMTP (SASL), against a mail server, if the username is an e-mail
 *  - Database, populated by the Halon mail gateways when mail are quarantined
 *  - Local accounts, statically configured in this file (with access rights).
 *    Use lower case letters when manually adding an access level.
 *  - Server account, authorized against an account on the nodes themselves.
 * 
 * If no authorization methods are specified, 'server' is assumed.
 */

//$settings['authentication'][] = array(
//		'type' => 'database',
//		);
//$settings['authentication'][] = array(
// 		'type' => 'account',
// 		'username' => 'foo',
// 		'password' => 'foo',
//		'access' => array( // optional access restrictions
//				'domain' => array('example.com'),
//				'mail' => array('foo@example.com'),
//				),
// 		);
//$settings['authentication'][] = array(
//		'type' => 'ldap',
//		'uri' => 'ldap://10.2.7.2',
//		'base_dn' => 'CN=Users,DC=dev,DC=halon,DC=local',
//		'schema' => 'msexchange',
//		'options' => array(LDAP_OPT_PROTOCOL_VERSION => 3),
//		);
//$settings['authentication'][] = array(
//		'type' => 'smtp',
//		'host' => '10.2.0.30',
//		'port' => 25,
//		'tls' => array('verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false),
//		);
//$settings['authentication'][] = array(
//		'type' => 'server',
//		);

/*
 * The quarantine filter is used to restrict the end-user access to
 * only certain quarantines, in case you have multiple quarantines with
 * different purposes.
 */

//$settings['quarantine-filter'][] = 'mailquarantine:1';
//$settings['quarantine-filter'][] = 'mailquarantine:2';

/*
 * The archive filter is used to restrict the end-user access to
 * only certain archives (quarantines), in case you have multiple quarantines with
 * different purposes.
 */

//$settings['archive-filter'][] = 'mailquarantine:3';
//$settings['archive-filter'][] = 'mailquarantine:4';

/*
 * The default filter-pattern to use when creating additional
 * inbound/outbound restrictions are "{from} or {to}", however
 * in some cases it's necessary to know if the message is
 * inbound or outbound.
 */

//$settings['filter-pattern'] = '{from} server=mailserver:2 or {to} server=mailserver:1';

/*
 * The rate limits to display on the "rate limit" page.
 * All parameters in the array for a namespace are optional and can be omittied if not applicable.
 */

//$settings['ratelimits'][] = array(
//		'name' => 'Outbound spammers',		// Name to show in UI
//		'ns' => 'outbound-spammers',		// Namespace
//		'count_min' => 10,			// Minimum count required for entry to show
//		'count_limit' => 100,			// At which number the limit is exceeded 
//		'action' => 'DEFER',			// Action taken if limit is exceeded
//		'search_filter' => 'from=$entry',	// Search filter for the "messages" page
//		);

/*
 * It's possible to send "digest" messages with a list of what's in
 * the quarantine. It is added as a cron job, to be run every 24 hours:
 * # php cron.php.txt digestday
 * and it will use the authentication sources to find users. To use LDAP,
 * add a 'bind_dn' and 'bind_password' to your LDAP source. To use static
 * users (type account), add a 'email' to them. To send digest messages to
 * EVERY RECIPIENT (user or not) that has quarantine messages, enable the
 * to-all option below. To have a "direct release link" in the messages,
 * enable the digest secret below.
 */

//$settings['digest']['to-all'] = true;
//$settings['digest']['secret'] = 'badsecret';

/*
 * If hosting multiple websites on the same server, it's important to use
 * different session names for each site.
 */

//$settings['session-name'] = 'spenduser';

/*
 * Enables two-factor authentication with Google Authenticator.
 * This option requires that you use a database but works for all types of auth methods
 */

//$settings['twofactorauth'] = false;

/*
 * Customizable text in the interface.
 */

//$settings['theme'] = 'paper'; // see themes/
//$settings['brand-logo'] = '/logo.png';
//$settings['brand-logo-height'] = 40; // the real height of the image, should be double (hidpi) the size of themes brand container
//$settings['pagename'] = "Halon log server";
//$settings['logintext'] = "Some text you'd like to display on the login form";
//$settings['forgottext'] = "Some text you'd like to display on the forgot form";

/*
 * Maxmind GEOIP2
 * Dependencies:
 * composer require geoip2/geoip2:~2.0
 * composer require components/flag-icon-css
 * Visit https://www.maxmind.com/ for more information
 */

//$settings['geoip'] = false;
//$settings['geoip-database'] = ''; // download the country database at https://dev.maxmind.com/geoip/geoip2/geolite2/ and specify the path to it
