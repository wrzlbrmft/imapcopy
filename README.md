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
migration of multiple IMAP accounts, some of which had up to 30.000 e-mail
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
* PHP extensions imap and mbstring
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
might change and you will have a hard time to do things like that.

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

## Migration Best Practice

*(work in progress)*
