<?php
/* Original HTML-GRAPHS (v4.1) by Gerd Tentler */
class BAR_GRAPH {
    public $type = 'hBar';
    public $values;
    public $graphBGColor = '';
    public $graphBorder = '';
    public $graphPadding = 0;
    public $labels;
    public $labelColor = 'black';
    public $labelBGColor = '#C0E0FF';
    public $labelBorder = '2px groove white';
    public $labelFont = 'Arial, Helvetica';
    public $labelSize = 12;
    public $labelSpace = 0;
    public $barWidth = 20;
    public $barLength = 1.0;
    public $barColors;
    public $barBGColor;
    public $barBorder = '2px outset white';
    public $barLevelColors;
    public $showValues = 0;
    public $absValuesColor = 'black';
    public $absValuesBGColor = '#C0E0FF';
    public $absValuesBorder = '2px groove white';
    public $absValuesFont = 'Arial, Helvetica';
    public $absValuesSize = 12;
    public $absValuesPrefix = '';
    public $absValuesSuffix = '';
    public $percValuesColor = 'black';
    public $percValuesFont = 'Arial, Helvetica';
    public $percValuesSize = 12;
    public $percValuesDecimals = 0;
    public $charts = 1;
    public $legend;
    public $legendColor = 'black';
    public $legendBGColor = '#F0F0F0';
    public $legendBorder = '2px groove white';
    public $legendFont = 'Arial, Helvetica';
    public $legendSize = 12;
    public $debug = false;
    public $colors = ['#0000FF', '#FF0000', '#00E000', '#A0A0FF', '#FFA0A0', '#00A000'];
    public $err_type = 'ERROR: Type must be "hBar", "vBar", "pBar", or "fader"';
    public $cssGRAPH = '';
    public $cssBAR = '';
    public $cssBARBG = '';
    public $cssLABEL = '';
    public $cssLABELBG = '';
    public $cssLEGEND = '';
    public $cssLEGENDBG = '';
    public $cssABSVALUES = '';
    public $cssPERCVALUES = '';

    public function __construct($type = '') { if($type) $this->type = $type; }

    public function set_styles() {
        if($this->graphBGColor) $this->cssGRAPH .= 'background-color:' . $this->graphBGColor . ';';
        if($this->graphBorder) $this->cssGRAPH .= 'border:' . $this->graphBorder . ';';
        if($this->barBorder) $this->cssBAR .= 'border:' . $this->barBorder . ';';
        if($this->barBGColor) $this->cssBARBG .= 'background-color:' . $this->barBGColor . ';';
        if($this->labelColor) $this->cssLABEL .= 'color:' . $this->labelColor . ';';
        if($this->labelBGColor) $this->cssLABEL .= 'background-color:' . $this->labelBGColor . ';';
        if($this->labelBorder) $this->cssLABEL .= 'border:' . $this->labelBorder . ';';
        if($this->labelFont) $this->cssLABEL .= 'font-family:' . $this->labelFont . ';';
        if($this->labelSize) $this->cssLABEL .= 'font-size:' . $this->labelSize . 'px;';
        if($this->labelBGColor) $this->cssLABELBG .= 'background-color:' . $this->labelBGColor . ';';
        if($this->legendColor) $this->cssLEGEND .= 'color:' . $this->legendColor . ';';
        if($this->legendFont) $this->cssLEGEND .= 'font-family:' . $this->legendFont . ';';
        if($this->legendSize) $this->cssLEGEND .= 'font-size:' . $this->legendSize . 'px;';
        if($this->legendBGColor) $this->cssLEGENDBG .= 'background-color:' . $this->legendBGColor . ';';
        if($this->legendBorder) $this->cssLEGENDBG .= 'border:' . $this->legendBorder . ';';
        if($this->absValuesColor) $this->cssABSVALUES .= 'color:' . $this->absValuesColor . ';';
        if($this->absValuesBGColor) $this->cssABSVALUES .= 'background-color:' . $this->absValuesBGColor . ';';
        if($this->absValuesBorder) $this->cssABSVALUES .= 'border:' . $this->absValuesBorder . ';';
        if($this->absValuesFont) $this->cssABSVALUES .= 'font-family:' . $this->absValuesFont . ';';
        if($this->absValuesSize) $this->cssABSVALUES .= 'font-size:' . $this->absValuesSize . 'px;';
        if($this->percValuesColor) $this->cssPERCVALUES .= 'color:' . $this->percValuesColor . ';';
        if($this->percValuesFont) $this->cssPERCVALUES .= 'font-family:' . $this->percValuesFont . ';';
        if($this->percValuesSize) $this->cssPERCVALUES .= 'font-size:' . $this->percValuesSize . 'px;';
    }

    public function level_color($value, $color) {
        if($this->barLevelColors) {
            for($i = 0; $i < count($this->barLevelColors); $i += 2) {
                if($i+1 < count($this->barLevelColors)) {
                    if(($this->barLevelColors[$i] > 0 && $value >= $this->barLevelColors[$i]) ||
                       ($this->barLevelColors[$i] < 0 && $value <= $this->barLevelColors[$i])) {
                        $color = $this->barLevelColors[$i+1];
                    }
                }
            }
        }
        return $color;
    }

    public function build_bar($value, $width, $height, $color) {
        $title = $this->absValuesPrefix . $value . $this->absValuesSuffix;
        $bg = preg_match('/\.(jpg|jpeg|jpe|gif|png)$/is', $color) ? 'background' : 'bgcolor';
        $bar = '<table border=0 cellspacing=0 cellpadding=0><tr>';
        $bar .= '<td style="' . $this->cssBAR . '" ' . $bg . '="' . $color . '"';
        $bar .= ($value != '') ? ' title="' . $title . '">' : '>';
        $bar .= '<table border=0 cellspacing=0 cellpadding=0><tr>';
        $bar .= '<td width=' . $width . ' height=' . $height . ' '. $bg . '="' . $color . '"></td>';
        $bar .= '</tr></table>';
        $bar .= '</td></tr></table>';
        return $bar;
    }

    public function build_fader($value, $width, $height, $x, $color) {
        $fader = '<table border=0 cellspacing=0 cellpadding=0><tr>';
        $x -= round($width / 2);
        if($x > 0) $fader .= '<td width=' . $x . '></td>';
        $fader .= '<td>' . $this->build_bar($value, $width, $height, $color) . '</td>';
        $fader .= '</tr></table>';
        return $fader;
    }

    public function build_value($val, $max_dec, $sum = 0, $align = '') {
        $val = number_format((float)$val, $max_dec);
        if($sum) $sum = number_format((float)$sum, $max_dec);
        $value = '<td style="' . $this->cssABSVALUES . '"';
        if($align) $value .= ' align=' . $align;
        $value .= ' nowrap>';
        $value .= '&nbsp;' . $this->absValuesPrefix . $val . $this->absValuesSuffix;
        if($sum) $value .= ' / ' . $this->absValuesPrefix . $sum . $this->absValuesSuffix;
        $value .= '&nbsp;</td>';
        return $value;
    }

    public function build_legend($barColors) {
        $legend = '<table border=0 cellspacing=0 cellpadding=0><tr>';
        $legend .= '<td style="' . $this->cssLEGENDBG . '">';
        $legend .= '<table border=0 cellspacing=4 cellpadding=0>';
        $l = (is_array($this->legend)) ? $this->legend : explode(',', $this->legend);

        for($i = 0; $i < count($barColors); $i++) {
            $legend .= '<tr>';
            $legend .= '<td>' . $this->build_bar('', $this->barWidth, $this->barWidth, $barColors[$i]) . '</td>';
            $legend .= '<td style="' . $this->cssLEGEND . '" nowrap>' . trim($l[$i] ?? '') . '</td>';
            $legend .= '</tr>';
        }
        $legend .= '</table></td></tr></table>';
        return $legend;
    }

    public function create_hBar($value, $percent, $mPerc, $mPerc_neg, $max_neg, $mul, $valSpace, $bColor, $border, $spacer, $spacer_neg) {
        $bar = '<table border=0 cellspacing=0 cellpadding=0 height=100% '. ($this->cssBARBG ? ' style="' . $this->cssBARBG . '"' : '') . '><tr>';

        if($percent < 0) {
            $percent *= -1;
            $bar .= '<td style="' . $this->cssLABELBG . '" height=' . $this->barWidth . ' width=' . round(($mPerc_neg - $percent) * $mul + $valSpace) . ' align=right nowrap>';
            if($this->showValues < 2) $bar .= '<span style="' . $this->cssPERCVALUES . '">' . number_format($percent, $this->percValuesDecimals) . '%</span>';
            $bar .= '&nbsp;</td><td style="' . $this->cssLABELBG . '">';
            $bar .= $this->build_bar($value, round($percent * $mul), $this->barWidth, $bColor);
            $bar .= '</td><td width=' . $spacer . '></td>';
        } else {
            if($max_neg) {
                $bar .= '<td style="' . $this->cssLABELBG . '" width=' . $spacer_neg . '>';
                $bar .= '<table border=0 cellspacing=0 cellpadding=0><tr><td></td></tr></table></td>';
            }
            if($percent) {
                $bar .= '<td '. ($this->cssBARBG ? ' style="' . $this->cssBARBG . '"' : '') .'>';
                $bar .= $this->build_bar($value, round($percent * $mul), $this->barWidth, $bColor);
                $bar .= '</td>';
            } else $bar .= '<td><img width=1 height=' . ($this->barWidth + ($border * 2)) . '></td>';
            
            $bar .= '<td style="' . $this->cssPERCVALUES . '" width=' . round(($mPerc - $percent) * $mul + $valSpace) . ' align=left nowrap>';
            if($this->showValues < 2) $bar .= '&nbsp;' . number_format($percent, $this->percValuesDecimals) . '%';
            $bar .= '&nbsp;</td>';
        }
        $bar .= '</tr></table>';
        return $bar;
    }

    public function create_vBar($value, $percent, $mPerc, $mPerc_neg, $max_neg, $mul, $valSpace, $bColor, $border, $spacer, $spacer_neg) {
        $bar = '<table border=0 cellspacing=0 cellpadding=0 width=100%><tr align=center>';

        if($percent < 0) {
            $percent *= -1;
            $bar .= '<td height=' . $spacer . '></td></tr><tr align=center valign=top><td style="' . $this->cssLABELBG . '">';
            $bar .= $this->build_bar($value, $this->barWidth, round($percent * $mul), $bColor);
            $bar .= '</td></tr><tr align=center valign=top>';
            $bar .= '<td style="' . $this->cssLABELBG . '" height=' . round(($mPerc_neg - $percent) * $mul + $valSpace) . ' nowrap>';
            $bar .= ($this->showValues < 2) ? '<span style="' . $this->cssPERCVALUES . '">' . number_format($percent, $this->percValuesDecimals) . '%</span>' : '&nbsp;';
            $bar .= '</td>';
        } else {
            $bar .= '<td style="' . $this->cssPERCVALUES . '" valign=bottom height=' . round(($mPerc - $percent) * $mul + $valSpace) . ' nowrap>';
            if($this->showValues < 2) $bar .= number_format($percent, $this->percValuesDecimals) . '%';
            $bar .= '</td>';
            if($percent) {
                $bar .= '</tr><tr align=center valign=bottom><td>';
                $bar .= $this->build_bar($value, $this->barWidth, round($percent * $mul), $bColor);
                $bar .= '</td>';
            } else $bar .= '</tr><tr><td><img width=' . ($this->barWidth + ($border * 2)) . ' height=1></td>';
            
            if($max_neg) {
                $bar .= '</tr><tr><td style="' . $this->cssLABELBG . '" height=' . $spacer_neg . '>';
                $bar .= '<table border=0 cellspacing=0 cellpadding=0><tr><td></td></tr></table></td>';
            }
        }
        $bar .= '</tr></table>';
        return $bar;
    }

    public function create() {
        error_reporting(E_WARNING);

        $this->type = strtolower($this->type);
        $d = (is_array($this->values)) ? $this->values : explode(',', $this->values);
        if(is_array($this->labels)) $r = $this->labels;
        else $r = (strlen($this->labels) > 1) ? explode(',', $this->labels) : [];
        
        if($this->barColors) $drc = (is_array($this->barColors)) ? $this->barColors : explode(',', $this->barColors);
        else $drc = [];
        
        $val = $bc = [];
        if($this->barLength < 0.1) $this->barLength = 0.1;
        else if($this->barLength > 2.9) $this->barLength = 2.9;
        $bars = (count($d) > count($r)) ? count($d) : count($r);

        if($this->type == 'pbar' || $this->type == 'fader') {
            if(!$this->barBGColor) $this->barBGColor = $this->labelBGColor;
            if($this->labelBGColor == $this->barBGColor) {
                $this->labelBGColor = '';
                $this->labelBorder = '';
            }
        }

        $this->set_styles();

        $graph = '<table border=0 cellspacing=0 cellpadding=' . $this->graphPadding . '><tr>';
        $graph .= '<td' . ($this->cssGRAPH ? ' style="' . $this->cssGRAPH . '"' : '') . '>';

        if($this->legend && $this->type != 'pbar' && $this->type != 'fader')
            $graph .= '<table border=0 cellspacing=0 cellpadding=0><tr valign=top><td>';

        if($this->charts > 1) {
            $divide = ceil($bars / $this->charts);
            $graph .= '<table border=0 cellspacing=0 cellpadding=6><tr valign=top><td>';
        } else $divide = 0;

        for($i = $sum = $max = $max_neg = $max_dec = $ccnt = $lcnt = $chart = 0; $i < $bars; $i++) {
            if($divide && $i && !($i % $divide)) {
                $lcnt = 0;
                $chart++;
            }
            $drv = explode(';', $d[$i]);

            for($j = $dec = 0; $j < count($drv); $j++) {
                $val[$chart][$lcnt][$j] = $v = trim(str_replace(',', '.', $drv[$j]));

                if($v > $max) $max = $v;
                else if($v < $max_neg) $max_neg = $v;

                if($v < 0) $v *= -1;
                $sum += $v;

                if(strstr($v, '.')) {
                    $dec = strlen(substr($v, strrpos($v, '.') + 1));
                    if($dec > $max_dec) $max_dec = $dec;
                }

                if(!isset($bc[$j])) {
                    if($ccnt >= count($this->colors)) $ccnt = 0;
                    $bc[$j] = (!isset($drc[$j]) || strlen($drc[$j]) < 3) ? $this->colors[$ccnt++] : trim($drc[$j]);
                }
            }
            $lcnt++;
        }

        $border = (int) $this->barBorder;
        $mPerc = $sum ? round($max * 100 / $sum) : 0;
        if($this->type == 'pbar' || $this->type == 'fader') $mul = 2;
        else $mul = $mPerc ? 100 / $mPerc : 1;
        $mul *= $this->barLength;

        if($this->showValues < 2) {
            if($this->type == 'hbar')
                $valSpace = ($this->percValuesDecimals * ($this->percValuesSize / 1.6)) + ($this->percValuesSize * 3.2);
            else $valSpace = $this->percValuesSize * 1.2;
        } else $valSpace = $this->percValuesSize;
        
        $spacer = $maxSize = round($mPerc * $mul + $valSpace + $border * 2);

        if($max_neg) {
            $mPerc_neg = $sum ? round(-$max_neg * 100 / $sum) : 0;
            $spacer_neg = round($mPerc_neg * $mul + $valSpace + $border * 2);
            $maxSize += $spacer_neg;
        }

        for($chart = $lcnt = 0; $chart < count($val); $chart++) {
            $graph .= '<table border=0 cellspacing=2 cellpadding=0>';

            if($this->type == 'hbar') {
                for($i = 0; $i < count($val[$chart]); $i++, $lcnt++) {
                    $label = ($lcnt < count($r)) ? trim($r[$lcnt]) : $lcnt+1;
                    $rowspan = count($val[$chart][$i]);
                    $graph .= '<tr><td style="' . $this->cssLABEL . '"' . (($rowspan > 1) ? ' rowspan=' . $rowspan : '') . ' align=center>';
                    $graph .= '&nbsp;' . $label . '&nbsp;</td>';

                    for($j = 0; $j < count($val[$chart][$i]); $j++) {
                        $percent = $sum ? $val[$chart][$i][$j] * 100 / $sum : 0;
                        $value = number_format((float)$val[$chart][$i][$j], $max_dec);
                        $bColor = $this->level_color($val[$chart][$i][$j], $bc[$j]);

                        if($this->showValues == 1 || $this->showValues == 2)
                            $graph .= $this->build_value($val[$chart][$i][$j], $max_dec, 0, 'right');

                        $graph .= '<td' . ($this->cssBARBG ? ' style="' . $this->cssBARBG . '"' : '') . ' height=100% width=' . $maxSize . '>';
                        $graph .= $this->create_hBar($value, $percent, $mPerc, $mPerc_neg, $max_neg, $mul, $valSpace, $bColor, $border, $spacer, $spacer_neg);
                        $graph .= '</td></tr>';
                        if($j < count($val[$chart][$i]) - 1) $graph .= '<tr>';
                    }
                    if($this->labelSpace && $i < count($val[$chart])-1) $graph .= '<tr><td colspan=3 height=' . $this->labelSpace . '></td></tr>';
                }
            } else if($this->type == 'vbar') {
                $graph .= '<tr align=center valign=bottom>';

                for($i = 0; $i < count($val[$chart]); $i++) {
                    for($j = 0; $j < count($val[$chart][$i]); $j++) {
                        $percent = $sum ? $val[$chart][$i][$j] * 100 / $sum : 0;
                        $value = number_format((float)$val[$chart][$i][$j], $max_dec);
                        $bColor = $this->level_color($val[$chart][$i][$j], $bc[$j]);

                        $graph .= '<td' . ($this->cssBARBG ? ' style="' . $this->cssBARBG . '"' : '') . '>';
                        $graph .= $this->create_vBar($value, $percent, $mPerc, $mPerc_neg, $max_neg, $mul, $valSpace, $bColor, $border, $spacer, $spacer_neg);
                        $graph .= '</td>';
                    }
                    if($this->labelSpace) $graph .= '<td width=' . $this->labelSpace . '></td>';
                }
                if($this->showValues == 1 || $this->showValues == 2) {
                    $graph .= '</tr><tr align=center>';
                    for($i = 0; $i < count($val[$chart]); $i++) {
                        for($j = 0; $j < count($val[$chart][$i]); $j++) {
                            $graph .= $this->build_value($val[$chart][$i][$j], $max_dec);
                        }
                        if($this->labelSpace) $graph .= '<td width=' . $this->labelSpace . '></td>';
                    }
                }
                $graph .= '</tr><tr align=center>';

                for($i = 0; $i < count($val[$chart]); $i++, $lcnt++) {
                    $label = ($lcnt < count($r)) ? trim($r[$lcnt]) : $lcnt+1;
                    $colspan = count($val[$chart][$i]);
                    $graph .= '<td style="' . $this->cssLABEL . '"' . (($colspan > 1) ? ' colspan=' . $colspan : '') . '>';
                    $graph .= '&nbsp;' . $label . '&nbsp;</td>';
                    if($this->labelSpace) $graph .= '<td width=' . $this->labelSpace . '></td>';
                }
                $graph .= '</tr>';
            }
            
            $graph .= '</table>';

            if($chart < $this->charts - 1 && count($val[$chart+1])) {
                $graph .= '</td>';
                if($this->type == 'vbar') $graph .= '</tr><tr valign=top>';
                $graph .= '<td>';
            }
        }

        if($this->charts > 1) $graph .= '</td></tr></table>';

        if($this->legend && $this->type != 'pbar' && $this->type != 'fader') {
            $graph .= '</td><td width=10>&nbsp;</td><td>';
            $graph .= $this->build_legend($bc);
            $graph .= '</td></tr></table>';
        }

        if($this->debug) {
            $graph .= "<br>sum=$sum max=$max max_neg=$max_neg max_dec=$max_dec mPerc=$mPerc mPerc_neg=$mPerc_neg mul=$mul valSpace=$valSpace";
        }

        $graph .= '</td></tr></table>';

        return $graph;
    }
}