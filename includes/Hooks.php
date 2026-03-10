<?php
namespace MediaWiki\Extension\MarkupBabel;

use Article;
use MediaWiki\MediaWikiServices;
use Parser;
use ParserOptions;
use GeSHi;
use RequestContext;

class Hooks {
    public static function onParserFirstCallInit( Parser $parser ) {
        $arr = [
            'amsmath'     => 'amsmath',
            'm'           => 'amsmath',
            'latex'       => 'latex',
            'circo'       => 'circo',
            'circo-print' => 'circo_print',
            'fdp'         => 'fdp',
            'fdp-print'   => 'fdp_print',
            'graphviz'    => 'graph',
            'graph'       => 'graph',
            'graph-print' => 'graph_print',
            'neato'       => 'neato',
            'neato-print' => 'neato_print',
            'twopi'       => 'twopi',
            'twopi-print' => 'twopi_print',
            'pic-svg'     => 'pic_svg',
            'pic-svg-gif' => 'pic_svg',
            'plot'        => 'plot',
            'hbarchart'   => 'hbarchart',
            'vbarchart'   => 'vbarchart',
            'umlet'       => 'umlet',
            'umlgraph'    => 'umlgraph',
            'umlsequence' => 'umlsequence',
            'gantt'       => 'gantt',
        ];

        $config = MediaWikiServices::getInstance()->getMainConfig();
        if ( !$config->has( 'UseTex' ) || !$config->get( 'UseTex' ) ) {
            $arr['math'] = 'amsmath';
        }

        foreach ( $arr as $tag => $handler ) {
            $parser->setHook( $tag, function ( $text, $args, $parser, $frame ) use ( $handler ) {
                return self::process( $text, $handler, $args, $parser );
            } );
        }

        $langArray = [
            "actionscript", "ada", "apache", "asm", "asp",
            "bash", "c", "c_mac", "caddcl", "cadlisp", "cpp", "csharp", "css",
            "delphi", "html4strict", "java", "javascript", "lisp", "lua",
            "mpasm", "nsis", "objc", "oobas", "oracle8",
            "pascal", "perl", "php", "php-brief", "python",
            "qbasic", "smarty", "sql",
            "vb", "vbnet", "visualfoxpro",
            "xml"
        ];

        foreach ( $langArray as $lang ) {
            $parser->setHook( 'code-' . $lang, function ( $str ) use ( $lang ) {
                return self::geshiCallback( $str, $lang );
            } );
        }

        return true;
    }

    public static function geshiCallback( $str, $lang ) {
        if ( !class_exists( 'GeSHi' ) ) {
            return "<pre class=\"geshi-missing\" style=\"color:red;\" title=\"Please run 'composer install' in MarkupBabel dir\">GeSHi library is missing. Please run 'composer install' in the MarkupBabel extension directory.</pre>\n<pre>" . htmlspecialchars( $str ) . "</pre>";
        }
        $geshi = new GeSHi( $str, $lang );
        $geshi->set_overall_style( '' );
        $geshi->set_code_style( 'margin:0; padding:0; background:none; vertical-align:top;' );
        $code = $geshi->parse_code();
        $code = preg_replace( "/(^\s*<pre[^<>]*>\s*)&nbsp;\n|\n&nbsp;\s*(<\/pre>)/is", '$1$2', $code );
        return $code;
    }

    public static function onArticleViewHeader( Article $article, &$outputDone, &$useParserCache ) {
        $config = MediaWikiServices::getInstance()->getMainConfig();
        
        $extensions = $config->has( 'AutoHighlightExtensions' ) ? $config->get( 'AutoHighlightExtensions' ) : [
            'js'    => 'javascript',
            'css'   => 'css',
            'sh'    => 'bash',
            'diff'  => 'diff',
            'patch' => 'diff',
            'htm'   => 'html4strict',
            'html'  => 'html4strict',
            'xml'   => 'xml',
            'svg'   => 'xml',
        ];

        $title = $article->getTitle();
        $ns = $title->getNamespace();

        if ( $extensions && $ns !== NS_FILE ) {
            $ext = strtolower( pathinfo( $title->getText(), PATHINFO_EXTENSION ) );
            if ( isset( $extensions[$ext] ) && ( $article->getPage()->exists() || $ns === NS_MEDIAWIKI ) ) {
                $content = $article->getPage()->getContent();
                if ( $content instanceof \MediaWiki\Content\TextContent ) {
                    $text = $content->getText();
                    if ( !preg_match( '#^\s*<(source|code-|nowiki)#is', $text ) ) {
                        $lang = $extensions[$ext];
                        $outputDone = true;
                        $out = $article->getContext()->getOutput();
                        $out->addHTML( self::geshiCallback( $text, $lang ) );
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public static function onArticlePurge( \MediaWiki\Page\WikiPage $page ) {
        $services = MediaWikiServices::getInstance();
        $parser = $services->getParser();
        $user = RequestContext::getMain()->getUser();
        $content = $page->getContent();

        if ( $content instanceof \MediaWiki\Content\TextContent ) {
            $parser->parse( $content->getText(), $page->getTitle(), ParserOptions::newFromUser( $user ) );
        }
        return true;
    }

    public static function process( $strSrc, $strMode, $args, Parser $parser ) {
        $config = MediaWikiServices::getInstance()->getMainConfig();
        $uploadDir = $config->get( 'UploadDirectory' );
        $uploadPath = $config->get( 'UploadPath' );
        
        $baseDir = $uploadDir . '/generated';
        $strHash = md5( $strSrc . $strMode . var_export( $args, true ) );
        $rel = '/' . $strMode . '/' . $strHash[0] . '/' . substr( $strHash, 0, 2 ) . '/' . $strHash;

        $strDir = $baseDir . $rel;
        $strURI = '$URI' . $rel . '/';

        $oldumask = umask( 0 );
        if ( !file_exists( $strDir ) ) {
            mkdir( $strDir, 0777, true );
        }
        umask( $oldumask );

        $strLocalFile = "$strMode.source";
        $strFile = $strDir . "/" . $strLocalFile;

        $processor = new Processor( $strSrc, $strFile, $strMode, $strURI, $parser );
        $html = $processor->rendme( $args );

        $html = str_replace( $strURI, "{$uploadPath}/generated{$rel}/", $html );
        return $html;
    }
}
