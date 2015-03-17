# Hacker News clone in PHP
This is a clone of [Hackernews](http://news.ycombinator.com) written in PHP.

## Features

- Submit stories or ask questions.
- Recursive comments
- Vote on questions and comments
- Stories are ranked based on user votes using an algorithm similar to [hacker news ranking](http://www.righto.com/2013/11/how-hacker-news-ranking-really-works.html).

## Screen shots

### Front Page
![Front Page](http://i.imgur.com/lxLYign.png)

### Comments
![Comments](http://i.imgur.com/8eWHGsr.png)


## System Requirements

This has been tested on
- PHP >= 5.2.X
- MySQL >= 5.6

##Installation

Installation requires access to a command line.

* Clone/download the repository
* Install dependencies
```
composer install
```
1. Rename config.ini.template to config.ini
2. Update config.ini with your database information
  * Make sure the database already exists (this installation does not create the database)
  * Make sure the db user has permission to create tables.
3. execute php install.php
4. edit your crontab and add cron.php to execute every 30minutes.
  * Open crontab for editing
  ```
  crontab -e
  ```
  Add this line, with ABSOLUTE_PATH_TO_INSTALL_FOLDER set correctly.
  ```
  */30 * * * * php /ABSOLUTE_PATH_TO_INSTALL_FOLDER/cron.php
  ```
5. goto http://{{YOUR_WEBSITE}}/signup.php and create a new user:
6. Done. Start creating stories.

## To Do

* Rank comments using the same time weighted algorithm as stories
* Change comment table structure to make it more [efficient](http://explainextended.com/2009/03/17/hierarchical-queries-in-mysql/).

## License

Hackernews is released under the MIT License. See [LICENSE](LICENSE) file for details.
