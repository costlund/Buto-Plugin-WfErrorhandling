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

    //wfHelp::yml_dump($data, true);
    
    $error = error_get_last();
    // Handle error if there is one.
    if($error){
      /**
       * Add extra params to error.
       */
      $error['server'] = $_SERVER;
      $error['session'] = $_SESSION;
      // Default values.
      $default = wfFilesystem::loadYml(__DIR__.'/data/default.yml');
      // Rewrite default.
      $default = array_merge($default, wfArray::get($data, 'data'));
      // Element to output.
      $element = wfFilesystem::loadYml(__DIR__.'/data/alert.yml');
      // Alert.
      if($default['alert']){
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/message/innerHTML', wfArray::get($error, 'message'));
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/file/innerHTML', 'File: '.wfArray::get($error, 'file'));
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/line/innerHTML', 'Line: '.wfArray::get($error, 'line'));
      }
      // Log.
      if($default['log']){
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
      // Log.
      if($default['email']){
        //include_once wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/phpmailer/action.class.php';
        wfPlugin::includeonce('wf/phpmailer');
        $wf_phpmailer = new PluginWfPhpmailer();
        $default['smtp'] = wfSettings::getSettingsFromYmlString($default['smtp']);
        if(is_array($default['smtp'])){
          $default = wfArray::set($default, 'smtp/Body', wfHelp::getYmlDump($error, true));
          $temp = $wf_phpmailer->send($default['smtp']);
          //wfHelp::yml_dump($temp);
          if(wfArray::get($temp, 'success')){
            $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/email/innerHTML', "Mail was sent to: ".wfArray::get($temp, 'smtp/To').".");
          }else{
            $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/email/innerHTML', "Mail could NOT be sent to: ".wfArray::get($temp, 'smtp/To').".");
          }
        }else{
          // smtp is not an array...
        }
      }
      if($default['slack']){
        if(isset($default['slack_settings']) && isset($default['slack_settings']['webhook']) && isset($default['slack_settings']['group'])){
          PluginWfErrorhandling::slack($default['slack_settings']['webhook'], wfHelp::getYmlDump($error, true), $default['slack_settings']['group']);
        }
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/slack/innerHTML', 'Slack message was sent!');
      }
      if($default['alert']){
        // Render.
        if(wfUser::hasRole('webmaster')){
          // If user has role webmaster we show detailed info.
          $element = wfArray::set($element, 'innerHTML/webmaster/settings/disabled', false);
        }
        wfDocument::renderElement(array($element));
        
      }
      //Todo: debug_backtrace()?
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























