# imapcopy

*imapcopy* is a command-line tool written in PHP to recursively copy all e-mail
messages and folders from one IMAP account to another.

It has successfully been tested with a
[Courier IMAP](http://www.courier-mta.org/imap/)-to-[Gmail](https://mail.google.com/)
migration of multiple IMAP accounts, some of which had up to 30.000 e-mail
messages (17 GB with lots of huge attachments) stored across over 450 folders.

## Features

* Recursively copy all e-mail messages and folders, also across servers
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
* Continue copying at a specific folder or message
* Only copy specific folders or even specific messages in folders
* SSL support
* Optionally ignore invalid SSL certificates
* Perform a test run with no changes made to the destination
* Detailed console output for logging and debugging

## Requirements

* [PHP](http://php.net/) 5.3.2+
* PHP extensions: imap, mbstring
* *on the user:* knowledge about the
[JSON notation](http://en.wikipedia.org/wiki/JSON) (see Configuration)

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

**NOTE:** Make sure you are in the `src/cli` directory.

There is one optional parameter `-test`, which will perform a test run with no
changes made to the destination:

```
php imapcopy.php config.json -test
```

A test run still produces all console output of a regular run.

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

		"ignoredFolders": [
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

The JSON needs to have a `src` and a `dst` object, otherwise *imapcopy* will
exit with an error.

### Source and Destination Settings

Both the source (`src`) and the destination (`dst`) have these settings.

**hostname**

...

**port**

...


### Source-specific Settings

...

### Destination-specific Settings

...

