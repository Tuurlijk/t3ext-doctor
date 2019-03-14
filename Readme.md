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
  doctor:backenduser                       Backend user information
  doctor:cache                             Cache information
  doctor:content                           Content information
  doctor:cruftcount                        Cruft Count; show amount and
                                           percentage of deleted and hidden
                                           records
  doctor:database                          Database information
  doctor:overrides                         Overrides information
  doctor:site                              System information
  doctor:typoscript                        Typoscript information
```

### Database info
Show info about the database and some more info on table pages:
```bash
./typo3/cli_dispatch.phpsh extbase doctor:database --table pages
```

Example result part:
```apacheconfig
Record age for table pages
-------------------------------------------------------------------------------
  total                                    60,482
  Younger than 1 year                      34,402 - 56.88%
  Older than 1 years                       26,080 - 43.12%
  Older than 2 years                       11,270 - 18.63%
  Older than 3 years                       8,064 - 13.33%
  Older than 4 years                       3,362 - 5.56%
  Older than 5 years                       1,046 - 1.73%

Deleted records for table pages
-------------------------------------------------------------------------------
  total records                            60,482
  deleted records                          14,352 - 23.73%

Deleted record age for table pages
-------------------------------------------------------------------------------
  total                                    14,352
  Younger than 1 year                      12,218 - 85.13%
  Older than 1 years                       2,134 - 14.87%

Hidden records for table pages
-------------------------------------------------------------------------------
  total records                            60,482
  hidden records                           7,355 - 12.16%

Hidden record age for table pages
-------------------------------------------------------------------------------
  total                                    7,355
  Younger than 1 year                      5,368 - 72.98%
  Older than 1 years                       1,987 - 27.02%
  Older than 2 years                       673 - 9.15%
  Older than 3 years                       412 - 5.60%
  Older than 4 years                       286 - 3.89%
  Older than 5 years                       73 - 0.99%
```

### Amount of cruft in the database
When databases grow old, they tend to accumulate a lot of hidden and deleted records. For pages we have the trash can, but for all the other tables we can't really tell how many records are deleted without asking the database directly.
Show info about all the hidden and deleted cruft in the database:
```bash
./typo3/cli_dispatch.phpsh extbase doctor:cruftcount
```

Example result part:
```apacheconfig
Deleted records for table sys_refindex
-------------------------------------------------------------------------------
  total records                            1,220,516
  deleted records                          174,700 - 14.31%

Deleted records for table fe_users
-------------------------------------------------------------------------------
  total records                            171,627
  deleted records                          6,703 - 3.91%

Deleted records for table tt_content
-------------------------------------------------------------------------------
  total records                            148,542
  deleted records                          63,056 - 42.45%

Hidden records for table tt_content
-------------------------------------------------------------------------------
  total records                            148,542
  hidden records                           7,196 - 4.84%

Deleted records for table sys_file_collection
-------------------------------------------------------------------------------
  total records                            63,614
  deleted records                          20,195 - 31.75%

Hidden records for table sys_file_collection
-------------------------------------------------------------------------------
  total records                            63,614

Deleted records for table pages
-------------------------------------------------------------------------------
  total records                            60,668
  deleted records                          14,352 - 23.66%

Hidden records for table pages
-------------------------------------------------------------------------------
  total records                            60,668
  hidden records                           7,355 - 12.12%

Deleted records for table sys_file_reference
-------------------------------------------------------------------------------
  total records                            40,499
  deleted records                          20,261 - 50.03%

Hidden records for table sys_file_reference
-------------------------------------------------------------------------------
  total records                            40,499
  hidden records                           600 - 1.48%

Deleted records for table tx_cal_event
-------------------------------------------------------------------------------
  total records                            5,211
  deleted records                          1,475 - 28.31%

Hidden records for table tx_cal_event
-------------------------------------------------------------------------------
  total records                            5,211
  hidden records                           358 - 6.87%

Deleted records for table tt_address
-------------------------------------------------------------------------------
  total records                            4,809
  deleted records                          852 - 17.72%

Deleted records for table tx_djoaccordion_domain_model_accordion
-------------------------------------------------------------------------------
  total records                            3,923
  deleted records                          2,343 - 59.72%

Deleted records for table tx_powermail_domain_model_answers
-------------------------------------------------------------------------------
  total records                            2,806
  deleted records                          172 - 6.13%
```

### Unused content
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

### Backend user info
Show details of a backend user:
```bash
./typo3/cli_dispatch.phpsh extbase doctor:backenduser --uid 287
```

Example output:

```apacheconfig
Tinus Tester
--------------------------------------------------------------------------------------
  Id                                       287
  Real name                                Tinus Tester
  Username                                 tinus
  Description                              Test Account
  Email                                    tinus@test.com
  Activated                                yes
  Admin                                    no

  Groups:
--------------------------------------------------------------------------------------
  Content editor                           Can create and edit content and pages
    Powermail                              Create and edit powermail forms

  Tables view:
--------------------------------------------------------------------------------------
 - pages
 - sys_collection
 - tt_content
 - tx_powermail_domain_model_field
 - tx_powermail_domain_model_form
 - tx_powermail_domain_model_mail
 - tx_powermail_domain_model_page

  Tables modify:
--------------------------------------------------------------------------------------
 - pages
 - sys_collection
 - tt_content
 - tx_powermail_domain_model_field
 - tx_powermail_domain_model_form
 - tx_powermail_domain_model_mail
 - tx_powermail_domain_model_page
```

## Roadmap
* Add documentation
* Add backend module?
* Use language labels
* Generate report document / pdf
* Add more scans
* Also make versions of he extension that run on TYPO3 versions 8 and up

## Known Issues
Extesnsion is known to work on TYPO3 6.2 and 7.6 and only in the cli.
