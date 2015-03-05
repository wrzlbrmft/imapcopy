# imapcopy

*imapcopy* is a command-line tool written in [PHP](http://php.net/) to
recursively copy all e-mail messages and folders from one
[IMAP](http://en.wikipedia.org/wiki/Internet_Message_Access_Protocol) account to
another.

The purpose of *imapcopy* is either to be run directly on the source or the
destination mail server, or at least remotely on a data-center server with a
very high bandwidth. This allows you to do a fast server-to-server migration
instead of using a client like Thunderbird, first downloading everything and
then uploading it again through a relatively slow home DSL connection.

*imapcopy* has successfully been tested with a
[Courier IMAP](http://www.courier-mta.org/imap/)-to-[Gmail](https://mail.google.com/)
migration of multiple IMAP accounts, some of which had up to 30,000 e-mail
messages (one was 17 GB of e-mails with lots of huge attachments stored across
over 450 folders).

## Features

* Recursively copy all e-mail messages and folders across servers
* Copy multi-part messages including all their attachments
* Preserve the date and status of messages including
  * Unread/read
  * Answered
  * Flagged
  * Deleted
  * Draft
* Optionally auto-remove invalid spaces from folder names
* Support different folder separators for both source and destination, e.g. `.`
and `/`
* Exclude specific folders from being copied
* Re-locate the folder structure as a whole
  * Get it out of a sub-folder from the source
  * Put it into an arbitrary sub-folder in the destination
* Map individual source folders to different destination folders
* Merge multiple source folders into one destination folder
* Start/continue copying at a specific folder or message
* Only copy specific folders or even specific messages in folders
* SSL support
* Optionally ignore invalid SSL certificates
* Perform a test run with no changes made to the destination
* Generous console output showing the progress and details for logging and
debugging

## Requirements

* [PHP](http://php.net/) 5.3.2+ command-line interpreter
* PHP extensions: [IMAP](http://php.net/manual/en/book.imap.php) and
[Multibyte String](http://php.net/manual/en/book.mbstring.php) (mbstring)
* *On the user:*
  * Knowledge about the
[JSON notation](http://en.wikipedia.org/wiki/JSON) (see *Configuration*)
  * Shell access (although it is possible, better not run *imapcopy* from your
browser...)

## Download

You can download the latest version as a [ZIP
file](https://github.com/wrzlbrmft/imapcopy/archive/master.zip) from GitHub.

## Usage

*imapcopy* is run from the command-line using the PHP command-line interpreter.
The configuration is passed as a mandatory file name parameter, pointing to the
configuration file (see below).

This would load the configuration from the file `config.json`:

```
php imapcopy.php config.json
```

There is one optional parameter `-test`, which will perform a test run with no
changes made to the destination:

```
php imapcopy.php config.json -test
```

A test run still produces all the console output of a regular run.

### Important Tip

You should make sure that no changes are made to both the source and destination
IMAP account, while *imapcopy* is running on them. This also includes new
incoming messages being delivered to the source IMAP account.

Although it will not break or delete anything (the source account is accessed
read-only by default), you are well-advised to avoid *any* change made to the
source IMAP account, until you have completely copied it to the destination.
Especially if you need to run *imapcopy* several times to get the job fully
done, e.g. due to errors.

The reason is, that *imapcopy* counts all folders and their messages in the
source IMAP account, before it actually starts copying them. The folder and
message numbers are used to indicate the copying progress.
But you can also use the numbers to start/continue copying at a specific folder
or message or only copy specific folders or even specific messages in folders.
If you make changes to the source IMAP account, the folder and message numbers
might change and you will have a hard time to do things like that if you are in
the middle of a migration.

For more information see also *Migration Best Practice*.

## Configuration

The configuration is provided as a file written using the
[JSON notation](http://en.wikipedia.org/wiki/JSON).

**Example:**

```json
{
	"src": {
		"hostname": "127.0.0.1",
		"port": 143,
		"username": "john.doe",
		"password": "*****",
		"ssl": false,
		"sslNovalidateCert": false,
		"readOnly": true,
		"folderSeparator": ".",

		"excludedFolders": [
			"INBOX.Drafts",
			"INBOX.Spam",
			"INBOX.Trash"
		],
		"onlyFoldersNum": [1, 2, 3],
		"onlyFolderMessagesNum": {
			"2": [7],
			"3": [8, 9]
		},
		"startFolderNum": 1,
		"startFolderMessageNum": 1
	},
	"dst": {
		"hostname": "imap.gmail.com",
		"port": 993,
		"username": "john.doe@gmail.com",
		"password": "*****",
		"ssl": true,
		"sslNovalidateCert": false,
		"readOnly": false,
		"folderSeparator": "/",

		"trimFolderPath": true,
		"mappedFolders": {
			"INBOX/Sent": "[Gmail]/Gesendet"
		},
		"popFolder": "INBOX",
		"pushFolder": ""
	}
}
```

You can also find the above example in `cli/example.json`.

**NOTE:** The JSON must have a `src` and a `dst` object, otherwise *imapcopy*
will exit with an error.

### Source and Destination Settings

Both the source (`src`) and the destination (`dst`) have these settings in
common:

#### hostname, port, username, password

Set the hostname (or IP address) and the port to connect to the mail server and
add credentials to login to the IMAP account.

**NOTE:** If no port is specified the default IMAP port 143 is used.

#### ssl, sslNovalidateCert

To connect via SSL set `ssl` to `true`. If your mail server certificate is
invalid (e.g. self-signed), you can set `sslNovalidateCert` to `true` so the
certificate is not validated on connect.

#### readOnly

It is a good idea to keep this setting set to `true` for the source account at
any time. Of course, keep it on `true` for the destination. ;-)

**NOTE:** When doing a test run using the `-test` command-line option, then
the `readOnly` setting of the destination is overwritten with `true`.

#### folderSeparator

Set the folder separator used by the mail server. Typically it is a dot `.`
(e.g. [Courier IMAP](http://www.courier-mta.org/imap/)) or a slash `/` (e.g.
[Gmail](https://mail.google.com/)).

### Source-specific Settings

#### excludedFolders

List folders that you want to exclude from being copied.

This will exclude `Drafts`, `Spam` and `Trash` under the inbox (`INBOX`):

```
		...
		"excludedFolders": [
			"INBOX.Drafts",
			"INBOX.Spam",
			"INBOX.Trash"
		],
		...
```

If you do not want to exclude and folders, set `excludedFolders` to empty:

```
		...
		"excludedFolders": [],
		...
```

**NOTE:** Folders excluded by `excludedFolders` will not get a folder number
for the current run of *imapcopy* (see also *Folder and Message Numbers*).

#### onlyFoldersNum

If you only want to copy specific folders, then list the numbers of the folders
you want to copy here.

This will only copy the folders number 1, 2 and 3:

```
		...
		"onlyFoldersNum": [1, 2, 3],
		...

```

If you want to copy all folders, set `onlyFoldersNum` to empty:

```
		...
		"onlyFoldersNum": [],
		...

```

**NOTE:** If you use `onlyFoldersNum` in combination with `excludedFolders`,
keep in mind, that folders excluded by `excludedFolders` will not get a folder
number for the current run of *imapcopy* (see also *Folder and Message
Numbers*).

#### onlyFolderMessagesNum

If you only want to copy specific messages of a folder, then list the number
of the folder and of its messages you want to copy here:

This will only copy message number 7 of folder number 2 and messages number 8
and 9 of folder number 3:

```
		...
		"onlyFolderMessagesNum": {
			"2": [7],
			"3": [8, 9]
		},
		...
```

If you do not only want to copy specific messages of folders, then set
`onlyFolderMessagesNum` to empty:

```
		...
		"onlyFolderMessagesNum": {},
		...
```

#### Combining onlyFoldersNum and onlyFolderMessagesNum

If you want to copy specific folders and from *some* of these folders only
specific messages, then you can combine `onlyFoldersNum` and
`onlyFolderMessagesNum`.

This will only copy messages from folders numers 1, 2 and 3. Folder number 1
will be copied with all its messages, but *imapcopy* will only copy message
number 7 of folder number 2 and messages number 8 and 9 of folder number 3:

```
		...
		"onlyFoldersNum": [1, 2, 3],
		"onlyFolderMessagesNum": {
			"2": [7],
			"3": [8, 9]
		},
		...
```

**NOTE:** If you combine `onlyFoldersNum` and `onlyFolderMessagesNum`, make sure
that all folders used in `onlyFolderMessagesNum` are also listed in
`onlyFoldersNum`. The following setting will *not* copy any message from folders
number 2 and 3:

```
		...
		"onlyFoldersNum": [1],
		"onlyFolderMessagesNum": {
			"2": [7],
			"3": [8, 9]
		},
		...
```

#### startFolderNum, startFolderMessageNum

...

### Destination-specific Settings

...

## Folder and Message Numbers

...

## Migration Best Practice

*(work in progress)*
