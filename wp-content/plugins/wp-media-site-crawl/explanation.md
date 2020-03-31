# Site Crawl Explanation File
## The Problem
The desired outcome is a sitemap visible to both the user and the admin of a 
particular site that is refreshed hourly, and deletes the previous site crawl 
at the start of every new crawl.

This site map will show all of the links from the main homepage of a site to 
allow the admin to better optimize SEO for the site.

## The Tech Spec
### WP Media Crawl
Author: Jenny Rasmussen
19 March 2020 - 2 April 2020

#### Overview
Create a site crawl plugin for WordPress (WP) that allows an admin to manually 
improve the site SEO. Display site map to both the front end user and admin.

#### Goals and Product Requirements
* Add back end page to a WP installation for admin to log in and trigger a 
  crawl/view results
* When task is triggered, run immediately and set to run every hour.
* Delete results from last crawl, if exists
* Delete sitemap, if exists.
* Crawl homepage, and retrieve all internal hyperlinks
* Store results in database temporarily
* Display the results on the admin page
* Save homepage php file as html
* Create sitemap.html that shows links in list structure
* Allow front end user to see sitemap.html

#### Assumptions
* The site crawl can only be triggered once, every other time the admin wants 
  to see the sitemap, it will be pulled from the latest info in the database
* Admin will be able to see most recent sitemap displayed in settings page 
* Only links from the main homepage will be displayed in the sitemap
  
#### Out of Scope
* Recursively checking all internal links
* Being able to trigger the sitemap crawl more than once
* Delete stored links based on updates

#### Open Questions
None at this time.

#### Approach
I chose to write a WP plugin even though I've never done that before, because 
I have wanted to learn and this seemed a good opportunity. In addition, since 
this position is for writing and maintaining WP plugins, it just made sense.
    
I will start with reviewing all documentation on WP plugin creations and code 
standards. I will approach in a piecemeal fashion, almost as a mini-sprint for 
each task. The remainder of the approach in the list below.

* Initialize plugin
* Add settings page for Site Crawl plugin
* Implement activation and deactivation methods, including creation and deletion of storage table
* Write function for crawl script and data storage
* Write function to set cron to run hourly
* Write data clean up function (DB and sitemap.html)
* Write functions that combine above functions appropriately so that code is 
  easily re-useable
* Update from procedural to OOP

Even though I have these steps written out, I will adjust accordingly as I 
realize I missed something, or made an incorrect assumption.

#### Schema Changes
Add a table (wp_wpmedia_site_crawl) with the following schema.

`id int(10) NOT NULL AUTO_INCREMENT,`
`link_text varchar(255),`
`link varchar(255),`
`timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,`
`PRIMARY KEY id (id)`

#### Security and Privacy
Adding this table is low risk because it stores only internal data, though 
proper passwords and escaped user entered data are still a must to have a more 
secure server.

#### Test Plan
Mainly manual testing and checking the database. Though I do have some 
familiarity with PHPUnit, I am not yet comfortable enough with it to be 
certain that I wouldn't accidentally delete all the data in the database 
as is now. 

#### Deployment and Rollout
Manual install, not available in the WP Plugin Store 

#### Rollback Plan
Remove the folder from the repository

#### Monitoring and Logging
N/A for this project

#### Metrics
* Saves homepage as html for faster load time
* Displays sitemap to admin and user
* Stores links in database for one hour, then deleted and refreshed by new crawl
* Sets cron to run hourly after initial run    

#### Long Term Support
Uncertain at this time

## Technical Decisions
As you go through the history of this repo, you will notice a major switch 
from procedural to OOP. I chose to write this plugin in procedural form first 
to allow myself to become more familiar with writing a wordpress plugin, and 
the resources available to me before switching to OOP. This way I could more 
quickly develop a plugin for the first time, and feel more confident in my 
choices as I switched to OOP.
    
### `crawl_site()`
I chose to use `file_get_contents()` (line 132) with `get_site_url()` because 
it allows you to fetch the rendered HTML with one line of code, and is easy 
to read.
    
I also chose not to include the links that were linking back to the home page 
(lines 171-173), because in a recursive crawl through all links at found links, 
it would end up in an infinite loop of homepage links forever. Further clean up 
would be required before using this for a deeper dive, because we would want to 
make sure that if a sub-page links back to the homepage, we'd want to record 
that, without getting stuck in another infinite loop while crawling the site. 
    
As noted in a comment in the code for the function (lines 147-151), I had to 
use `libxml_use_internal_errors(true);` to suppress errors that are inherent 
in using `DOMDocument`. This is not ideal, but as PHP has not fixed these 
issues yet, it is the best course of action in this case.

## How The Code Works
### Construct
The `__construct()` method contains all of the actions and hooks that the 
plugin uses. This manner of organizing the code allows us to see all of the 
actions and hooks in one place in the class. This will help prevent repeating 
code. 
		
### Activation
The activation steps include several functions, `wpmedia_activate()`, 
`wpmedia_site_crawl_menu()`, and `wpmedia_site_crawl_options()`, which 
is called by `wpmedia_site_crawl_menu()` to actually create the options 
page.
		
#### `wpmedia_activate()` 
This function creates a new table in the WordPress DB schema for temporary 
storage of link text, link value, and the timestamp of the insertion. Instead 
of adding a timestamp through every loop, and possibly slowing down the method 
as it runs, the database will automatically add a timestamp to each row as it 
is inserted.
   			
#### `wpmedia_site_crawl_menu()` 
Standard options menu page creation.
            
#### `wpmedia_site_crawl_options()`
This method is called by the `wpmedia_site_crawl_menu()` when 
the options page is created on activation. This gives the admin 
user (if they have the permissions) directions on how to start 
a crawl, and a link to a front-end user facing sitemap for use 
after the initial crawl of the site. 
	
The button provided here will only ever need to be touched once, 
then the cron will crawl the site and update the sitemap 
on its own. Because of this, there is a check to see if a cron has 
already been set if the button is clicked more than once, and will 
only run the process once manually. If the admin user visits this 
page again, there is a tad of jquery to grey out the button on page 
load, to prevent the user from attempting to crawl the site again, 
and again attempt to set a cron			

The final step of this method is to display the sitemap, if there 
is one, and to show nothing if it does not exist, e.g.: on initial 
activation.
			
### Crawl Methods
The next several methods in the class are the meat of the class, and 
perform the crawling function and clean up. `manual_trigger_site_crawl()`, 
`delete_old_data()`, `crawl_site()`, and `sitemap()`.
		
#### `manual_trigger_site_crawl()`
	
A simple function that should only run once in every install/activation. 
This method calls the `crawl_site()` method for the initial crawl, 
and sets the cron job to run hourly if it is not already set. Though 
it may be safe to assume that this will only run once, given the 
safeguards in place to ensure that, it is still best to check whether 
the cron is set or not, before setting another one. It will prevent 
the cron jobs from overwhelming the site, and slowing down the site 
unnecessarily.

#### `delete_old_data()`
This method deletes all data in the custom wordpress table that is 
older than one hour. 
			
This method, and `manual_trigger_site_crawl()` are at the top of the 
class due to their size. I was concerned that with the length of the 
remaining crawl methods, they could get easily lost in the shuffle, 
so I left them at the top.
			
#### `crawl_site()`
* Sets up use of the WordPress globals for WP DB, and WP Filesystem.
* Method calls `delete_old_data()`, removing previous crawl's data, if exists
* Gets the table for link storage using $wpdb methods
* Gets the rendered HTML of the homepage
* Deletes existing index.html, and saves new copy
* Loads `$site_html` into a new DOMDocument for processing
* Loops through all `<a>` tags, ignoring any that are empty, anchor tags, or 
  are the homepage link, then adds to the sql array of link information
* If there `$sql` is not empty, execute, else return an error asking the user 
  to try again later

#### `sitemap()`
* Instantiates WPDB and WP Filesystem
* Sets a `$sitemap_html` variable with start of HTML for display to admin/user
* Gets custom table, and selects all links in the table from last crawl
* Loop through returned SQL results and builds HTML that is concatenated to 
  the `$sitemap_html` variable
* Gets directory of this plugin
* Deletes old sitemap if exists
* Creates and saves new sitemap.html
* Returns `$sitemap_html` for consumption on the admin options page
		    
### De-activation
Upon deactivation of the plugin, the `wpmedia_deactivate()` method 
deletes the custom table, and ends the cron job so that it won't 
throw errors trying to run a job that isn't there anymore.

## How This Solves The User's Problem
By displaying the links from the homepage in a nested list, this displays 
the relation of all links on the homepage to other pages on the site. In 
addition, refreshing every hour allows the user to continually improve on 
their SEO strategies with new links and data available to them.

## A Final Note
This project allowed me to learn and show my style at the same time, and I 
thoroughly enjoyed working on this project. It's been a while since I've 
worked on something like this for someone else, and enjoyed it this much. 

Thank you for having such a thoughtful and fun experience as a part of your 
technical interview. 
