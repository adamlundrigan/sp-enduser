<?php

class Node
{
	private $id;
	private $address;
	private $username;
	private $password;
	private $serial;
	private $tls;
	private $timeout;
	
	public function __construct($id, $address, $username = null, $password = null, $serial = null, $tls = array(), $timeout = null)
	{
		$this->id = $id;
		$this->address = $address;
		$this->username = $username;
		$this->password = $password;
		$this->serial = $serial;
		$this->tls = $tls;
		$this->timeout = is_numeric($timeout) ? (int)$timeout : 5;
	}
	
	public function soap($async = false, $username = null, $password = null, $serial = null)
	{
		$session = Session::Get();
		
		if($username === null) $username = $session->getSOAPUsername() ?: $this->getUsername();
		if($password === null) $password = $session->getSOAPPassword() ?: $this->getPassword();

		$context = stream_context_create(array('ssl' => $this->tls));

		$options = array(
			'stream_context' => $context,
			'location' => $this->getAddress().'/remote/',
			'uri' => 'urn:halon',
			'login' => $username,
			'password' => $password,
			'connection_timeout' => $this->timeout,
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
			'compression' => SOAP_COMPRESSION_ACCEPT | (SOAP_COMPRESSION_GZIP | 0)
			);
		
		if ($async)
			return new SoapClientAsync($options['location'].'?wsdl', $options);
		return new SoapClient($options['location'].'?wsdl', $options);
	}

	public function getId()
	{
		return $this->id;
	}
	
	public function getAddress()
	{
		return $this->address;
	}
	
	public function getUsername()
	{
		return $this->username;
	}
	
	public function getPassword()
	{
		return $this->password;
	}
	
	public function getSerial($autoload = false)
	{
		if(!$this->serial && $autoload)
			$this->serial = $this->soap()->getSerial()->result;
		return $this->serial;
	}
}
