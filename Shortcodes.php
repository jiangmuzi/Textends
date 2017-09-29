<?php
/**
 * Typecho 短代码功能
 * @author 绛木子 <master@lixianhua.com>
 */

class Textends_Shortcodes{

    static private $tags = array();

    public static function add($tag, $func){
        if ( '' == trim($tag) ) {
            return _t( 'Invalid shortcode name: Empty name given.' );
        }
        if ( 0 !== preg_match( '@[<>&/\[\]\x00-\x20=]@', $tag ) ) {
            /* translators: 1: shortcode name, 2: space separated list of reserved characters */
            return sprintf( __( 'Invalid shortcode name: %1$s. Do not use spaces or reserved characters: %2$s' ), $tag, '& / < > [ ] =' );
        }
        self::$tags[$tag] = $func;
    }

    public static function remove($tag){
        unset(self::$tags[$tag]);
    }

    public static function clear(){
        self::$tags = aray();
    }

    public static function exists($tag){
        return array_key_exists( $tag, self::$tags );
    }

    public static function has($content, $tag){
        if ( false === strpos( $content, '[' ) ) {
            return false;
        }
    
        if ( self::exists( $tag ) ) {
            preg_match_all( '/' . self::getRegex() . '/', $content, $matches, PREG_SET_ORDER );
            if ( empty( $matches ) )
                return false;
    
            foreach ( $matches as $shortcode ) {
                if ( $tag === $shortcode[2] ) {
                    return true;
                } elseif ( ! empty( $shortcode[5] ) && self::has( $shortcode[5], $tag ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function execute($content, $ignoreHtml = false){
        if ( false === strpos( $content, '[' ) || empty(self::$tags) || !is_array(self::$tags) ) {
            return $content;
        }
        preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
        $tagNames = array_intersect( array_keys( self::$tags ), $matches[1] );
        
        if ( empty( $tagNames ) ) {
            return $content;
        }
        $pattern = self::getRegex( $tagNames );
        $content = preg_replace_callback( "/$pattern/", array('Textends_Shortcodes','executeTag'), $content);

        return $content;
    }

    public static function executeTag($m){
        if ( $m[1] == '[' && $m[6] == ']' ) {
            return substr($m[0], 1, -1);
        }
        $tag = $m[2];
        if ( ! is_callable( self::$tags[ $tag ] ) ) {
            return $m[0];
        }

        $attr = self::parseAtts( $m[3] );
        $attr = new Typecho_Config($attr);
        $content = isset( $m[5] ) ? $m[5] : null;

        $output = $m[1] . call_user_func( self::$tags[$tag], $attr, $content, $tag ) . $m[6];

        return $output;
    }

    public static function parseAtts($text){
        $atts = array();
        $pattern = self::getAttsRegex();
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                    $atts[] = stripcslashes($m[8]);
            }
    
            // Reject any unclosed HTML elements
            foreach( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    public static function getRegex($tagnames = null){
        if ( empty( $tagnames ) ) {
            $tagnames = array_keys( self::$tags );
        }
        $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
    
        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        return
              '\\['                              // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "($tagregexp)"                     // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag ...
            .     '\\]'                          // ... and closing bracket
            . '|'
            .     '\\]'                          // Closing bracket
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    public static function getAttsRegex() {
        return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    }
}