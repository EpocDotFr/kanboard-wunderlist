# Wunderlist plugin for Kanboard

This plugin allow you to import [Wunderlist](http://www.wunderlist.com/) tasks and lists directly from the user interface of [Kanboard](http://kanboard.net/) by uploading a Wunderlist export file. It is the successor of [this script](https://github.com/EpocDotFr/WunderlistToKanboard).

> **Don't forget that Microsoft, which acquired Wunderlist back in 2015, will shut down Wunderlist at an unkown date.
> Migrate your tasks as soon as possible.**
> More information: https://techcrunch.com/2017/04/19/microsoft-to-shut-down-wunderlist-in-favor-of-its-new-app-to-do/

[![Latest release](https://img.shields.io/github/release/EpocDotFr/kanboard-wunderlist.svg)](https://github.com/EpocDotFr/kanboard-wunderlist/releases) [![License](https://img.shields.io/github/license/EpocDotFr/kanboard-wunderlist.svg)](https://github.com/EpocDotFr/kanboard-wunderlist/blob/master/LICENSE.md)

## Prerequisites

[![Kanboard version](https://img.shields.io/badge/Kanboard-1.0.48-red.svg)](https://kanboard.net/news/version-1.0.48)

## Installation

**Manually, latest release:**

  1. Navigate to the `plugins` directory, located in the root installation directory of Kanboard
  2. Create the `Wunderlist` folder
  3. Download the latest release [here](https://github.com/EpocDotFr/kanboard-wunderlist/releases)
  4. Extract all files in the directory contained in the archive file you downloaded in the `Wunderlist` directory created previously

**Using the Git CLI, bleeding-edge code:**

  1. Navigate to the `plugins` directory, located in the root installation directory of Kanboard
  2. `git clone https://github.com/EpocDotFr/kanboard-wunderlist.git Wunderlist`

You can check if the plugin is correctly installed in the **Preferences** > **Plugins** menu.

## Usage

It is very simple.

### Creating the Wunderlist export file

  1. Go on the Wunderlist web app
  2. Go in **Menu** > **Account Settings** tab
  3. Click on the **Create Backup** button
  4. Download the file when it's done

### Importing in Kanboard

  1. Open Kanboard
  2. Go to the **Preferences** > **Import from Wunderlist** menu
  3. Select a Wunderlist export file (JSON format) to import, then click on the **Import** button

## How it works

Kanboard and Wunderlist are very different, so there's some things to know about what happens to your tasks and lists in certain cases:

  * Lists and folders are imported as projects
  * The default Kanboard's columns are created for each imported projects (according to your Kanboard configuration)
  * If a task is tagged as completed on Wunderlist, it will be tagged as closed on Kanboard
  * Public lists are imported with public access active (otherwise no)
  * Starred tasks will have a color of red, otherwise yellow
  * Notes are imported as task description
  * Tasks are created in the default column / swimlane of each projects

All the other data supported by Kanboard is imported with no problems.

## Gotchas

  * This plugin may broke if you update Kanboard. If so, please [submit an issue](https://github.com/EpocDotFr/kanboard-wunderlist/issues)
  * Only administrators can access this feature
  * Duplicates are **not** checked
  * Hooks **are** fired for each tasks created (and also all other relevant hooks)
  * The following things **cannot** be imported (they aren't available in the Wunderlist export file):
    * Users (and of course: users assigned to tasks)
    * Attached files
    * Comments

## Changelog

See [here](https://github.com/EpocDotFr/kanboard-wunderlist/releases).

## Contributors

Thanks to:

  - [@85pando](https://github.com/85pando)
  - [@dmkcv](https://github.com/dmkcv)
  - [@CMiksche](https://github.com/CMiksche)

## End words

If you have questions or problems, you can [submit an issue](https://github.com/EpocDotFr/kanboard-wunderlist/issues).

You can also submit pull requests. It's open-source man!
