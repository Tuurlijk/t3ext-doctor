# TYPO3 extension: doctor
Doctor tells you how your TYPO3 installation is doing.

## Installation
Install it using composer:
```bash
composer require michielroos/doctor
```

Or clone it
```bash
git clone https://github.com/Tuurlijk/t3ext-doctor.git doctor
```

## Usage
Call any of the cli commands. The `doctor:info` command runs all the other commands.

```apacheconfig
EXTENSION "DOCTOR":
-------------------------------------------------------------------------------
  doctor:info                              Information about the whole system
  doctor:cache                             Cache information
  doctor:content                           Content information
  doctor:database                          Database information
  doctor:overrides                         Overrides information
  doctor:site                              System information
  doctor:typoscript                        Typoscript information
```

Show info about the database and some more info on table pages:
```bash
./typo3/cli_dispatch.phpsh extbase doctor:database --table pages
```

Example result part:
```apacheconfig
Record age for table pages
-------------------------------------------------------------------------------
  total records                            59,611
  Older than 1 years                       24,701 - 41.44%
  Older than 2 years                       11,191 - 18.77%
  Older than 3 years                       7,893 - 13.24%
  Older than 4 years                       3,321 - 5.57%
  Older than 5 years                       1,010 - 1.69%

Deleted records for table pages
-------------------------------------------------------------------------------
  total records                            59,611
  deleted records                          13,571 - 22.77%
```

Find unused content elements and plugin types:
```bash
./typo3/cli_dispatch.phpsh extbase doctor:content
```

Example output:
```apacheconfig
Content information
-------------------------------------------------------------------------------
  Total number of content elements         107,927

Content usage
-------------------------------------------------------------------------------
  contentType                              count
-------------------------------------------------------------------------------
  text                                     98,303
  uploads                                  29,522
  list                                     14,510
  image                                    8,739
  textpic                                  4,989
  header                                   4,918
  shortcut                                 4,899
  bullets                                  572
  menu                                     552
  media                                    535
  table                                    295
  html                                     224
  login                                    4
  div                                      1
  multimedia                               unused
  swfobject                                unused
  qtobject                                 unused
  search                                   unused
  default                                  unused

Plugin usage
-------------------------------------------------------------------------------
  pluginType                               count
-------------------------------------------------------------------------------
  extbaseplugin_thing                      2300
  someotherplugin_pi2                      99
  9                                        77
  powermail_pi1                            30
  cal_controller                           1
  multicolumn_pi1                          unused
  multicolumn_pi_sitemap                   unused
  powermail_pi2                            unused
  pagebrowse_pi1                           unused
  tt_address_pi1                           unused
```

## Roadmap
* Add documentation
* Add backend module?
* Use language labels
* Generate report document / pdf
* Add more scans
* Also make versions of he extension that run on TYPO3 versions 7 and up

## Known Issues
Currently this only runs on TYPO3 6.2 and only in the cli.
