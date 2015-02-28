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

	public function getFolderSeparator() {
		$conf = $this->getConf();
		return isset($conf['folderSeparator']) ? $conf['folderSeparator'] : '.';
	}

	public function splitFolderPath($folder) {
		return explode($this->getFolderSeparator(), $folder);
	}

	public function joinFolderPath(array $folderPath) {
		return implode($this->getFolderSeparator(), $folderPath);
	}

	public function encodeFolder($folder) {
		$folderPath = $this->splitFolderPath($folder);
		foreach ($folderPath as &$i) {
			$i = imap_utf7_encode($i);
		}
		return $this->joinFolderPath($folderPath);
	}

	public function decodeFolder($folder) {
		$folderPath = $this->splitFolderPath($folder);
		foreach ($folderPath as &$i) {
			$i = imap_utf7_decode($i);
		}
		return $this->joinFolderPath($folderPath);
	}

	public function getFolderName($folder) {
		return $this->encodeFolder($folder);
	}

	public function getFullFolderName($folder) {
		return $this->getMailbox() . $this->getFolderName($folder);
	}

	public function trimMailbox($folder) {
		if (0 === strpos($folder, $this->getMailbox())) {
			$folder = substr($folder, strlen($this->getMailbox()));
		}
		return $folder;
	}

	public function trimFolder($folder, $trimFolder) {
		if (0 === strpos($folder, $trimFolder)) {
			$folder = substr($folder, strlen($trimFolder));

			if (0 === strpos($folder, $this->getFolderSeparator())) {
				$folder = substr($folder, strlen($this->getFolderSeparator()));
			}
		}
		return $folder;
	}

	public function getIgnoredFolders() {
		$conf = $this->getConf();
		return (isset($conf['ignoredFolders']) && is_array($conf['ignoredFolders'])) ?
			$conf['ignoredFolders'] : array();
	}

	public function getSubFolders($folder, $pattern = '%') {
		$subFolders = imap_list($this->getConnection(), $this->getFullFolderName($folder), $pattern);
		if (!empty($subFolders)) {
			foreach ($subFolders as &$subFolder) {
				$subFolder = $this->trimMailbox($subFolder);
				$subFolder = $this->decodeFolder($subFolder);
			}
			sort($subFolders);
			return array_values(array_diff($subFolders, $this->getIgnoredFolders()));
		}
		return false;
	}

	public function openFolder($folder) {
		return imap_reopen($this->getConnection(), $this->getFullFolderName($folder));
	}

	public function getFolderMessagesCount() {
		return imap_num_msg($this->getConnection());
	}
}
