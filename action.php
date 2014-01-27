<?php
/**
 *
 * Pirati: piratihtml5
 *
 * @author Vaclav Malek <vaclav.malek@pirati.cz>
 *
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_piratihtml5 extends DokuWiki_Action_Plugin
{

     function register(&$controller){
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'html5');
     }

     public function html5(&$event, $param){
          foreach($event->data['meta'] as $index => $meta){
               if($meta['name']=='date') unset($event->data['meta'][$index]);
          }
          foreach($event->data['script'] as $index => $script){
               $event->data['script'][$index] = array(
                    'type'=>$script['type'], 
                    '_data'=>$script['_data']
               );
               if(isset($script['src'])) $event->data['script'][$index]['src'] = $script['src'];
          }
     }
}

