<?php

$aliases['dev'] = array(
  'site' => 'dcomms',
  'env' => 'dev',
  'root' => '/var/www/current/app',
  'remote-host' => 'dcomms.qa.previousnext.com.au',
  'remote-user' => 'deployer',
  'ssh-options' => '-p 11079',
  'path-aliases' => array(
    '%real-files' => '/var/www/shared/files/',
    '%dump-dir' => '/var/tmp',
  ),
);

$aliases['staging'] = array(
  'site' => 'dcomms',
  'env' => 'staging',
  'root' => '/var/www/current/app',
  'remote-host' => 'dcomms.staging.previousnext.com.au',
  'remote-user' => 'deployer',
  'ssh-options' => '-p 11080',
  'path-aliases' => array(
    '%real-files' => '/var/www/shared/files/',
    '%dump-dir' => '/var/tmp',
  ),
);
