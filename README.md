# Buto-Plugin-WfErrorhandling

Handle errors.

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

## Settings

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
          domain_filter: localh
````

### Domain filter

If value is localh only domains having this text (like localhost) will send a Slack message.


