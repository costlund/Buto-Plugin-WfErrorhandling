# Buto-Plugin-WfErrorhandling
Buto plugin to handle errors.


<p>
Handle errors via event shutdown. 
</p>
<p>
Features:
</p>
<ul>
<li>Alert in browser.
<li>Log to file.
<li>Email.
<li>Slack.
</ul>

<p>
Param error_reporting in application settings.yml must have value 0 when using this plugin.
</p>


<p>
Add in theme settings yml.
</p>

````
events:
  shutdown:
    -
      plugin: 'wf/errorhandling'
      method: 'shutdown'
      data:
        alert: true
        log: false
        email: false
        slack: true
        slack_settings:
          webhook: 'url to slack hook'
          group: _group_
          domain_filter: _optional_filter_to_restrict_by_domain_
````


