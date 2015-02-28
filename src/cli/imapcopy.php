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
printf('    connecting via %s...', $src->getMailbox());
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

/*
printf("*** opening destination\n");
$dst = new Imap($conf['dst']);
printf('    connecting via %s...', $dst->getMailbox());
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
*/

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
	printf("    ... (f:%d/%d) %s\n", $srcFolderNum, $srcFoldersCount, utf8_encode($srcFolder));

	printf('        opening folder...');
	$_ = $src->openFolder($srcFolder);
	printf(" %s\n", test($_));

	printf('        counting folder messages...');
	$srcFolderMessagesCount = $src->getFolderMessagesCount();
	printf(" %s, %d folder message(s) found\n", test(0 < $srcFolderMessagesCount), $srcFolderMessagesCount);

	$srcMessagesCount += $srcFolderMessagesCount;
}
printf(">>> %d total source message(s) found\n", $srcMessagesCount);
if (0 == $srcMessagesCount) {
	printf(">>> nothing to do");
	die();
}
