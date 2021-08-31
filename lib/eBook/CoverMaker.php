<?php
namespace LivingFlame\eBook;
class CoverMaker {
	const TOP = 0;
	const CENTER = 1;
	const BOTTOM = 2;

	public $img;
	public $w;
	public $h;
	public $text_layer;
	public $margin;
	public $stroke_size;
	public $stroke_color;

	public $shadow_offset;
	public $shadow_color;
    public $default_font_family = 'Noto Sans';
	public $fonts = array(
        'Noto Sans' => array(
            'bold' => DOC_ROOT . 'assets/fonts/NotoSans-Bold.ttf',
            'bold_italic' => DOC_ROOT . 'assets/fonts/NotoSans-BoldItalic.ttf',
            'italic' => DOC_ROOT . 'assets/fonts/NotoSans-Italic.ttf',
            'normal' => DOC_ROOT . 'assets/fonts/NotoSans-Regular.ttf'
        )
    );

	public function __construct($img_width = 400, $img_height = 80, $background = '#fff',$margin = 96) {
        $this->make($img_width,$img_height, $background);
	}

	public function make($img_width = 400, $img_height = 80, $background = '#fff',$margin = 96) {
        $this->img = imagecreatetruecolor($img_width, $img_height);
        $this->w = imagesx($this->img);
        $this->h = imagesy($this->img);
        imagealphablending($this->img, false);
        $bg = $this->hex2rgb($background);
        $bg_locate = imagecolorallocatealpha($this->img, $bg['r'], $bg['b'], $bg['g'], 127);
        imagefilledrectangle($this->img, 0, 0, $this->w, $this->h, $bg_locate);
        imagealphablending($this->img, true);
        $this->margin($margin);
	}

    public function setTextLayer(){
        $this->text_layer = imagecreatetruecolor($this->w, $this->h);
        $layer_color = imagecolorallocatealpha($this->text_layer, 255,255,255, 127);// transparent
        // to get the Background to be transparent you MUST cut OFF the alphablending
        imagealphablending($this->text_layer, false);
        imagefilledrectangle($this->text_layer, 0, 0, $this->w, $this->h, $layer_color);
        // to get the text to be semi-transparent you MUST cut ON the alphablending
        imagealphablending($this->text_layer, true);
    }

    public function makePattern(){
    	$baseR = rand(0, 255);
        $baseG = rand(0, 255);
        $baseB = rand(0, 255);
		$divis = rand(1, 10);;
        $choose = rand(1, 2);
        /*  $divis = rand(1, 100);  */
        for ($i = 0; $i <= floor($this->w / $divis); $i++){
            for ($j = 0; $j <= floor($this->h / $divis); $j++){
                $val = floor(100 * (rand(0, 100) / 100));
                $r = $baseR - $val;
                $g = $baseG - $val;
                $b = $baseB - $val;

				if($r > 255) { $r = 255; }
				if($g > 255) { $g = 255; }
				if($b > 255) { $b = 255; }
				if($r < 0) { $r = 0; }
				if($g < 0) { $g = 0; }
				if($b < 0) { $b = 0; }
				
                $color = imagecolorallocate($this->img, $r, $g, $b); 
                
                switch($choose){
                	case 1: imagefilledrectangle($this->img, $i * $divis, $j * $divis, (($i + 1) * $divis), (($j + 1) * $divis), $color);
                		break;
                	case 2: imagefilledellipse($this->img, $i * $divis, $j * $divis, $divis, $divis, $color);
                		break;
                }
            }
        }
    }

    public function margin($margin){
        $pp = explode(' ', $margin);
        if (isset($pp[3])){
            $this->margin = array(
                (int) $pp[0], 
                (int) $pp[1], 
                (int) $pp[2], 
                (int) $pp[3]
            );
        }else if (isset($pp[2])){
            $this->margin = array(
                (int) $pp[0],
                (int) $pp[1],
                (int) $pp[2],
                (int) $pp[1]
            );
        }else if (isset($pp[1])){
            $this->margin = array(
                (int) $pp[0],
                (int) $pp[1],
                (int) $pp[0],
                (int) $pp[1]
            );
        }else{
            $this->margin = array_fill(0, 4, (int) $pp[0]);
        }
    }

    public function hex2rgb( $colour ) {
        if ( $colour[0] == '#' ) {
            $colour = substr( $colour, 1 );
        }
        if ( strlen( $colour ) == 6 ) {
            list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
        } elseif ( strlen( $colour ) == 3 ) {
            list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
        } else {
            return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return array( 'r' => $r, 'g' => $g, 'b' => $b );
    }

    public function drawBorder($thickness = 1, $color = '#000000', $x=0, $y=0) 
    {
        $c = $this->hex2rgb($color);
        $border_color = imagecolorallocatealpha($this->img, $c['r'], $c['g'], $c['b'], 0);
        $x1 = $x; 
        $y1 = $y; 
        $x2 = $this->w - ($x); 
        $y2 = $this->h - ($y); 

        for($i = 0; $i < $thickness; $i++) 
        { 
            imagerectangle($this->img, $x1++, $y1++, $x2--, $y2--, $border_color); 
            imagealphablending($this->img, true);
        } 
    }
    public function shadow($shadow_offset,$color = '#ffffff'){
        $this->shadow_offset = $shadow_offset;
        $this->shadow_color = $color;

    }
    public function stroke($stroke_size,$color = '#ffffff'){
        $this->stroke_size = $stroke_size;
        $this->stroke_color = $color;
    }
    public function drawText($text, $font, $fontSize, $text_color, $line_height, $text_decoration, $angle = 0, $x_offset = 0, $y_offset = 0, $pos = self::TOP, $add_line = false){
        
        //break lines
        $splitText = explode ( "\n" , $text );
        $lines = count($splitText);
        $multiplier = 1;
        $space = $this->margin[0];
        $uHeight = abs(round($fontSize/12));
        if($pos == 2){
            $multiplier = $lines;
            $space = $this->margin[3];
        }
        $textHeight = 0;
        $longest = 0;
        $tc = $this->hex2rgb($text_color);
        $color = imagecolorallocatealpha($this->text_layer, $tc['r'], $tc['b'], $tc['g'], 0);
        
        
        
        foreach($splitText as $txt){
            $textBox = imagettfbbox($fontSize,$angle,$font,$txt);
            $textWidth = abs(max($textBox[2], $textBox[4]));
            if($longest < $textWidth){
                $longest = $textWidth;
            }
            
            $textHeight = abs(max($textBox[5], $textBox[7]));
            $length = max($textBox[2], $textBox[4]);
            $x = ($this->w - $textWidth)/2;
            if($pos == 0){
                $y = $space + ($textHeight * $line_height) * $multiplier;
                $multiplier++;
            } elseif($pos == 2){
                $y = $this->h - ($space + ($textHeight * $line_height) * $multiplier);
                $multiplier--;
            }

            if($this->stroke_size){
                $stroke_layer = imagecreatetruecolor($this->w, $this->h);
                // to get the Background to be transparent you MUST cut OFF the alphablending
                imagealphablending($stroke_layer, false);
                $stroke_bg = imagecolorallocatealpha($stroke_layer, 255,255,255, 127);// transparent
                imagefilledrectangle($stroke_layer, 0, 0, $this->w, $this->h, $stroke_bg);
                // to get the text to be semi-transparent you MUST cut ON the alphablending
                imagealphablending($stroke_layer, true);
                $c = $this->hex2rgb($this->stroke_color);
                $stroke_color = imagecolorallocatealpha($stroke_layer, $c['r'], $c['g'], $c['b'], 0);
                

                for($c1 = ($x-abs($this->stroke_size)); $c1 <= ($x+abs($this->stroke_size)); $c1++){
                    for($c2 = ($y-abs($this->stroke_size)); $c2 <= ($y+abs($this->stroke_size)); $c2++){
                        $bg = imagettftext($stroke_layer, $fontSize, $angle, $c1, $c2, $stroke_color, $font, $txt);
                    }
                }
                //imagecopy($stroke_layer, $this->text_layer, 0, 0, 0, 0, $this->w, $this->h);
                imagecopy($this->img, $stroke_layer, 0, 0, 0, 0, $this->w, $this->h);
                imagedestroy($stroke_layer);
            }
            if($this->shadow_offset){ 
                $drop_shadow = imagecreatetruecolor($this->w, $this->h);
                // to get the Background to be transparent you MUST cut OFF the alphablending
                imagealphablending($drop_shadow, false);
                $shadow_bg = imagecolorallocatealpha($drop_shadow, 255,255,255, 127);// transparent
                imagefilledrectangle($drop_shadow, 0, 0, $this->w, $this->h, $shadow_bg);
                // to get the text to be semi-transparent you MUST cut ON the alphablending
                imagealphablending($drop_shadow, true);
                
                $c = $this->hex2rgb($this->shadow_color);
                $shadow_color = imagecolorallocatealpha($drop_shadow, $c['r'], $c['g'], $c['b'], 70);
                imagettftext($drop_shadow, $fontSize, $angle, $x+$this->shadow_offset - 2, $y+$this->shadow_offset - 2, $shadow_color, $font, $txt);
                imagettftext($drop_shadow, $fontSize, $angle, $x+$this->shadow_offset - 1, $y+$this->shadow_offset - 1, $shadow_color, $font, $txt);
                imagettftext($drop_shadow, $fontSize, $angle, $x+$this->shadow_offset - 3, $y+$this->shadow_offset - 3, $shadow_color, $font, $txt);
                
                //imagefilter($drop_shadow, IMG_FILTER_GAUSSIAN_BLUR);
                //imagefilter($drop_shadow, IMG_FILTER_GAUSSIAN_BLUR);
                //imagefilter($drop_shadow, IMG_FILTER_GAUSSIAN_BLUR);

                //imagecopy($drop_shadow, $this->text_layer, 0, 0, 0, 0, $this->w, $this->h);
                imagecopy($this->img, $drop_shadow, 0, 0, 0, 0, $this->w, $this->h);
                imagedestroy($drop_shadow);
            }
            //add the text
            imagettftext($this->text_layer, $fontSize, $angle, $x + $x_offset, $y + $y_offset, $color, $font, $txt);
            switch ($text_decoration) {
                case "underline":
                    imagefilledrectangle($this->text_layer, $x + $x_offset, $y+$uHeight + $y_offset, ($x + $textWidth)  + $x_offset, (($y+$uHeight) + $uHeight)  + $y_offset, $color);
                break;
                case "overline":
                    imagefilledrectangle($this->text_layer, $x + $x_offset, $y - abs($textHeight/2) + $uHeight  + $y_offset, ($x + $textWidth) + $x_offset, (($y - abs($textHeight/2) + $uHeight) + $uHeight)  + $y_offset, $color);
                break;
                case "line-through":
                    imagefilledrectangle($this->text_layer, $x + $x_offset, ( $y - $textHeight ) - $uHeight  + $y_offset, ($x + $textWidth) + $x_offset, (( $y - $textHeight  - $uHeight ) + $uHeight)  + $y_offset, $color);
                break;
            }
        }
        
        if($add_line){
            $x = (imagesx($this->text_layer) - $longest)/2;
            if($pos == 0){
                $y = $space + ($textHeight * $line_height) * $multiplier + $fontSize;
            } elseif($pos == 2){
                $y = $this->h - $space + $fontSize;
            }

            imagefilledrectangle($this->text_layer, $x + $x_offset, ( $y - $textHeight ) - $uHeight + $y_offset, ($x + $longest)  + $x_offset, (( $y - $textHeight  - $uHeight ) + $uHeight) + $y_offset, $color);
        }
        imagecopy($this->img, $this->text_layer, 0, 0, 0, 0, $this->w, $this->h);
    }

	public function addText ($text, $font_info = array(), $separate_line_after_chars=40, $shadow_color = '#808080', $pos = self::TOP, $add_line = false) {
        if(!is_resource($this->text_layer)){ 
            $this->setTextLayer();
        }
        $text_info =  array_merge(array(
            'color' => '#000000',
            'font-size' => 20,
            'font-family' => null,
            'font-weight' => "normal",
            'font-style' => null,
            'line-height' => 1,
            'text-decoration' => 'none',
        ),$font_info);
        
        $font_family = (!empty($text_info['font-family']) && isset($this->fonts[$text_info['font-family']])) ? $text_info['font-family'] : $this->default_font_family;
        $current_font = $this->fonts[$font_family];
        $font_weight = ( isset($current_font[ $text_info['font-weight'] ]) ) ? $text_info['font-weight'] : 'normal';
        $font = $current_font[$font_weight];
        $font_style = $text_info['font-style'];
        if(!is_null($text_info['font-style'])){
            $weight = ($font_weight !== 'normal') ? $font_weight . '_' : '';
            if(( isset($current_font[ $weight . $font_style ]) )){
                $font = $current_font[ $weight . $font_style ];
            } else {
                $font = $current_font[ $font_style ];
            }
        }

        $text_color = $text_info['color'];
        $fontSize = $text_info['font-size'];
        $line_height = $text_info['line-height'];
        $text_decoration = $text_info['text-decoration'];
        
        $x=explode(" ", $text);
        $final='';
        $len = '';

        foreach($x as $key => $value){
            $returnes='';
            if(empty($final)){
                $final .= $value . ' ';
                $len .= $value . ' '; 
            } else {
                if(mb_strlen($len . $value,'utf-8') > $separate_line_after_chars){ 
                    $final = trim($final) . "\n" . $value . ' ';
                    $len = $value . ' '; 
                } else {
                    $final .= $value . ' ';
                    $len .= $value . ' '; 
                }
            }
        }

        $text=trim($final);
        $angle = 0;

        //text
        $this->drawText($text, $font, $fontSize, $text_color, $line_height, $text_decoration, $angle, 0, 0, $pos, $add_line);
        
	}

    public function output($file_name = NULL){
        $file_parts = pathinfo($file_name);
        if(empty($file_parts['extension'])){
            $file_name . '.' . $type;
        }
        header('Content-type: image/png');
        imagesavealpha($this->img, true);
        imagepng($this->img, $file_name);
        if(!is_resource($this->text_layer)){ 
            imagedestroy($this->text_layer);
        }
        imagedestroy($this->img);
        if($file_name){
            echo file_get_contents($file_name);
        }
    }
    public function __destruct() {
        if(!is_resource($this->text_layer)){ 
            imagedestroy($this->text_layer);
        }
        if(!is_resource($this->img)){ 
            imagedestroy($this->img);
        }
    }
}
?>