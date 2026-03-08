<?php
namespace MediaWiki\Extension\MarkupBabel;

use MediaWiki\MediaWikiServices;
use Sanitizer;
use Parser;
use ParserOptions;
use SVGMetadataExtractor;

class Processor {
    public $Content = '';
    public $Source = '';
    public $Filename = '';
    public $BaseDir = '';
    public $Mode = '';
    public $Cache = '';
    public $URI = '';
    private $parser;
    
    public $dotpath = "";
    public $gnuplotpath = "";
    public $texpath = "";
    public $inkscapepath = "";
    public $umlgraphpath = "";
    public $cacheHomeDir = "";

    public function __construct( $content, $path, $mode, $uri, Parser $parser ) {
        $config = MediaWikiServices::getInstance()->getMainConfig();
        $uploadDir = $config->get( 'UploadDirectory' );

        $this->Content = $content;
        $this->Source = $path;
        $this->Filename = basename( $path );
        $this->BaseDir = dirname( $path );
        $this->Mode = $mode;
        $this->Cache = $path . ".cache";
        $this->URI = $uri;
        $this->parser = $parser;

        if ( DIRECTORY_SEPARATOR === '\\' ) {
            $baseDir = $config->get( 'BaseDirectory' );
            $this->dotpath = realpath( $baseDir . "/../../app/graphviz/bin" ) . "/";
            $this->gnuplotpath = realpath( $baseDir . "/../../app/gnuplot/bin" ) . "/p";
            $this->texpath = realpath( $baseDir . "/../../app/xetex/bin/win32" ) . "/";
            $this->inkscapepath = realpath( $baseDir . "/../../app/inkscape" ) . "/";
            $this->umlgraphpath = realpath( $baseDir . "/../../app/umlgraph/bin" ) . "/";
        }

        $this->cacheHomeDir = "$uploadDir/cachehome";

        $oldumask = umask( 0 );
        if ( !file_exists( $this->cacheHomeDir ) ) {
            mkdir( $this->cacheHomeDir, 0777, true );
        }
        umask( $oldumask );
    }

    public function format_error( $err ) {
        $mode = htmlspecialchars( $this->Mode );
        $source = htmlspecialchars( $this->Source );
        $error = htmlspecialchars( $err );
        return "<div class=\"screenonly\">'{$mode}' generation for '{$source}' failed:<pre>{$error}</pre></div>\n";
    }

    public function rendme( $args ) {
        global $wgRequest;
        if ( file_exists( $this->Cache ) && $wgRequest->getVal( 'action' ) !== 'purge' ) {
            return file_get_contents( $this->Cache );
        }
        
        file_put_contents( $this->Source, $this->Content );
        if ( !file_exists( $this->Source ) ) {
            return $this->format_error( "No write permission to {$this->Source}" );
        }
        
        chdir( $this->BaseDir );
        $mode = $this->Mode;
        $res = $this->$mode( $args );
        file_put_contents( $this->Cache, $res );
        return $res;
    }

    public static function imagesizes( $file, $divby = 1 ) {
        $r = "";
        $width = 0;
        $height = 0;
        if ( $image = @imagecreatefrompng( $file ) ) {
            $width = ceil( imagesx( $image ) / $divby );
            $height = ceil( imagesy( $image ) / $divby );
            $r = "width=\"$width\" height=\"$height\"";
            imagedestroy( $image );
        }
        return [ $r, $width, $height ];
    }

    public function anchor_cur_title( $m ) {
        $titleUrl = $this->parser->getTitle()->getFullUrl();
        return $m[1] . $titleUrl . '#' . Sanitizer::escapeIdForAttribute( $m[2] );
    }

    public function generate_graphviz( $dot, $mode = "" ) {
        $mapname = md5( $this->BaseDir );
        wfShellExec( "{$this->dotpath}{$dot} -Tsvg -o {$this->Source}.svg {$this->Source} >{$this->Source}.err 2>&1" );
        wfShellExec( "{$this->dotpath}{$dot} -Tcmap -o {$this->Source}.map {$this->Source} >>{$this->Source}.err 2>&1" );
        wfShellExec( "{$this->dotpath}{$dot} -Tpng  -o {$this->Source}.png {$this->Source} >>{$this->Source}.err 2>&1" );
        
        if ( $mode == "print" ) {
            wfShellExec( "{$this->dotpath}{$dot} -Tpng -Gdpi=196 -o {$this->Source}.print.png {$this->Source} >>{$this->Source}.err 2>&1" );
        }
        
        if ( !file_exists( $this->Source . '.png' ) || !file_exists( $this->Source . '.svg' ) ) {
            $err = str_replace( "\n", "<br />", file_get_contents( "{$this->Source}.err" ) );
            return "<div class=\"error\">\n$err\n</div>";
        }

        $svg = file_get_contents( $this->Source . '.svg' );
        $svg = preg_replace_callback( '/(xlink:href=[\"\'])#([^<>\"\']*)/is', [ $this, 'anchor_cur_title' ], $svg );
        $svg = preg_replace( '#<a([^<>]*xlink:href=[^<>]*[^/])(/?)>#is', '<a$1 target="_parent"$2>', $svg );
        file_put_contents( $this->Source . '.svg', $svg );

        list( $wh, $w, $h ) = self::imagesizes( "{$this->Source}.png" );
        $w++; $h++;

        $map = file_get_contents( "{$this->Source}.map" );
        
        $str = "<object width=\"$w\" height=\"$h\" type=\"image/svg+xml\" data=\"{$this->URI}{$this->Filename}.svg\" style=\"overflow: hidden\">\n" .
               "<map name=\"$mapname\">$map</map>\n" .
               "<img $wh src=\"{$this->URI}{$this->Filename}.png\" usemap=\"#{$mapname}\"/>\n" .
               "<a class=\"dotsvg\" href=\"{$this->URI}{$this->Filename}.svg\">[svg]</a>\n" .
               "</object>";

        if ( $mode == "print" ) {
            list( $wh ) = self::imagesizes( "{$this->Source}.print.png", 2 );
            $str = "<div class=\"screenonly\">$str</div>\n" .
                   "<div class=\"printonly\">\n" .
                   "<img $wh src=\"{$this->URI}{$this->Filename}.print.png\" />\n" .
                   "</div>";
        }
        return str_replace( "\n", "", $str );
    }

    public function graph() { return $this->generate_graphviz( "dot" ); }
    public function graph_print() { return $this->generate_graphviz( "dot", "print" ); }
    public function neato() { return $this->generate_graphviz( "neato" ); }
    public function neato_print() { return $this->generate_graphviz( "neato", "print" ); }
    public function twopi() { return $this->generate_graphviz( "twopi" ); }
    public function twopi_print() { return $this->generate_graphviz( "twopi", "print" ); }
    public function circo() { return $this->generate_graphviz( "circo" ); }
    public function circo_print() { return $this->generate_graphviz( "circo", "print" ); }
    public function fdp() { return $this->generate_graphviz( "fdp" ); }
    public function fdp_print() { return $this->generate_graphviz( "fdp", "print" ); }

    public function gantt( $args ) {
        $data = [];
        $task_idx = [];
        $min = $max = NULL;
        $lines = explode( "\n", $this->Content );
        $xtics = [];
        
        foreach ( $lines as $line ) {
            if ( !preg_match( '/^\s*([^\s"\']+)\s+(\d+-\d+-\d+)\s+(\d+(?:-\d+-\d+)?)\s+(.*)$/s', $line, $m ) ) {
                continue;
            }
            list( $line, $res, $start, $days, $task ) = $m;
            $task = trim( $task );
            $startParts = explode( '-', $start );
            $start = sprintf( "%04d-%02d-%02d", $startParts[0], $startParts[1], $startParts[2] );
            
            if ( $min === NULL || $start < $min ) { $min = $start; }
            
            if ( strpos( $days, '-' ) ) {
                $endParts = explode( '-', $days );
                $end = sprintf( "%04d-%02d-%02d", $endParts[0], $endParts[1], $endParts[2] );
            } else {
                $endParts = explode( '-', $start );
                $end = date( 'Y-m-d', mktime( 0, 0, 0, $endParts[1], $endParts[2], $endParts[0] ) + $days * 86400 );
            }
            
            if ( $max === NULL || $end > $max ) { $max = $end; }
            if ( !isset( $task_idx[$task] ) ) { $task_idx[$task] = count( $task_idx ); }
            
            $xtics[$start] = true;
            $xtics[$end] = true;
            $data[$res][$start] = [ $task, $end ];
        }
        
        if ( $min === null || $max === null ) {
            return $this->format_error( "Invalid Gantt data." );
        }

        $min_ymd = explode( '-', $min );
        $max_ymd = explode( '-', $max );
        $years = intval( $min_ymd[0] ) != intval( $max_ymd[0] );
        $ytics = [];
        $i = count( $data );
        
        foreach ( $data as $res => &$tasks ) {
            $ytics[] = '"' . addslashes( $res ) . '" ' . ( $i-- );
            ksort( $tasks );
        }
        
        $lines = [
            'set xdata time',
            'set timefmt "%Y-%m-%d"',
            'set format x "%d.%m' . ( $years ? '.%Y' : '' ) . '"',
            'set xrange ["' . $min . '":"' . $max . '"]',
            'set autoscale x',
            'set yrange [0.4:' . count( $data ) . '.6]',
            'set ytics (' . implode( ', ', $ytics ) . ')',
            'set xtics rotate by -90 ("' . implode( '", "', array_keys( $xtics ) ) . '")',
            'set key outside width +2',
            'set grid xtics',
            'set palette model RGB defined (0 1.0 0.8 0.8, 1 1.0 0.8 1.0, 2 0.8 0.8 1.0, 3 0.8 1.0 1.0, 4 0.8 1.0 0.8, 5 1.0 1.0 0.8)',
            'unset colorbox',
        ];
        
        $i = count( $data );
        $j = 1;
        $plot = [];
        
        foreach ( $data as $res => &$tasks ) {
            foreach ( $tasks as $start => $task ) {
                $lines[] = 'set object ' . ( $j++ ) . ' rectangle from "' . $start . '", ' . ( $i - 0.2 ) .
                    ' to "' . $task[1] . '", ' . ( $i + 0.2 ) . ' fillcolor palette frac ' . ( $task_idx[$task[0]] / max( 1, count( $task_idx ) - 1 ) ) . ' fillstyle solid 0.8';
            }
            $i--;
        }
        
        foreach ( $task_idx as $task => $idx ) {
            $plot[] = '-1 title "' . $task . '" with lines linecolor palette frac ' . ( $idx / max( 1, count( $task_idx ) - 1 ) ) . ' linewidth 6';
        }
        
        $lines[] = 'plot ' . implode( ', ', $plot );
        $this->Content = implode( "\n", $lines );
        return $this->plot( $args, false );
    }

    public function plot( $args, $check = true ) {
        $blackList = [
            'cd', 'call', 'exit', 'load', 'pause', 'print',
            'pwd', 'quit', 'replot', 'reread', 'reset', 'save',
            'shell', 'system', 'test', 'update', '!', 'path', 'historysize', 'mouse', 'out', 'term', 'file', '\'/', '\'.', '"'
        ];
        if ( $check ) {
            foreach ( $blackList as $strBlack ) {
                if ( stristr( $this->Content, $strBlack ) !== false ) {
                    return "Sorry, directive {$strBlack} is forbidden!";
                }
            }
        }

        $lines = explode( "\n", $this->Content );
        $datasets = [];
        $src_filtered = "";
        $activedataset = "";
        
        foreach ( $lines as $line ) {
            if ( strpos( $line, "ENDDATASET" ) !== false ) {
                $activedataset = "";
            } elseif ( strpos( $line, "DATASET" ) !== false ) {
                $terms = explode( ' ', trim( $line ) );
                if ( sizeof( $terms ) >= 2 ) {
                    $datasetname = $terms[1];
                }
                $datasetlabel = substr( $line, strlen( "DATASET " . $datasetname ) );
                $datasets[$datasetname] = [
                    'name'  => $datasetname,
                    'label' => $datasetlabel,
                    'src'   => '',
                ];
                $activedataset = $datasetname;
            } else {
                $res = preg_match( '/^\s*(\d[\deEdDqQ\-\.]+)\s*(\d[eEdDqQ\.]*)\s*(#.*)?/', $line );
                if ( $res && $activedataset != "" ) {
                    $datasets[$activedataset]['src'] .= $line . "\n";
                } else {
                    $src_filtered .= $line . "\n";
                }
            }
        }

        foreach ( $datasets as $dataset ) {
            file_put_contents( $dataset['name'] . ".dat", $dataset['src'] );
        }

        $outputpath = $this->Source;
        if ( DIRECTORY_SEPARATOR === '\\' ) {
            $outputpath = str_replace( "\\", "/", $outputpath );
        }
        
        $width = isset( $args['width'] ) && $args['width'] > 0 ? intval( $args['width'] ) : 640;
        $height = isset( $args['height'] ) && $args['height'] > 0 ? intval( $args['height'] ) : $width / 4 * 3;
        $font = isset( $args['font'] ) ? ' font "' . addslashes( $args['font'] ) . '"' : '';
        
        $str = "set encoding utf8\n" .
               "set terminal png size {$width}, {$height}{$font}\n" .
               "set output \"{$outputpath}.png\"\n" .
               "{$src_filtered}";

        file_put_contents( $this->Source . ".plt", $str );
        $cmd = "{$this->gnuplotpath}gnuplot < {$this->Source}.plt 2>{$this->Source}.err";
        wfShellExec( $cmd );
        
        usleep( 100000 );
        $resexists = file_exists( "{$this->Source}.png" );
        
        if ( !$resexists ) {
            $err = file_get_contents( "{$this->Source}.err" );
            return "<div class=\"error\">$resexists<p>{$this->Source}.png<p>$cmd$err</div>";
        }
        
        $str = "set encoding utf8\n" .
               "set terminal svg size {$width}, {$height}{$font}\n" .
               "set output \"{$outputpath}.svg\"\n" .
               "{$src_filtered}";

        file_put_contents( $this->Source . ".plt2", $str );
        wfShellExec( "{$this->gnuplotpath}gnuplot {$this->Source}.plt2 2>{$this->Source}.err2" );
        
        $str = "<img src=\"{$this->URI}{$this->Filename}.png\">";
        if ( file_exists( "{$this->Source}.svg" ) ) {
            $str = "<object type=\"image/svg+xml\" width=\"{$width}\" height=\"{$height}\" data=\"{$this->URI}{$this->Filename}.svg\">\n" .
                   "{$str}\n" .
                   "</object>";
        } else {
            $err = file_get_contents( "{$this->Source}.err" );
            $str .= "<div class=\"error\">$err</div>";
        }
        return $str;
    }

    public function pic_svg() {
        $opts = " --without-gui --export-area-drawing ";
        wfShellExec( "{$this->inkscapepath}inkscape $opts --export-plain-svg={$this->Source}.svg {$this->Source} >{$this->Source}.err 2>&1" );
        wfShellExec( "{$this->inkscapepath}inkscape $opts --export-png={$this->Source}.png {$this->Source} >>{$this->Source}.err 2>&1" );
        
        if ( !file_exists( "{$this->Source}.png" ) ) {
            $err = file_get_contents( "{$this->Source}.err" );
            return "<div class=\"error\">\n$err\n</div>";
        }
        list( $wh ) = self::imagesizes( "{$this->Source}.png" );
        $str = "<a href=\"{$this->URI}{$this->Filename}.svg\"><img $wh src=\"{$this->URI}{$this->Filename}.png\"></a>";
        return str_replace( "\n", "", $str );
    }

    public function umlgraph() {
        copy( $this->Source, $this->Source . ".java" );
        wfShellExec( "umlgraph {$this->Source} png -outputencoding UTF-8 >{$this->Source}.err 2>&1" );
        wfShellExec( "umlgraph {$this->Source} svg -outputencoding UTF-8 >>{$this->Source}.err 2>&1" );
        wfShellExec( "umlgraph {$this->Source} dot -outputencoding UTF-8 >>{$this->Source}.err 2>&1" );
        
        if ( !file_exists( "{$this->Source}.png" ) ) {
            $err = file_get_contents( "{$this->Source}.err" );
            return "<div class=\"error\">\n$err\n</div>";
        }
        return "<a href=\"{$this->URI}{$this->Filename}.svg\"><img src=\"{$this->URI}{$this->Filename}.png\"></a>";
    }

    public function umlsequence() {
        $sequencefilename = dirname( __FILE__ ) . '/sequence.pic';
        $this->Content = ".PS\ncopy \"{$sequencefilename}\";\n{$this->Content}\n.PE\n";
        $this->Content = str_replace( "\r", "", $this->Content );
        file_put_contents( $this->Source, $this->Content );
        
        wfShellExec( "pic2plot -Tsvg {$this->Source} > {$this->Source}.svg 2>{$this->Source}.err" );
        wfShellExec( "inkscape --without-gui --export-area-drawing  --export-plain-svg={$this->Source}.svg {$this->Filename}.svg" );
        wfShellExec( "inkscape --without-gui --export-area-drawing  --export-png={$this->Source}.png {$this->Filename}.svg 2>{$this->Source}.err2" );
        
        if ( !file_exists( "{$this->Source}.png" ) ) {
            $err = file_get_contents( "{$this->Source}.err" );
            return "<div class=\"error\">\n$err\n</div>";
        }
    }

    public function umlet() {
        wfShellExec( "UMLet -action=convert -format=svg -filename={$this->Source}" );
        $opts = " --without-gui --export-area-drawing ";
        wfShellExec( "{$this->inkscapepath}inkscape $opts --export-plain-svg={$this->Source}.svg {$this->Filename}.svg" );
        wfShellExec( "{$this->inkscapepath}inkscape $opts --export-png={$this->Source}.png {$this->Filename}.svg 2>{$this->Source}.err" );
        
        if ( !file_exists( "{$this->Source}.png" ) ) {
            $err = file_get_contents( "{$this->Source}.err" );
            return "<div class=\"error\">\n$err\n</div>";
        }
        return "<a href=\"{$this->URI}{$this->Filename}.svg\"><img src=\"{$this->URI}{$this->Filename}.png\"></a>";
    }

    public function do_tex( $tex ) {
        $blackList = [
            '\catcode', '\def', '\include', '\includeonly', '\input', '\newcommand', '\newenvironment', '\newtheorem',
            '\newfont', '\renewcommand', '\renewenvironment', '\typein', '\typeout', '\write', '\let', '\csname', '\read', '\open'
        ];
        foreach ( $blackList as $strBlack ) {
            if ( stristr( $tex, $strBlack ) !== false ) {
                return "Sorry, directive {$strBlack} is forbidden!";
            }
        }
        file_put_contents( $this->Source . ".tex", $tex );

        if ( !getenv( 'HOME' ) || !is_writeable( getenv( 'HOME' ) ) ) {
            putenv( "HOME={$this->cacheHomeDir}" );
        }
        
        wfShellExec( "{$this->texpath}latex --interaction=nonstopmode {$this->Source}.tex >{$this->Source}.err 2>&1" );
        wfShellExec( "{$this->texpath}dvipng -gamma 1.5 -T tight {$this->Source} >>{$this->Source}.err 2>&1" );
        wfShellExec( "{$this->texpath}dvisvgm --exact -TS1.5 --page=1- --no-fonts --bbox=min --output=\"%f-%p.svg\" {$this->Source}.dvi >>{$this->Source}.err 2>&1" );
        
        $str = "";
        $hash = basename( $this->Filename, ".source" );
        $i = 1;
        
        foreach ( glob( "{$this->BaseDir}/{$hash}*.png" ) as $pngfile ) {
            $pngfile = basename( $pngfile );
            $svgfilename = null;
            
            $svgfilename_ = $this->Filename . '-' . str_pad( $i, 2, "0", STR_PAD_LEFT ) . '.svg';
            if ( file_exists( $this->BaseDir . '/' . $svgfilename_ ) ) {
                $svgfilename = $svgfilename_;
            }

            $svgfilename_ = $this->Filename . '-' . str_pad( $i, 1, "0", STR_PAD_LEFT ) . '.svg';
            if ( file_exists( $this->BaseDir . '/' . $svgfilename_ ) ) {
                $svgfilename = $svgfilename_;
            }

            if ( $svgfilename ) {
                if ( class_exists( 'SVGMetadataExtractor' ) ) {
                    $meta = SVGMetadataExtractor::getMetadata( $this->BaseDir . '/' . $svgfilename );
                    $size = ' width="' . ceil( $meta['width'] ) . '" height="' . ceil( $meta['height'] ) . '"';
                } else {
                    $size = '';
                }
                $str .= "<object $size type=\"image/svg+xml\" style=\"vertical-align: middle\" data=\"{$this->URI}{$svgfilename}\"><img src=\"{$this->URI}{$pngfile}\" /></object>";
            } else {
                $str .= "<img src=\"{$this->URI}{$pngfile}\" />";
            }
            $i++;
        }
        return $str;
    }

    public function latex() {
        $str = "\\documentclass[12pt]{article}\n" .
               "\\usepackage{ucs}\n" .
               "\\usepackage[utf8x]{inputenc}\n" .
               "\\usepackage[english,russian]{babel}\n" .
               "\\usepackage{amssymb,amsmath,amscd}\n" .
               "\\usepackage{color}\n" .
               "\\usepackage{tikz}\n" .
               "\\pagestyle{empty}\n" .
               "\\begin{document}\n" .
               "{$this->Content}\n" .
               "\\end{document}";
        return $this->do_tex( $str );
    }

    public function amsmath() {
        $str = "\\documentclass[12pt]{article}\n" .
               "\\usepackage{ucs}\n" .
               "\\usepackage[utf8x]{inputenc}\n" .
               "\\usepackage[english,russian]{babel}\n" .
               "\\usepackage{amssymb,amsmath,amscd}\n" .
               "\\pagestyle{empty}\n" .
               "\\begin{document}\n" .
               "\\begin{equation*}{$this->Content}\\end{equation*}\n" .
               "\\end{document}";
        return $this->do_tex( $str );
    }

    public function hbarchart() { return $this->barchart( 'hBar' ); }
    public function vbarchart() { return $this->barchart( 'vBar' ); }

    public function barchart( $charttype = 'hBar' ) {
        require_once __DIR__ . "/graphs.inc.php";
        $graph = new \BAR_GRAPH( $charttype );
        $graph->showValues = 1;
        $graph->barWidth = 20;
        $graph->labelSize = 12;
        $graph->absValuesSize = 12;
        $graph->percValuesSize = 12;
        $graph->graphBGColor = '#c0f0ff';
        $graph->barColors = 'Gold';
        $graph->barBGColor = 'Azure';
        $graph->labelColor = 'black';
        $graph->labelBGColor = 'LemonChiffon';
        $graph->absValuesColor = '#000000';
        $graph->absValuesBGColor = 'Cornsilk';
        $graph->graphPadding = 15;
        $graph->graphBorder = '1px solid blue';
        $graph->barBorder = '1px outset #ffea95';

        $lines = explode( "\n", $this->Content );
        $labels = [];
        $values = [];
        
        foreach ( $lines as $line ) {
            $line = preg_replace( "/\s+/", ' ', trim( $line ) );
            $terms = explode( ' ', $line );
            if ( sizeof( $terms ) > 1 ) {
                $value = $terms[sizeof( $terms ) - 1];
                unset( $terms[sizeof( $terms ) - 1] );
                $text = join( ' ', $terms );
                
                $parserOutput = $this->parser->parse( $text, $this->parser->getTitle(), $this->parser->getOptions(), true, false );
                $label = str_replace( "<p>", "", str_replace( "</p>", "", $parserOutput->getText() ) );
                $label = str_replace( ["\r", "\n"], "", $label );
                
                array_push( $labels, $label );
                array_push( $values, $value );
            }
        }
        
        $graph->values = $values;
        $graph->labels = $labels;
        $res = $graph->create();
        $res = str_replace( "<table", "\n<table", $res );
        $res = str_replace( "<td", "\n<td", $res );
        $res = str_replace( "<tr", "\n<tr", $res );
        return $res;
    }
}