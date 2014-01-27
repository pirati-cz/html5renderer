<?php
/**
 * Render Plugin for XHTML output with headers folding
 *
 * @author2 Vaclav Malek <vaclav.malek@pirati.cz>
 * @author Jiri Kaderavek <jiri.kaderavek@webstep.net>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_INC . 'inc/parser/xhtml.php';
require_once DOKU_PLUGIN . 'hspan/HTMLPurifier.standalone.php';

/**
 * The Renderer
 */
class renderer_plugin_piratihtml5 extends Doku_Renderer_xhtml {

    function canRender($format) {
      return ($format=='xhtml');
    }

    private $_content = '';
    private $_box = false;
    private $_toc = true;
    private $_fnotes = array();
    
     function header($text, $level, $pos){
          if(preg_match("/^BOX:.*/", $text, $matches)!=0){
               $this->_content = $this->doc;
               $this->_box = true;
               $this->doc = '';
          } else {
               //$hid = $this->_headerToLink($text,false);
               //$this->doc .= '<a name="'.$hid.'-2" id="'.$hid.'-2"></a><br/><br/>';
               parent::header($text, $level, $pos);
          }
     }

    function document_end(){
          parent::document_end();

          global $INFO;
          //var_dump($this->toc);
          if(is_null($INFO['prependTOC']) or empty($this->toc)) $this->_toc = false;
          
          //$c = '<div class="container-fluid">';

               $c .= '<div class="row-fluid">';
               if($this->_box){
                    $box = $this->doc;     
                    $c .= '<div class="span9">';
                         if($this->_toc) $c .= tpl_toc(true);
                         $c .= $this->_content;
                    $c .= '</div>';
                    $c .= '<div class="span3" id="thebox">';
                         $c .= $box;
                    $c .= '</div>';
               } else {
                    $c .= '<div class="span12">';
                         if($this->_toc) $c .= tpl_toc(true);
                         $c .= $this->doc;
                    $c .= '</div>';
                    $this->doc = '';
               }
               $c .= '</div>';

               $c .= '<div class="row-fluid">';
                    if ( count ($this->_fnotes) > 0 ) {
                         $c .= '<div class="footnotes">'.DOKU_LF;
                         $id = 0;
                         foreach ( $this->_fnotes as $footnote ) {
                              $id++;   // the number of the current footnote
                              // check its not a placeholder that indicates actual footnote text is elsewhere
                              if (substr($footnote, 0, 5) != "@@FNT") {
                                   // open the footnote and set the anchor and backlink
                                   $c .= '<div class="fn">';
                                   $c .= '<sup><a href="#fnt__'.$id.'" id="fn__'.$id.'" name="fn__'.$id.'" class="fn_bot">';
                                   $c .= $id.')</a></sup> '.DOKU_LF;

                                   // get any other footnotes that use the same markup
                                   $alt = array_keys($this->_fnotes, "@@FNT$id");

                                   if (count($alt)) {
                                        foreach ($alt as $ref) {
                                             // set anchor and backlink for the other footnotes
                                             $c .= ', <sup><a href="#fnt__'.($ref+1).'" id="fn__'.($ref+1).'" name="fn__'.($ref+1).'" class="fn_bot">';
                                             $c .= ($ref+1).')</a></sup> '.DOKU_LF;
                                        }
                                   }

                                   // add footnote markup and close this footnote
                                   $c .= $footnote;
                                   $c .= '</div>' . DOKU_LF;
                              }
                         }
                         $c .= '</div>'.DOKU_LF; // end class footnotes
                    } // end count() footnotes
               $c .= '</div>'; // end row-fluid

          //$c .= '</div>'; // end container fluid
          $this->doc = $c;
     }

     function footnote_close(){
          
        // recover footnote into the stack and restore old content
        $footnote = $this->doc;
        $this->doc = $this->store;
        $this->store = '';

        // check to see if this footnote has been seen before
        $i = array_search($footnote, $this->_fnotes);

        if ($i === false) {
            // its a new footnote, add it to the $footnotes array
            $id = count($this->_fnotes)+1;
            $this->_fnotes[count($this->_fnotes)] = $footnote;
        } else {
            // seen this one before, translate the index to an id and save a placeholder
            $i++;
            $id = count($this->_fnotes)+1;
            $this->_fnotes[count($this->_fnotes)] = "@@FNT".($i);
        }

        // output the footnote reference and link
        $this->doc .= '<sup><a href="#fn__'.$id.'" name="fnt__'.$id.'" id="fnt__'.$id.'" class="fn_top">'.$id.')</a></sup>';

     }

     function toc_additem($id, $text, $level){
          //if(isset($_GET['dev'])){
               //var_dump('-------------------');
               //var_dump($text);
          //}
          if($text!='BOX:related') parent::toc_additem($id/*.'-2'*/, $text, $level);
          //parent::toc_additem($id, $text, $level);
     }

     function html($text, $wrapper='code'){
          global $ID, $conf;
          if($conf['htmlok']){
               $meta_hmacs = p_get_metadata($ID, 'html_hmacs');
               $hmac = bin2hex(mhash(MHASH_MD5, $text, $conf['hmac_key']));
               if(is_array($meta_hmacs) and in_array($hmac,$meta_hmacs)) $this->doc .= $text;
               else {

                    // add new hmacs
                    //if($hmac=='15f55e1f5b5a3d19faad8d5a84cdc27e') p_set_metadata($ID,array('html_hmacs' => array('15f55e1f5b5a3d19faad8d5a84cdc27e')));
                    //

                    $purifier_config = HTMLPurifier_Config::createDefault();
                    $purifier_config->set('HTML.Doctype', 'HTML 4.01 Transitional');
                    $purifier = new HTMLPurifier($purifier_config);
                    $this->doc .= '<div style="display:none">hmac:'.$hmac.' : '.$ID.'</div>';
                    $this->doc .= $purifier->purify($text);
               }
          } else {
               $this->doc .= p_xhtml_cached_geshi($text, 'html4strict', $wrapper);
          }
     }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
