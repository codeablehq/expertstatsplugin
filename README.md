[![Codeable](https://img.shields.io/badge/Codeable-Experts-blue.svg?style=flat-square)]()
# Codeable Expert Stats Plugin Readme
*Contributors:* Spyros Vlachopoulos, Panagiotis Synetos, John Leskas, Justin Frydman, Jonathan Bossenger, Rob Scott, Philipp Stracker

*Tested up to:* WordPress 5.2.2

Codeable Expert Stats plugin makes it easy for you to track and monitor your success as an expert on Codeable via an easy-to-understand dashboard for your personal/agency WordPress site.


### Processes

If you are opening an issue please use labels!

### Description

The dashboard and stats provided by the Codeable web app for experts is a bit lackluster which leaves a big gap for actively monitoring and tracking your success. This plugin aims to solve that problem by providing the data in a visual format that experts can learn from, track progress, and optimize their performance as Codeable experts. Now you can visualize your own performance on Codeable in many new ways and know where you need to focus to improve and grow on Codeable.

### Features

1. All your regular stats from Codeable now in your WordPress dashboard.
2. Filter stats by date range
3. Monthly averages of revenue and fees
4. Monthly money chart
5. Best month and best month’s revenue
6. Amounts range - Your tasks budget groups pie chart
7. Tasks per month bar graph
8. Client data - Fully sortable by revenue, avg. per task, total tasks, more…
9. Client data - Individual client detail modal
10. PERT Calculator


### Installation

Note: SSL enabled sites highly encouraged for the use of this plugin please.

1. Upload the `expertstatsplugin-master` folder and it's contents to the `/wp-content/plugins/` directory or install the .zip via the WP plugins panel in your WordPress admin dashboard
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it! You should now be able to access the Plugin's options via `Codeable Stats` link on WordPress dashboard menu.  You will need to enter your Codeable Username and Password under Settings then ‘Fetch remote data’.

### Get Automatic Updates When New Versions Released (Since version 0.0.4)
Use GitHub Updater plugin to allow automatic updates of this plugin from the GitHub repo.

Follow these steps:

1. Download and install the plugin https://github.com/afragen/github-updater to your wp site;
2. Ensure you have access to the private repo for this plugin!
3. In Github, click your picture (top right) to get the dropdown; and hit "Settings"
4. Under here select "Personal Access Tokens" and generate a new token (or use an existing one with the right permissions!) - give it a name like "github updater" or similar for future reference.
5. Check (tick, select) the box next to "Repo" and the three under this can stay selected.
6. Copy your token and keep it in a safe place if you want to use it again.
7. On your wp website, open Settings > Github Updater and if you already have the expertstatsplugin installed, this will show up. You will need to place your key to get updates.

If you don't have it installed, you can:

1. Hit the "Add plugin" tab
2. Paste in https://github.com/codeablehq/expertstatsplugin/ under URL
3. Leave the "github" part
4. Paste in the github key you just generated,
5. Hit "Install plugin"

This will give you the normal WP update notification when a new version ships, and its a 1 click update.

### Migrating from Old Version

You may want to truncate these tables, replace wp_ with your database prefix

```
TRUNCATE TABLE wp_codeable_amounts;
TRUNCATE TABLE wp_codeable_clients;
TRUNCATE TABLE wp_codeable_tasks;
TRUNCATE TABLE wp_codeable_transactions;
TRUNCATE TABLE wp_codeable_transcactions;
```

### Frequently Asked Questions

*There was a problem fetching remote data from Codeable*

Be sure that you set PHP timeout to 120 or more on your first fetch or if you have deleted the cached data.


### Screenshots

[![Money Charts](https://raw.githubusercontent.com/codeablehq/expertstatsplugin/master/screenshot1-money-charts.png?token=ACPiMX4GRnmFUQzH1KXutE40Y23hf5Pjks5Yvq0bwA%3D%3D)]()


[![Client Data](https://github.com/codeablehq/expertstatsplugin/raw/master/screenshot2-client-data.png)]()

[![Client Data Detail Modal ](https://github.com/codeablehq/expertstatsplugin/raw/master/screenshot3-client-detail-modal.png)]()
