# Wunderlist plugin for Kanboard

This plugin allow you to import [Wunderlist](http://www.wunderlist.com/) tasks and lists directly from the user interface of [Kanboard](http://kanboard.net/) by uploading an export file.

![Latest release](https://img.shields.io/github/release/EpocDotFr/kanboard-wunderlist.svg) ![License](https://img.shields.io/github/license/EpocDotFr/kanboard-wunderlist.svg) 

## Prerequisites

![Kanboard version](https://img.shields.io/badge/Kanboard-1.0.19-red.svg)

## Installation

- Create the folder **plugins/Wunderlist** in the installation directory of Kanboard
- Copy all the files from this repository in the directory above

You can check if the plugin is correctly installed in the **Preferences** > **Plugins** menu.

## Usage

Go to the **Preferences** > **Import from Wunderlist** menu. Select a Wunderlist export file (JSON format) to import. Click on the **Import** button.

## How it works

Kanboard and Wunderlist are very different, so there's some things to know about what happens to your tasks and lists in certain cases:

  * Lists and folders are imported as projects
  * The default Kanboard's columns are created for each imported projects (according to your Kanboard configuration)
  * If a task is tagged as completed on Wunderlist, it will be tagged as closed on Kanboard
  * Public lists are imported as public projects
  * Starred tasks will have a color of red, otherwise yellow
  * Notes are imported as task description
  * Tasks are created in the default column of each projects

All the other data supported by Kanboard is imported with no problems.

## Gotchas

  * This plugin may broke if you update Kanboard (the plugin system is in its alpha stage, as mentioned [in this article](http://kanboard.net/news/version-1.0.19))
  * Only administrators can access this feature
  * Existing tasks / projects are not checked yet (this means that duplicates may be created)
  * The following things **cannot** be imported (they are not present in the Wunderlist export file):
    * Users
    * Attached files
    * Comments

## End words

If you have questions or problems, you can [submit an issue](https://github.com/EpocDotFr/kanboard-wunderlist/issues).

You can also submit pull requests. It's open-source man!
