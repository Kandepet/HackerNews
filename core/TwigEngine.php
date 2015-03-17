<?php

namespace HackerNews;

class TwigEngine {

   protected $loader = null;
   protected $engine = null;
   protected $template_dir = null;
   protected $template = null;
   protected $vars = null;
   protected $config = null;


   protected function addExtension($extension) {
      $this->engine->addExtension($extension);
   }

   protected function loadDefaultVars() {
      $this->vars = array(
         'config' => $this->config,
         'base_dir' => rtrim($this->config['site.base_path'], '/'),
         'base_url' => $this->config['site.base_url'] . $this->config['site.base_path'],
         'theme_dir' => $this->config['site.themes_dir'] . "/" . $this->config['site.theme'],
         'theme_url' => $this->config['site.base_url'] . $this->config['site.base_path'] .'/'
         . basename($this->config['site.themes_dir']) .'/'. $this->config['site.theme'],
            'site_title' => $this->config['site.title']
         );
   }

   protected function bootstrap() {
      $this->template_dir = $this->config['site.themes_dir'] . "/" . $this->config['site.theme'];
      $this->loadDefaultVars();
      $this->loader = new \Twig_Loader_Filesystem($this->template_dir);
      $this->engine = new \Twig_Environment($this->loader, $this->config['twig_config']);
      $this->addExtension(new \Twig_Extension_Debug());
   }

   public function __construct($config) {
      $this->config = $config;
      $this->bootstrap();
   }

   public function setVars($vars) {
      $this->vars = array_merge($this->vars, $vars);
      return $this;
   }

   public function render($template) {
      return $this->engine->render($template .'.html', $this->vars);
   }
}
