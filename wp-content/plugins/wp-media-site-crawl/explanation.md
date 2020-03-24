<h1>Site Crawl Explanation File</h1>

<h2>The Problem</h2>

<p>
    The desired outcome is a sitemap visible to both 
    the user and the admin of a particular site that 
    is refreshed hourly, and deletes the previous site 
    crawl at the start of every new crawl.
</p>

<p>
    This site map will show all of the links from the 
    main homepage of a site to allow the admin to 
    better optimize SEO for the site.
</p>

<h2>The Tech Spec</h2>

<h3>WP Media Crawl</h3>
<p>Author: Jenny Rasmussen</p>
<p>20 March 2020 - 1 April 2020</p>

<h4>Overview</h4>
<p>
    Create a site crawl plugin for WordPress (WP) that 
    allows an admin to manually improve the site SEO. 
    Display site map to both the front end user and 
    admin.
</p>

<h4>Goals and Product Requirements</h4>
    <ul>
        <li>
            Add back end page to a WP installation for 
            admin to log in and trigger a crawl/view 
            results
        </li>
        <li>
            When task is triggered, run immediately and 
            set to run every hour.
        </li>
        <li>
            Delete results from last crawl, if exists
        </li>
        <li>
            Delete sitemap, if exists.
        </li>
        <li>
            Crawl homepage, and retrieve all internal 
            hyperlinks
        </li>
        <li>
            Store results in database temporarily
        </li>
        <li>
            Display the results on the admin page
        </li>
        <li>
            Save homepage php file as html
        </li>
        <li>
            Create sitemap.html that shows links 
            in list structure
        </li>
        <li>
            Allow front end user to see sitemap.html
        </li>
    </ul>

<h4>Assumptions</h4>
    <ul>
        <li>
            The site crawl can only be triggered once, every 
            other time the admin wants to see the sitemap, 
            it will be pulled from the latest info in the 
            database
        </li>
        <li>
            Admin will be able to see most recent sitemap 
            displayed in settings page 
        </li>
        <li>
            Only links from the main homepage will be 
            displayed in the sitemap
        </li>
    </ul>
    
<h4>Out of Scope</h4>
    <ul>
        <li>
            Recursively checking all internal links
        </li>
        <li>
            Being able to trigger the sitemap crawl more 
            than once
        </li>
        <li>
            Only delete stored links based on time
        </li>
    </ul>
    
<h4>Open Questions</h4>
    <p>
        Not sure why I need to save the homepage php file 
        as html?
    </p>
    
<h4>Approach</h4>
    <p>
        I chose to write a WP plugin even though I've never 
        done that before because I have wanted to learn and 
        this seemed a good opportunity. In addition, since 
        this position is for writing and maintaining WP 
        plugins, it felt like a good time to learn.               
    </p>
    <p>
        I will start with reviewing all documentation on WP 
        plugin creations and code standards. I will approach 
        in a piecemeal fashion, almost as a mini-sprint for 
        each task. The remainder of the approach in the list 
        below.
    </p>
    <ul>
        <li>
            Initialize plugin
        </li>
        <li>
            Add settings page for Site Crawl plugin
        </li>
        <li>
            Implement activation and deactivation methods, 
            including creation and deletion of storage table
        </li>
        <li>
            Write function for crawl script and data storage
        </li>
        <li>
            Write function to set cron to run hourly
        </li>
        <li>
            Write data clean up function (DB and sitemap.html)
        </li>
        <li>
            Write functions that combine above functions 
            appropriately so that code is easily re-useable
        </li>
    </ul>
    <p>
        Even though I have these steps written out, I will 
        adjust accordingly as I realize I missed something, 
        or made an incorrect assumption.
    </p>

<h4>Schema Changes</h4>
    <p>
        Add a table (wp_wpmedia_site_crawl) with the 
        following schema.<br />
        id int(10) NOT NULL AUTO_INCREMENT,<br />
        link_text varchar(255),<br />
        link varchar(255),<br />
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,<br />
        PRIMARY KEY id (id)
    </p>

<h4>Security and Privacy</h4>
    <p>
        Adding this table is low risk because it stores only 
        internal data, though proper passwords and escaped 
        user entered data are still a must to have a more 
        secure server.
    </p>

<h4>Test Plan</h4>
    <p>
        Mainly manual testing and checking the database. 
        Though I do have some familiarity with PHPUnit, I 
        am not yet comfortable enough with it to be certain 
        that I wouldn't accidentally delete all the data in 
        the database as is now. 
    </p>

<h4>Deployment and Rollout</h4>
    <p>
        Manual install, not available in the WP Plugin Store 
    </p>

<h4>Rollback Plan</h4>
    <p>Remove the folder from the repository</p>

<h4>Monitoring and Logging</h4>
    <p>N/A for this project</p>

<h4>Metrics</h4>
    <p>
        Whether or not this moves me to the next part of 
        the process!
    </p>

<h4>Long Term Support</h4>
    <p>
        Uncertain at this time
    </p>

<h2>Technical Decisions</h2>
<h2>How the code works</h2>
<h2>How this solves the user's problem</h2>
