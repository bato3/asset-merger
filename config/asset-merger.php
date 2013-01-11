<?php defined('SYSPATH') OR die('No direct script access.');

return array(
    'merge'      => array(Kohana::PRODUCTION, Kohana::STAGING, Kohana::DEVELOPMENT),
    'process'      => array(Kohana::PRODUCTION, Kohana::STAGING),
    'folder'     => 'assets',
    'load_paths' => array(
        Assets::JAVASCRIPT => array(DOCROOT.'media'.DIRECTORY_SEPARATOR, APPPATH.'views'.DIRECTORY_SEPARATOR),
        Assets::STYLESHEET => array(DOCROOT.'media'.DIRECTORY_SEPARATOR, APPPATH.'views'.DIRECTORY_SEPARATOR),
    ),
    'processor'  => array(
        Assets::JAVASCRIPT => 'jsminplus',
        Assets::STYLESHEET => 'cssmin',
    ),
    'docroot' => DOCROOT
);