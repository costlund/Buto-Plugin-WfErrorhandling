<?php
/**
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
</ul>
<p>
Param error_reporting in application settings.yml must have value 0 when using this plugin.
</p>
#code-yml#
error_reporting: 0
#code#
 */
class PluginWfErrorhandling{
  /**
  <p>
  Default values:
  </p>
  #code-yml#
  #load:[app_dir]/plugin/[plugin]/data/default.yml:load#
  #code#
  <p>
  Registrate shutdown event in theme settings.yml.
  </p>
  <p>
  Set alert to true to output element with error info. 
  Set log to true if put error to file /log/wf/errorhandling/YYDDMM.log.yml. The folder must exist. 
  Set email to true along with proper settings for smtp to send email.
  </p>
  <p>
  If user has role "webmaster" error details will be output also.
  </p>
  <p>
  Example of usage:
  </p>
  #code-yml#
  events:
    shutdown:
      wf_error:
        plugin: 'wf/errorhandling'
        method: 'shutdown'
        data:
          alert: true
          log: true
          email: true
          smtp: 'yml:/theme/[theme]/data/phpmailer.yml:wf_phpmailer_gmail'
  #code#
   */
  public static function event_shutdown($data){
    wfPlugin::includeonce('wf/array');
    $data = new PluginWfArray($data);
    /**
     * Get error.
     */
    $error = error_get_last();
    /**
     * If error and not type deprecated.
     */
    if($error && $error['type'] != 8192){
      $error['server'] = $_SERVER;
      $error['server']['HTTP_COOKIE'] = '*';
      $error['session'] = $_SESSION;
      $default = wfFilesystem::loadYml(__DIR__.'/data/default.yml');
      $default = new PluginWfArray(array_merge($default, $data->get('data')));
      $element = wfFilesystem::loadYml(__DIR__.'/data/alert.yml');
      if($default->get('alert')){
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/message/innerHTML', wfArray::get($error, 'message'));
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/file/innerHTML', 'File: '.wfArray::get($error, 'file'));
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/line/innerHTML', 'Line: '.wfArray::get($error, 'line'));
      }
      /**
       * Log.
       */
      if($default->get('log')){
        // Log if folder /log/wf/errorhandling exist in theme directory.
        $dir = '/log/wf/errorhandling';
        if(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/theme_dir').$dir)){
          $log = array(microtime() => array('error' => $error, 'session' => $_SESSION, 'server' => $_SERVER));
          $log = sfYaml::dump($log, 99);
          $filename = wfArray::get($GLOBALS, 'sys/theme_dir').$dir.'/'.date('ymd').'.log.yml';
          file_put_contents($filename, $log, FILE_APPEND);
          $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/log/innerHTML', "Log was written to: $filename.");
        }else{
          $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/log/innerHTML', "Log could not be written due to dir $dir not exist!");
        }
      }
      /**
       * Email.
       */
      if($default->get('email')){
        wfPlugin::includeonce('wf/phpmailer');
        $wf_phpmailer = new PluginWfPhpmailer();
        $default->set('smtp', wfSettings::getSettingsFromYmlString($default->get('smtp')));
        if(is_array($default->get('smtp'))){
          $default->set('smtp/Body', wfHelp::getYmlDump($error, true));
          $temp = $wf_phpmailer->send($default->get('smtp'));
          if(wfArray::get($temp, 'success')){
            $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/email/innerHTML', "Mail was sent to: ".wfArray::get($temp, 'smtp/To').".");
          }else{
            $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/email/innerHTML', "Mail could NOT be sent to: ".wfArray::get($temp, 'smtp/To').".");
          }
        }else{
          // smtp is not an array...
        }
      }
      /**
       * Slack.
       */
      $slack_filter = true;
      if($data->get('data/slack_settings/domain_filter') && !strstr(wfServer::getServerName(), $data->get('data/slack_settings/domain_filter'))){
        $slack_filter = false;
      }
      if($default->get('slack') && $slack_filter){
        if($default->get('slack_settings/webhook') && $default->get('slack_settings/group') ){
          PluginWfErrorhandling::slack($default->get('slack_settings/webhook'), wfHelp::getYmlDump($error, true), $default->get('slack_settings/group'));
        }
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/slack/innerHTML', 'Slack message was sent!');
      }
      /**
       * Alert.
       */
      if($default->get('alert')){
        if(wfUser::hasRole('webmaster')){
          /**
           * Alert more if webmaster.
           */
          $element = wfArray::set($element, 'innerHTML/webmaster/settings/disabled', false);
        }
        wfDocument::renderElement(array($element));
      }
    }
  }
  public static function slack($webhook, $message, $room = "some-group", $icon = ":red_card:"){
    $room = ($room) ? $room : "buggar";
    $data = "payload=" . json_encode(array(
            "channel"       =>  "#{$room}",
            "text"          =>  $message,
            "icon_emoji"    =>  $icon
        ));
    $ch = curl_init($webhook);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }
}























