# Wunderlist plugin for Kanboard

This plugin allow you to import [Wunderlist](http://www.wunderlist.com/) tasks and lists directly from the user interface of [Kanboard](http://kanboard.net/) by uploading an export file.

![Latest release](https://img.shields.io/github/release/EpocDotFr/kanboard-wunderlist.svg) ![Kanboard version](https://img.shields.io/badge/Kanboard-1.0.19-red.svg) ![License](https://img.shields.io/github/license/EpocDotFr/kanboard-wunderlist.svg) 

## Prerequisites

  - Kanboard 1.0.19

## Installation

- Create the folder **plugins/Wunderlist** in the installation directory of Kanboard
- Copy all the files from this repository in the directory above

You can check if the plugin is correctly installed in the **Preferences** > **Plugins** menu.

## Usage

Go to the **Preferences** > **Import from Wunderlist** menu. Select a Wunderlist export file (JSON format) to import. Click on the **Import** button.

## Gotchas

  * This plugin may broke if you update Kanboard (the plugin system is in its alpha stage, as mentioned [in this article](http://kanboard.net/news/version-1.0.19))
  * Only administrators can access this feature
  * Existing tasks / projects are not checked yet (this means that duplicates may be created)

## End words

If you have questions or problems, you can [submit an issue](https://github.com/EpocDotFr/kanboard-wunderlist/issues).

You can even submit pull requests if you want. It's open-source man!