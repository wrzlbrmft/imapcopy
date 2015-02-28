<?php
class Imap {
	protected $conf = array();
	protected $connection = NULL;

	public function __construct(array $conf) {
		$this->setConf($conf);
	}

	public function __destruct() {
		$this->disconnect();
	}

	public function getConf() { return $this->conf; }
	public function setConf(array $conf) { $this->conf = $conf; }

	public function getConnection() { return $this->connection; }
	public function setConnection($connection) { $this->connection = $connection; }

	public function getMailboxOptions() {
		$conf = $this->getConf();
		$options = NULL;

		if (isset($conf['ssl']) && $conf['ssl']) {
			$options .= '/ssl';
		}

		if (isset($conf['sslNovalidateCert']) && $conf['sslNovalidateCert']) {
			$options .= '/novalidate-cert';
		}

		if (isset($conf['readOnly']) && $conf['readOnly']) {
			$options .= '/readonly';
		}

		return $options;
	}

	public function getMailbox() {
		$conf = $this->getConf();
		$hostname = isset($conf['hostname']) ? $conf['hostname'] : NULL;
		$port = isset($conf['port']) ? $conf['port'] : '143';

		return sprintf('{%s:%d/imap%s}',
			$hostname,
			$port,
			$this->getMailboxOptions()
		);
	}

	public function isConnected() {
		return !empty($this->getConnection());
	}

	public function connect() {
		if (!$this->isConnected()) {
			$conf = $this->getConf();
			$username = isset($conf['username']) ? $conf['username'] : NULL;
			$password = isset($conf['password']) ? $conf['password'] : NULL;

			$connection = imap_open(imap_utf7_encode($this->getMailbox()), $username, $password);
			if (!empty($connection)) {
				$this->setConnection($connection);
				return true;
			}
		}
		return false;
	}

	public function disconnect() {
		if ($this->isConnected()) {
			if (imap_close($this->getConnection())) {
				$this->setConnection(NULL);
				return true;
			}
		}
		return false;
	}
}
