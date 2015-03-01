<?php
require_once('../lib/classes/Imap.php');
require_once('../lib/utils.php');

if (2 > $argc) {
	printf("usage: php %s <confFile>\n", basename($argv[0]));
	die();
}
else {
	$confFile = $argv[1];

	if (file_exists($confFile)) {
		$conf = json_decode(file_get_contents($confFile), true);

		if (!is_array($conf) || !isset($conf['src']) || !isset($conf['dst'])) {
			printf("error: invalid/incomplete configuration\n");
			die();
		}
	}
	else {
		printf("error: configuration file not found (%s)\n", $confFile);
		die();
	}
}

printf("*** opening source\n");
$src = new Imap($conf['src']);
printf('    connecting via \'%s\'...', $src->getMailbox());
$_ = $src->connect();
printf(" %s\n", test($_));
if (!$src->isConnected()) {
	printf(">>> error opening source\n");
	die();
}
else {
	printf(">>> source is ready\n");
}

printf("\n");

printf("*** opening destination\n");
$dst = new Imap($conf['dst']);
printf('    connecting via \'%s\'...', $dst->getMailbox());
$_ = $dst->connect();
printf(" %s\n", test($_));
if (!$dst->isConnected()) {
	printf(">>> error opening destination\n");
	die();
}
else {
	printf(">>> destination is ready\n");
}

printf("\n");

printf('*** Counting total source folders...');
$srcFolders = $src->getSubFolders('', '*');
printf(" %s\n", test(is_array($srcFolders)));
if (!is_array($srcFolders)) {
	printf(">>> error counting total source folders\n");
	die();
}
$srcFoldersCount = count($srcFolders);
printf(">>> %d total source folder(s) found\n", $srcFoldersCount);
if (0 == $srcFoldersCount) {
	printf(">>> nothing to do");
	die();
}

printf("\n");

printf('*** counting total source messages...');
$srcMessagesCount = 0;
$srcFolderNum = 0;
foreach ($srcFolders as $srcFolder) {
	$srcFolderNum++;

	printf("\n");
	printf("    ... (f:%d/%d) '%s'\n", $srcFolderNum, $srcFoldersCount, utf8_encode($srcFolder));

	printf('        opening folder...');
	$_ = $src->openFolder($srcFolder);
	printf(" %s\n", test($_));
	if (!$_) {
		continue;
	}

	printf('        counting folder messages...');
	$srcFolderMessagesCount = $src->getFolderMessagesCount();
	printf(" %s, %d folder message(s) found\n", test(0 < $srcFolderMessagesCount), $srcFolderMessagesCount);
	if (0 == $srcFolderMessagesCount) {
		continue;
	}

	$srcMessagesCount += $srcFolderMessagesCount;
}
printf(">>> %d total source message(s) found\n", $srcMessagesCount);
if (0 == $srcMessagesCount) {
	printf(">>> nothing to do");
	die();
}

printf("\n");

printf("*** recursively copying folders and messages...");
$srcMessageNum = 0;
$srcFolderNum = 0;
foreach ($srcFolders as $srcFolder) {
	$srcFolderNum++;

	printf("\n");
	printf("    ... (f:%d/%d) '%s'\n", $srcFolderNum, $srcFoldersCount, utf8_encode($srcFolder));

	printf('        opening folder...');
	$_ = $src->openFolder($srcFolder);
	printf(" %s\n", test($_));
	if (!$_) {
		continue;
	}

	printf('        counting folder messages...');
	$srcFolderMessagesCount = $src->getFolderMessagesCount();
	printf(" %s, %d folder message(s) found\n", test(0 < $srcFolderMessagesCount), $srcFolderMessagesCount);
	if (0 == $srcFolderMessagesCount && $src->ignoreEmptyFolders()) {
		continue;
	}

	printf('        destination folder will be');
	$folderPath = $src->splitFolderPath($srcFolder);
	$dstFolder = $dst->joinFolderPath($folderPath, true);
	$dstFolder = $dst->getMappedFolder($dstFolder);
	$dstFolder = $dst->popFolder($dstFolder);
	$dstFolder = $dst->pushFolder($dstFolder);
	printf(" '%s'\n", utf8_encode($dstFolder));

	printf('        creating destination folder...');
	$_ = $dst->createFolder($dstFolder);
	printf(" %s\n");
	if (!$_) {
		continue;
	}

	for ($srcFolderMessageNum = 1; $srcFolderMessageNum <= $srcFolderMessagesCount; $srcFolderMessageNum++) {
		$srcMessageNum++;

		printf("\n");
		printf('        ... (f:%d/%d;m:%d/%d,%d/%d)',
			$srcFolderNum,
			$srcFoldersCount,
			$srcFolderMessageNum,
			$srcFolderMessagesCount,
			$srcMessageNum,
			$srcMessagesCount
		);

		$srcMessageHeaderInfo = $src->getMessageHeaderInfo($srcFolderMessageNum);
		$srcMessageSubject = isset($srcMessageHeaderInfo->subject) ? $srcMessageHeaderInfo->subject : '';
		printf(" '%s'\n", utf8_encode(mb_decode_mimeheader($srcMessageSubject)));

		$srcMessageSize = isset($srcMessageHeaderInfo->Size) ? $srcMessageHeaderInfo->Size: '?';
		printf('            loading source message (%s byte(s))...', $srcMessageSize);
		$srcMessage = $src->loadMessage($srcMessageNum);
		printf(" %s, %d byte(s)\n", test(0 < strlen($srcMessage)), strlen($srcMessage));

		printf('            storing destination message...');
		$_ = $dst->storeMessage($dstFolder, $srcMessage, $srcMessageHeaderInfo);
		printf(" %s\n", test($_));
	}
}
