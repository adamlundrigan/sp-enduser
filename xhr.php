<?php

if (!defined('SP_ENDUSER')) die('File not included');
header('Content-Type: application/json; charset=UTF-8');

function checkAccess($perm)
{
	if (Session::Get()->checkAccessAll())
		return true;
	$access = Session::Get()->getAccess();
	foreach ($access as $type)
		foreach ($type as $item)
			if ($item == $perm)
				return true;
	if (strpos($perm, '@') !== false)
		if (Session::Get()->checkAccessMail($perm))
			return true;
	return false;
}

if ($_POST['page'] == 'bwlist')
{
	if (!$settings->getDisplayBWlist())
		die(json_encode(array('error' => "The setting display-bwlist isn't enabled")));
	$dbh = $settings->getDatabase();

	if ($_POST['list'] == 'delete')
	{
		foreach (explode(',', $_POST['access']) as $a)
		{
			if (!checkAccess($a))
				die(json_encode(array('error' => 'permission', 'value' => $a)));

			$statement = $dbh->prepare("DELETE FROM bwlist WHERE access = :access AND bwlist.type = :type AND bwlist.value = :value;");
			$statement->execute(array(':access' => $a, ':type' => $_POST['type'], ':value' => $_POST['value']));
		}
		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'add' || $_POST['list'] == 'edit')
	{
		$value = strtolower(trim($_POST['value']));
		if ($value[0] == '@') $value = substr($value, 1);
		if (substr($value, 0, 2) == '*@') $value = substr($value, 2);

		$type = $_POST['type'];

		if ($_POST['list'] == 'edit') {
			$old_value = strtolower(trim($_POST['old_value']));
			if ($old_value[0] == '@') $old_value = substr($old_value, 1);
			if (substr($old_value, 0, 2) == '*@') $old_value = substr($old_value, 2);

			$old_type = $_POST['old_type'];
		}

		$added = false;
		foreach ($_POST['access'] as $access)
		{
			if (strpos($value, ' ') !== false)
				die(json_encode(array('error' => 'syntax', 'field' => 'value', 'reason' => 'Field contained whitespace')));
			if (strpos($access, ' ') !== false)
				die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'Field contained whitespace')));

			if ($access[0] == '@')
				$access = substr($access, 1);
			if (substr($access, 0, 2) == '*@')
				$access = substr($access, 2);

			if (!checkAccess($access))
				die(json_encode(array('error' => 'permission', 'value' => $access)));

			if ($type == 'whitelist' || $type == 'blacklist') {
				if ($_POST['list'] == 'add') {
					$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
					$statement->execute(array(':access' => strtolower($access), ':type' => $type, ':value' => $value));
				}
				if ($_POST['list'] == 'edit') {
					$statement = $dbh->prepare("UPDATE bwlist SET type = :type, value = :value WHERE access = :access AND value = :old_value and type = :old_type;");
					$statement->execute(array(':access' => strtolower($access), ':type' => $type, ':value' => $value, ':old_value' => $old_value, ':old_type' => $old_type));
				}
				$added = true;
			} else
				die(json_encode(array('error' => 'syntax', 'field' => 'type', 'reason' => 'Type not black or whitelist')));
		}
		if (!$added)
			die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'No recipients')));
		die(json_encode(array('status' => 'ok')));
	}
}

if ($_POST['page'] == 'spam')
{
	if (!$settings->getDisplaySpamSettings())
		die(json_encode(array('error' => "The setting display-spamsettings isn't enabled")));
	$dbh = $settings->getDatabase();

	if ($_POST['list'] == 'delete')
	{
		foreach (explode(',', $_POST['access']) as $a)
		{
			if (!checkAccess($a))
				die(json_encode(array('error' => 'permission', 'value' => $a)));

			$statement = $dbh->prepare("DELETE FROM spamsettings WHERE access = :access;");
			$statement->execute(array(':access' => $a));
		}
		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'add' || $_POST['list'] == 'edit')
	{
		$spamsettings = array();
		$spamsettings['level'] = $_POST['level'];

		$added = false;
		foreach ($_POST['access'] as $access)
		{
			if ($spamsettings['level'] == '')
				die(json_encode(array('error' => 'syntax', 'field' => 'level', 'reason' => 'No level selected')));
			if (strpos($access, ' ') !== false)
				die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'Field contained whitespace')));

			if ($access[0] == '@')
				$access = substr($access, 1);

			if (!checkAccess($access))
				die(json_encode(array('error' => 'permission', 'value' => $access)));

			if ($_POST['list'] == 'add') {
				$statement = $dbh->prepare("INSERT INTO spamsettings (access, settings) VALUES(:access, :settings);");
				$statement->execute(array(':access' => strtolower($access), ':settings' => json_encode($spamsettings)));
			}
			if ($_POST['list'] == 'edit') {
				$statement = $dbh->prepare("UPDATE spamsettings SET settings = :settings WHERE access = :access;");
				$statement->execute(array(':access' => strtolower($access), ':settings' => json_encode($spamsettings)));
			}
			$added = true;
		}
		if (!$added)
			die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'No recipients')));
		die(json_encode(array('status' => 'ok')));
	}
}

if ($_POST['page'] == 'datastore')
{
	if (!$settings->getDisplayDataStore() || Session::Get()->checkDisabledFeature('display-datastore'))
		die(json_encode(array('error' => "The setting display-datastore isn't enabled")));

	$dbh = $settings->getDatabase();

	if ($_POST['list'] == 'add')
	{
		if (!Session::Get()->checkAccessAll())
			die(json_encode(array('error' => 'Insufficient permissions')));

		if (empty($_POST['namespace']) || empty($_POST['key']) || empty($_POST['value']))
			die(json_encode(array('error' => 'Missing values')));

		try {
			$statement = $dbh->prepare('INSERT INTO datastore (namespace, keyname, value) VALUES(:namespace, :key, :value);');
			$statement->execute(array(':namespace' => $_POST['namespace'], ':key' => $_POST['key'], ':value' => $_POST['value']));
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}
		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'edit')
	{
		$access = Session::Get()->getAccess();
		if (!Session::Get()->checkAccessAll() && count($access['domain']) == 0)
			die(json_encode(array('error' => 'Insufficient permissions')));

		if (empty($_POST['namespace']) || empty($_POST['key']) || empty($_POST['value']))
			die(json_encode(array('error' => 'Missing values')));

		if (!checkAccess($_POST['key']))
			die(json_encode(array('error' => 'No permission for '.$_POST['key'])));

		try {
			$statement = $dbh->prepare('UPDATE datastore SET value = :value WHERE namespace = :namespace AND keyname = :key;');
			$statement->execute(array(':namespace' => $_POST['namespace'], ':key' => $_POST['key'], ':value' => $_POST['value']));
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}
		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'delete')
	{
		if (!Session::Get()->checkAccessAll())
			die(json_encode(array('error' => 'Insufficient permissions')));

		if (empty($_POST['namespace']) || empty($_POST['key']))
			die(json_encode(array('error' => 'Missing values')));

		try {
			$statement = $dbh->prepare('DELETE FROM datastore WHERE namespace = :namespace AND keyname = :key;');
			$statement->execute(array(':namespace' => $_POST['namespace'], ':key' => $_POST['key']));
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}
		die(json_encode(array('status' => 'ok')));
	}
}

if ($_POST['page'] == 'rates')
{
	if (!$settings->getDisplayRateLimits())
		die(json_encode(array('error' => "The setting display-ratelimits isn't enabled")));
	if (!Session::Get()->checkAccessAll()) die(json_encode(array('error' => 'Insufficient permissions')));

	if ($_POST['list'] == 'clear')
	{
		$nodeBackend = new NodeBackend($settings->getNodes());
		$results = $nodeBackend->clearRate(array('ns' => $_POST['ns'], 'entry' => $_POST['entry']), $errors);
		if ($errors)
			die(json_encode(array('error' => 'soap', 'value' => $errors)));
		die(json_encode(array('status' => 'ok')));
	}

	if (isset($_POST['rate']))
	{
		$ratelimits = $settings->getRateLimits();
		if (!isset($ratelimits[$_POST['rate']])) die(json_encode(array('error' => 'Invalid ratelimit')));
		$rate = $ratelimits[$_POST['rate']];

		$nodeIndex = isset($rate['node']) ? (int)$rate['node'] : 0;
		$nodeBackend = new NodeBackend($settings->getNode($nodeIndex));

		// Compensate for count being '> X' in the API
		if (isset($rate['count_min']))
			$rate['count_min'] = max($rate['count_min'] - 1, 0);

		$limit = 10000;
		$params = array('ns' => $rate['ns'], 'matchexact' => array('ns' => true, 'entry' => false));
		if ($_POST['search'])
			$params['entry'] = $_POST['search'];
		$params['count'] = intval($rate['count_min']);
		$params['paging'] = array('limit' => $limit);

		$result = array();
		while (true)
		{
			$errors = array();
			$r = $nodeBackend->getRate($params, $errors);
			$r = $r[0];
			if ($errors)
				die(json_encode(array('error' => $errors)));
			if (!$r->result->item)
				break;

			$result = array_merge($result, $r->result->item);
			if (count($r->result->item) < $limit)
				break;

			$last = end($r->result->item);
			$params['paging']['ns'] = $last->ns;
			$params['paging']['entry'] = $last->entry;
		}

		$items = array();
		foreach ($result as $item) {
			$items[] = array(
					'entry' => $item->entry,
					'ns' => $item->ns,
					'count' => $item->count,
					'search_filter' => urlencode(str_replace('$entry', $item->entry, $rate['search_filter'])),
					);
		}

		function cmp($a, $b) {
			$x = $b['count'] - $a['count'];
			if ($x != 0)
				return $x;
			return strnatcmp($a['entry'], $b['entry']);
		}
		usort($items, 'cmp');

		$actions = array(
				'QUARANTINE' => array('color' => '#f70', 'icon' => 'inbox'),
				'REJECT' => array('color' => '#ba0f4b', 'icon' => 'ban'),
				'DELETE' => array('color' => '#333', 'icon' => 'trash-o'),
				'DEFER' => array('color' => '#b5b', 'icon' => 'clock-o'),
				);

		$action = strtoupper($rate['action']);
		if (!array_key_exists($action, $actions)) $action = '';

		$data = array();
		$data['action_type'] = $action;
		$data['action_icon'] = isset($actions[$action]['icon']) ? $actions[$action]['icon'] : 'exclamation';
		$data['action_color'] = isset($actions[$action]['color']) ? $actions[$action]['color'] : '#9d9d9d';
		$data['count_limit'] = intval($rate['count_limit']);
		$data['page_limit'] = 10;
		$data['page_start'] = intval($_POST['paging']);
		$data['items'] = array_slice($items, $data['page_start'], $data['page_limit']);
		$data['items_count'] = count($items);

		die(json_encode($data));
	}
}

if ($_POST['page'] == 'stats')
{
	if (!$settings->getDisplayStats())
		die(json_encode(array('error' => "The setting display-stats isn't enabled")));

	$dbh = $settings->getDatabase();
	function stats_check_access($domain)
	{
		global $dbh;
		$access = Session::Get()->getAccess();
		$q = $dbh->prepare('SELECT domain FROM stat WHERE domain = :domain AND userid = :userid LIMIT 1;');
		$q->execute(array(':userid' => $access['userid'], ':domain' => $domain));
		$rows = $q->fetch(PDO::FETCH_ASSOC);
		if (is_array($rows) && count($rows) == 1)
			return true;
		if (Session::Get()->checkAccessDomain($domain))
			return true;
		return false;
	}

	if ($_POST['type'] == 'rrd')
	{
		if ($settings->getUseDatabaseStats())
		{
			if (!stats_check_access($_POST['domain']))
				die(json_encode(array('error' => 'Insufficient permissions')));

			$data = array();
			$data[] = base64_encode(file_get_contents($settings->getGraphPath().'/'.$_POST['domain'].(($_POST['direction'] == 'outbound') ? '-outbound.rrd' : '.rrd')));
			die(json_encode($data));
		} else {
			if (!Session::Get()->checkAccessDomain($_POST['domain']))
				die(json_encode(array('error' => 'Insufficient permissions')));

			$listener = 'mailserver:1';
			$listener = str_replace(':', '-', $listener);
			$domain = $_POST['domain'];
			$data = array();
			foreach ($settings->getNodes() as $node) {
				try {
					$data[] = base64_encode($node->soap()->graphFile(array('name' => 'mail-stat-'.$listener.'-'.$domain))->result);
				} catch (SoapFault $e) {
				}
			}
			die(json_encode($data));
		}
	}
	if ($_POST['type'] == 'pie')
	{
		if ($settings->getUseDatabaseStats())
		{
			if (!stats_check_access($_POST['domain']))
				die(json_encode(array('error' => 'Insufficient permissions')));

			$access = Session::Get()->getAccess();
			// total pie
			if (!$_POST['time']) {
				$q = $dbh->prepare('SELECT SUM(reject) AS reject, SUM(deliver) AS deliver FROM stat WHERE direction = :direction AND domain = :domain GROUP BY domain;');
				$q->execute(array(':direction' => $_POST['direction'], ':domain' => $_POST['domain']));
			} else {
				$date = explode('-', $_POST['time']);
				$q = $dbh->prepare('SELECT reject, deliver FROM stat WHERE direction = :direction AND domain = :domain AND year = :year AND month = :month;');
				$q->execute(array(':direction' => $_POST['direction'], ':domain' => $_POST['domain'], ':year' => $date[0], ':month' => $date[1]));
			}
			$row = $q->fetch(PDO::FETCH_ASSOC);
			$flot = array();
			$flot[] = array('label' => 'deliver', 'data' => $row['deliver'], 'color' => '#7d6');
			$flot[] = array('label' => 'reject', 'data' => $row['reject'], 'color' => '#d44');
			die(json_encode(array('flot' => $flot)));
		} else {
			if (!Session::Get()->checkAccessDomain($_POST['domain']))
				die(json_encode(array('error' => 'Insufficient permissions')));
			$listener = 'mailserver:1';
			$keyname = 'mail:action:';
			$stats = array();
			$since = null;
			foreach ($settings->getNodes() as $node) {
				try {
					$ss = $node->soap()->statList(array('key1' => $keyname.'%', 'key2' => $inbound, 'key3' => $_POST['domain'], 'offset' => 0, 'limit' => 10))->result->item;
					if (!is_array($ss))
						continue;
					foreach ($ss as $s) {
						$k = str_replace($keyname, '', $s->key1);
						if (!$stats[$k]) $stats[$k] = 0;
						$stats[$k] += $s->count;
						if ($since === null || $s->created < $since) $since = $s->created;
					}
				} catch (SoapFault $e) {
				}
			}
			$flot = array();
			foreach ($stats as $k => $v) {
				$p = array('label' => $k, 'data' => $v);
				$color = null;
				if ($k == 'delete') $color = '#666';
				if ($k == 'deliver') $color = '#7d6';
				if ($k == 'allow') $color = '#9cf';
				if ($k == 'reject') $color = '#d44';
				if ($k == 'block') $color = '#622';
				if ($k == 'defer') $color = '#ed4';
				if ($k == 'quarantine') $color = '#e96';
				if ($color) $p['color'] = $color;
				$flot[] = $p;
			}
			die(json_encode(array('since' => $since, 'flot' => $flot)));
		}
	}
	if ($_POST['type'] == 'since')
	{
		if ($settings->getUseDatabaseStats())
		{
			if (!stats_check_access($_POST['domain']))
				die(json_encode(array('error' => 'Insufficient permissions')));

			$access = Session::Get()->getAccess();
			$q = $dbh->prepare('SELECT year, month FROM stat WHERE direction = :direction AND domain = :domain;');
			$q->execute(array(':direction' => $_POST['direction'], ':domain' => $_POST['domain']));
			die(json_encode($q->fetchAll(PDO::FETCH_ASSOC)));
		}
	}
}

if ($_POST['page'] == 'users')
{
	if (!$settings->getDisplayUsers() || Session::Get()->checkDisabledFeature('display-users'))
		die(json_encode(array('error' => "The setting display-users isn't enabled")));
	if (!Session::Get()->checkAccessAll())
		die(json_encode(array('error' => 'Insufficient permissions')));

	$dbh = $settings->getDatabase();

	if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite') {
		try {
			$dbh->query('PRAGMA foreign_keys = ON;');
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}
	}

	if ($_POST['list'] == 'add-user')
	{
		if (empty($_POST['username']) || empty($_POST['password_1']) || empty($_POST['password_2']))
			die(json_encode(array('error' => 'Missing values')));

		if ($_POST['password_1']  != $_POST['password_2'])
			die(json_encode(array('error' => "The passwords doesn't match")));

		if (is_array($_POST['access']))
			foreach ($_POST['access'] as $access)
				if ($access == '')
					die(json_encode(array('error' => 'One or more of the permissions are empty')));

		$password = password_hash($_POST['password_1'], PASSWORD_DEFAULT);

		try {
			$statement = $dbh->prepare('INSERT INTO users (username, password) VALUES (:username, :password);');
			$statement->execute(array(':username' => $_POST['username'], ':password' => $password));
			if (is_array($_POST['access'])) {
				foreach ($_POST['access'] as $access) {
					$type = (strpos($access, '@')) ? 'mail' : 'domain';
					$statement = $dbh->prepare('INSERT INTO users_relations (username, type, access) VALUES (:username, :type, :access)');
					$statement->execute(array(':username' => $_POST['username'], ':type' => $type, ':access' => $access));
				}
			}
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}

		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'edit-user')
	{
		if (empty($_POST['username']) || empty($_POST['old_username']))
			die(json_encode(array('error' => 'Missing values')));

		if (is_array($_POST['access']))
			foreach ($_POST['access'] as $access)
				if ($access == '') die(json_encode(array('error' => 'One or more of the permissions are empty')));

		if (!empty($_POST['password_1']) and !empty($_POST['password_2'])) {
			if ($_POST['password_1']  != $_POST['password_2'])
				die(json_encode(array('error' => "The passwords doesn't match")));
			$password = password_hash($_POST['password_1'], PASSWORD_DEFAULT);
		}

		try {
			if ($_POST['username'] != $_POST['old_username']) {
				$statement = $dbh->prepare('UPDATE users SET username = :username WHERE username = :old_username;');
				$statement->execute(array(':username' => $_POST['username'], ':old_username' => $_POST['old_username']));
			}
			if (!empty($password)) {
				$statement = $dbh->prepare('UPDATE users SET password = :password WHERE username = :username;');
				$statement->execute(array(':username' => $_POST['username'], ':password' => $password));
			}
			if (is_array($_POST['access'])) {
				foreach ($_POST['access'] as $access) {
					$type = (strpos($access, '@')) ? 'mail' : 'domain';
					$statement = $dbh->prepare('INSERT INTO users_relations (username,type,access) VALUES (:username, :type, :access);');
					$statement->execute(array(':username' => $_POST['username'], ':type' => $type, ':access' => $access));
				}
			}
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}

		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'edit-access')
	{
		if (empty($_POST['username']) || empty($_POST['access']) || empty($_POST['old_access']))
			die(json_encode(array('error' => 'Missing values')));

		try {
			$statement = $dbh->prepare('UPDATE users_relations SET access = :access WHERE username = :username AND access = :old_access;');
			$statement->execute(array(':username' => $_POST['username'], ':access' => $_POST['access'], ':old_access' => $_POST['old_access']));
		} catch (PDOException $e) {
			die(json_encode(array('error' => 'Database error')));
		}

		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'delete')
	{
		if ((empty($_POST['username']) || empty($_POST['type'])) || ($_POST['type'] == 'access' and empty($_POST['access'])))
			die(json_encode(array('error' => 'Missing values')));

		if ($_POST['type'] == 'user') {
			try {
				$statement = $dbh->prepare('DELETE FROM users WHERE username = :username;');
				$statement->execute(array(':username' => $_POST['username']));
			} catch (PDOException $e) {
				die(json_encode(array('error' => 'Database error')));
			}

			die(json_encode(array('status' => 'ok')));
		}

		if ($_POST['type'] == 'access') {
			try {
				$statement = $dbh->prepare('DELETE FROM users_relations WHERE username = :username AND access = :access;');
				$statement->execute(array(':username' => $_POST['username'], ':access' => $_POST['access']));
			} catch (PDOException $e) {
				die(json_encode(array('error' => 'Database error')));
			}

			die(json_encode(array('status' => 'ok')));
		}
	}
}

die(json_encode(array('error' => 'unsupported request')));
