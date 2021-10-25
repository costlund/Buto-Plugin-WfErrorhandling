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
````

### Method
By using param data/method one could let other plugin handle data.
````
        method:
          plugin: 'error/push'
          name: push
          data:
            url: https://error.world.com/error/insert
````

### Slack
If domain_filter is localh only domains having this text (like localhost) will send a Slack message.
````
        slack: true
        slack_settings:
          webhook: 'url to slack hook'
          group: _group_
          domain_filter: localh
````


