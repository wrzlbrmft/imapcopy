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
		$options = '';

		if (isset($conf['ssl']) && !empty($conf['ssl'])) {
			$options .= '/ssl';
		}

		if (isset($conf['sslNovalidateCert']) && !empty($conf['sslNovalidateCert'])) {
			$options .= '/novalidate-cert';
		}

		if (isset($conf['readOnly']) && !empty($conf['readOnly'])) {
			$options .= '/readonly';
		}

		return $options;
	}

	public function getMailbox() {
		$conf = $this->getConf();
		$hostname = isset($conf['hostname']) ? $conf['hostname'] : '';
		$port = isset($conf['port']) ? $conf['port'] : '143';

		return sprintf('{%s:%d/imap%s}',
			$hostname,
			$port,
			$this->getMailboxOptions()
		);
	}

	public function isConnected() {
		return NULL !== $this->getConnection();
	}

	public function connect() {
		if (!$this->isConnected()) {
			$conf = $this->getConf();
			$username = isset($conf['username']) ? $conf['username'] : '';
			$password = isset($conf['password']) ? $conf['password'] : '';

			$connection = imap_open(mb_convert_encoding($this->getMailbox(), 'UTF7-IMAP', 'UTF-8'), $username, $password);
			if (false !== $connection) {
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

	public function joinFolderPath(array $folderPath, $trim = false) {
		if ($trim) {
			$conf = $this->getConf();
			if (isset($conf['trimFolderPath']) && !empty($conf['trimFolderPath'])) {
				foreach ($folderPath as &$i) {
					$i = trim($i);
					while (false !== strpos($i, '  ')) {
						$i = str_replace('  ', ' ', $i);
					}
				}
			}
		}
		return implode($this->getFolderSeparator(), $folderPath);
	}

	public function encodeFolder($folder) {
		$folderPath = $this->splitFolderPath($folder);
		foreach ($folderPath as &$i) {
			$i = mb_convert_encoding($i, 'UTF7-IMAP', 'UTF-8');
		}
		return $this->joinFolderPath($folderPath);
	}

	public function decodeFolder($folder) {
		$folderPath = $this->splitFolderPath($folder);
		foreach ($folderPath as &$i) {
			$i = mb_convert_encoding($i, 'UTF-8', 'UTF7-IMAP');
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

	public function popFolder($folder) {
		$conf = $this->getConf();
		$popFolder = isset($conf['popFolder']) ? $conf['popFolder'] : '';

		if (!empty($popFolder)) {
			if (0 === strpos($folder, $popFolder)) {
				$folder = substr($folder, strlen($popFolder));

				if (0 === strpos($folder, $this->getFolderSeparator())) {
					$folder = substr($folder, strlen($this->getFolderSeparator()));
				}
			}
		}
		return $folder;
	}

	public function pushFolder($folder) {
		$conf = $this->getConf();
		$pushFolder = isset($conf['pushFolder']) ? $conf['pushFolder'] : '';

		if (!empty($pushFolder)) {
			if (empty($folder)) {
				$folder = $pushFolder;
			}
			else {
				$folder = $pushFolder . $this->getFolderSeparator() . $folder;
			}
		}
		return $folder;
	}

	public function getExcludedFolders() {
		$conf = $this->getConf();
		return (isset($conf['excludedFolders']) && is_array($conf['excludedFolders'])) ?
			$conf['excludedFolders'] : array();
	}

	public function getMappedFolders() {
		$conf = $this->getConf();
		return (isset($conf['mappedFolders']) && is_array($conf['mappedFolders'])) ?
			$conf['mappedFolders'] : array();
	}

	public function getSubFolders($folder, $pattern = '%') {
		$subFolders = imap_list($this->getConnection(), $this->getFullFolderName($folder), $pattern);
		if (!empty($subFolders)) {
			foreach ($subFolders as &$subFolder) {
				$subFolder = $this->trimMailbox($subFolder);
				$subFolder = $this->decodeFolder($subFolder);
			}
			sort($subFolders);
			return array_values(array_diff($subFolders, $this->getExcludedFolders()));
		}
		return false;
	}

	public function openFolder($folder) {
		return imap_reopen($this->getConnection(), $this->getFullFolderName($folder));
	}

	public function getFolderMessagesCount() {
		return imap_num_msg($this->getConnection());
	}

	public function getMappedFolder($folder) {
		$mappedFolders = $this->getMappedFolders();
		if (array_key_exists($folder, $mappedFolders)) {
			return $mappedFolders[$folder];
		}
		return $folder;
	}

	public function createFolder($folder) {
		return imap_createmailbox($this->getConnection(), $this->getFullFolderName($folder));
	}

	public function getMessageHeaderInfo($messageNum) {
		return imap_headerinfo($this->getConnection(), $messageNum);
	}

	public function loadMessage($messageNum) {
		return imap_fetchbody($this->getConnection(), $messageNum, '');
	}

	public function getMessageOptions($messageHeaderInfo) {
		$options = array();

		if (isset($messageHeaderInfo->Unseen) && !trim($messageHeaderInfo->Unseen)) {
			$options[] = '\Seen';
		}

		if (isset($messageHeaderInfo->Answered) && trim($messageHeaderInfo->Answered)) {
			$options[] = '\Answered';
		}

		if (isset($messageHeaderInfo->Flagged) && trim($messageHeaderInfo->Flagged)) {
			$options[] = '\Flagged';
		}

		if (isset($messageHeaderInfo->Deleted) && trim($messageHeaderInfo->Deleted)) {
			$options[] = '\Deleted';
		}

		if (isset($messageHeaderInfo->Draft) && trim($messageHeaderInfo->Draft)) {
			$options[] = '\Draft';
		}

		return implode(' ', $options);
	}

	public function getMessageInternalDate($messageHeaderInfo) {
		return date('d-M-Y H:i:s O', $messageHeaderInfo->udate);
	}

	public function storeMessage($folder, $message, $headerInfo, $messageFlags = NULL) {
		$messageOptions = $this->getMessageOptions($headerInfo);

		if ($this->isMessageFlagsEnabled() && $messageFlags != NULL) {
			$messageOptions .= ' ' . $messageFlags;
		}

		return imap_append($this->getConnection(), $this->getFullFolderName($folder), $message,
			$messageOptions,
			$this->getMessageInternalDate($headerInfo));
	}

	public function isBeforeStartFolderNum($folderNum) {
		$conf = $this->getConf();
		$startFolderNum = isset($conf['startFolderNum']) ? $conf['startFolderNum'] : 1;

		return $folderNum < $startFolderNum;
	}

	public function isBeforeStartFolderMessageNum($folderNum, $folderMessageNum) {
		$conf = $this->getConf();
		$startFolderNum = isset($conf['startFolderNum']) ? $conf['startFolderNum'] : 1;
		$startFolderMessageNum = isset($conf['startFolderMessageNum']) ? $conf['startFolderMessageNum'] : 1;

		if ($folderNum < $startFolderNum) {
			return true;
		}
		elseif ($folderNum == $startFolderNum) {
			return $folderMessageNum < $startFolderMessageNum;
		}
		return false;
	}

	public function isOnlyFolderNum($folderNum) {
		$conf = $this->getConf();
		$onlyFoldersNum = isset($conf['onlyFoldersNum']) ? $conf['onlyFoldersNum'] : array();

		if (empty($onlyFoldersNum)) {
			return true;
		}
		return in_array($folderNum, $onlyFoldersNum);
	}

	public function isOnlyFolderMessageNum($folderNum, $folderMessageNum) {
		$conf = $this->getConf();
		$onlyFolderMessagesNum = isset($conf['onlyFolderMessagesNum']) ? $conf['onlyFolderMessagesNum'] : array();
		$onlyFolderMessagesNum = isset($onlyFolderMessagesNum[$folderNum]) ?
			$onlyFolderMessagesNum[$folderNum] : array();

		if (empty($onlyFolderMessagesNum)) {
			return true;
		}
		return in_array($folderMessageNum, $onlyFolderMessagesNum);
	}

	public function isMessageFlagsEnabled() {
		$conf = $this->getConf();

		if (isset($conf['flags']) && !empty($conf['flags'])) {
			return true;
		}
		else
			return false;
	}

	public function getFolderMessagesFlags($length) {
		$messagesFlags = array();
		$FLAGS_OFFSET = 44;

		$headers = imap_headers($this->getConnection());
		if ($headers == true) {
			foreach ($headers as $header) {
				$flags = NULL;

				if (preg_match("/{.*?}/", $header, $matches, PREG_OFFSET_CAPTURE, $FLAGS_OFFSET) == true) {
					foreach ($matches as $match) {
						if ($match[1] != $FLAGS_OFFSET) {
							continue;
						}

						$flags = ltrim($match[0], "{");
						$flags = rtrim($flags, "}");
						break;
					}
				}

				$messagesFlags[] = $flags;
			}
		}

		// Need array to start at 1 to match message indices
		array_unshift($messagesFlags, "dummy");
		unset($messagesFlags[0]);

		return $messagesFlags;
	}

}
