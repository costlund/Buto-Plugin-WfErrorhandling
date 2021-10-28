<?php
class PluginWfErrorhandling{
  public static function event_shutdown($data){
    wfPlugin::includeonce('wf/array');
    $data = new PluginWfArray($data);
    /**
     * Get error.
     */
    $error = error_get_last();
    /**
     * If error.
     */
    if($error){
      /**
       * Server
       * Clean up from $_SERVER.
       */
      $server = new PluginWfArray($_SERVER);
      $server_alert = new PluginWfArray();
      $server_variables = array('HTTP_HOST', 'HTTP_USER_AGENT', 'HTTP_REFERER', 'SERVER_NAME', 'SERVER_ADDR', 'DOCUMENT_ROOT', 'REDIRECT_QUERY_STRING', 'REDIRECT_URL', 'REQUEST_METHOD', 'QUERY_STRING', 'REQUEST_URI');
      foreach($server_variables as $v){
        $server_alert->set($v, $server->get($v));
      }
      $error['server'] = $server_alert->get();
      /**
       * Session
       */
      $error['session'] = $_SESSION;
      /**
       * 
       */
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
          wfPlugin::includeonce('slack/webhook_v1');
          $slack_webhook = new PluginSlackWebhook_v1();
          $slack_webhook->url = $default->get('slack_settings/webhook');
          $slack_webhook->channel = $default->get('slack_settings/group');
          $slack_webhook->text = wfHelp::getYmlDump($error, true);
          $slack_webhook->send();
        }
        $element = wfArray::set($element, 'innerHTML/webmaster/innerHTML/slack/innerHTML', 'Slack message was sent!');
      }
      /**
       * method
       */
      if($default->get('method')){
        $post = new PluginWfArray();
        $post->set('error_message', wfArray::get($error, 'message'));
        $post->set('error_file', wfArray::get($error, 'file'));
        $post->set('error_line', wfArray::get($error, 'line'));
        $post->set('error_type', wfArray::get($error, 'type'));
        foreach($server_variables as $v){
          $post->set($v, $server->get($v));
        }
        $post->set('session_id', session_id());
        $post->set('session', wfHelp::getYmlDump($_SESSION, true));
        $post->set('phpversion', phpversion());
        wfPlugin::includeonce($default->get('method/plugin'));
        $plugin = $default->get('method/plugin');
        $obj = wfSettings::getPluginObj($plugin);
        $obj_method = $default->get('method/name');
        $obj->$obj_method($default->get('method'), $post->get());
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
}
