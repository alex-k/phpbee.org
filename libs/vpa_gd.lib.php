<?php

class vpa_gd_layer {
	var $layer;
	var $w;
	var $h;
	
	function vpa_gd_layer($w=0,$h=0)
	{
		$this->w=$w;
		$this->h=$h;
		$this->layer=imagecreatetruecolor($w,$h);
	}
	
	function fill() {}
}

class vpa_gd {
	var $filename;
	var $old_img;
	var $new_img;
	var $old_width;
	var $old_height;
	var $old_mime;
	var $new_width;
	var $new_height;
	var $bg_color;
	var $bg_r=255;
	var $bg_g=255;
	var $bg_b=255;
	var $borders;
	
	function vpa_gd($filename='',$path=true)
	{
		if (!empty($filename) && $path==true)
		{
			$this->filename=$filename;
			$info=getimagesize($filename);
			$this->old_width=$info[0];
			$this->old_height=$info[1];
			$this->old_mime=$info['mime'];
			$this->old_img=imagecreatefromstring(file_get_contents($this->filename));
		}
		elseif ($path==false)
		{
			$this->filename=$filename;
			$this->old_img=imagecreatefromstring($this->filename);
			$this->old_width=imagesx ($this->old_img);
			$this->old_height=imagesy ($this->old_img);
			$this->old_mime='image/jpeg';
		}
		$this->borders=array();
	}
	
	function set_bg_color($r,$g,$b)
	{
		$this->bg_r=$r;
		$this->bg_g=$g;
		$this->bg_b=$b;
	}
	
	function add_border($width,$color)
	{
		$indx=count($this->borders);
		$this->borders[$indx]['width']=$width;
		$this->borders[$indx]['color']=$color;
	}
	
	/**
	* самый важный параметр:
	* string type - указывает, как именно он приведет имеющеюсюя картинку к картине данного размера
	* use_width - фиксируем ширину, высота определяется пропорционально ширине
	* use_height - фиксируем высоту, ширина определяется пропорционально высоте
	* use_box - вписываем картинку в прямоугольник заданных размеров, уменьшая по самой большой координате
	* use_fields - вписываем картинку в прямоугольник заданных размеров, но добавляем поля, чтобы картинка была четко той ширины и высоты, которой нам надо
	**/
	function resize($width,$height,$type)
	{
		$this->new_width=$width;
		$this->new_height=$height;
		switch($type)
		{
			case 'use_width':
				$this->make_width();
			break;
			case 'use_height':
				$this->make_height();
			break;
			case 'use_box':
				$this->make_box();
			break;
			case 'use_fields':
				$this->make_fields();
			break;
			case 'use_crop':
				$this->make_crop();
			break;
		}
	}
	
	function make_width()
	{
		$k=$this->old_width/$this->old_height;
		$this->new_width=intval($this->new_width);
		$this->new_height=intval(ceil($this->new_width/$k));
		$this->new_img=imagecreatetruecolor ($this->new_width, $this->new_height);
		ImageCopyResampled($this->new_img, $this->old_img, 0, 0, 0, 0, $this->new_width, $this->new_height, $this->old_width, $this->old_height);
	}
	
	function make_height()
	{
		$k=$this->old_width/$this->old_height;
		$k1=$this->new_width/$this->new_height;
		$this->new_width=intval(round($this->new_height*$k));
		$this->new_height=intval($this->new_height);
		$this->new_img=imagecreatetruecolor ($this->new_width, $this->new_height);
		ImageCopyResampled($this->new_img, $this->old_img, 0, 0, 0, 0, $this->new_width, $this->new_height, $this->old_width, $this->old_height);
		//$this->resize();
	}
	
	function make_box()
	{
		$k=$this->old_width/$this->old_height;
		$k1=$this->new_width/$this->new_height;
		
		if ($k>=$k1)
		{
			$this->new_width=intval($this->new_width);
			$this->new_height=intval(ceil($this->new_width/$k));
		}
		else
		{
			$this->new_height=intval($this->new_height);
			$this->new_width=intval(ceil($this->new_height*$k));
		}
		$this->new_img=imagecreatetruecolor ($this->new_width, $this->new_height);
		ImageCopyResampled($this->new_img, $this->old_img, 0, 0, 0, 0, $this->new_width, $this->new_height, $this->old_width, $this->old_height);
	}
	
	function make_crop()
	{
		$k=$this->old_width/$this->old_height;
		$k1=$this->new_width/$this->new_height;
		
		if ($k<=$k1)
		{
			$nw=intval($this->new_width);
			$nh=intval(ceil($this->new_width/$k));
		}
		else
		{
			$nh=intval($this->new_height);
			$nw=intval(ceil($this->new_height*$k));
		}
		$this->new_img=imagecreatetruecolor ($this->new_width, $this->new_height);
		ImageCopyResampled($this->new_img, $this->old_img, 0, 0, 0, 0, $nw, $nh, $this->old_width, $this->old_height);
	}
	
	function crop ($x,$y,$w,$h) {
		if (!empty($this->new_img)) {
			$this->old_img=$this->new_img;
			$this->old_width=$this->new_width;
			$this->old_height=$this->new_height;
		}
		$this->new_img=imagecreatetruecolor ($w, $h);
		ImageCopyResampled($this->new_img, $this->old_img, 0, 0, $x, $y, $w, $h, $w, $h);
	}
	
	function make_fields()
	{
		$r_w=$this->new_width;
		$r_h=$this->new_height;
		$k=$this->old_width/$this->old_height;
		$k1=$this->new_width/$this->new_height;
		if ($k>$k1)
		{
			$this->new_width=intval($this->new_width);
			$this->new_height=intval(ceil($this->new_width/$k));
			$offset_x=0;
			$offset_y=intval(ceil(($r_h-$this->new_height)/2));
		}
		else
		{
			$this->new_height=intval($this->new_height);
			$this->new_width=intval(ceil($this->new_height*$k));
			$offset_x=intval(ceil(($r_w-$this->new_width)/2));
			$offset_y=0;
		}
		$this->new_img=imagecreatetruecolor ($r_w, $r_h);
		$this->bg_color=imagecolorallocate($this->new_img, $this->bg_r, $this->bg_g, $this->bg_b);
		imagefill($this->new_img, 0, 0, $this->bg_color);
		ImageCopyResampled($this->new_img, $this->old_img, $offset_x, $offset_y, 0, 0, $this->new_width, $this->new_height, $this->old_width, $this->old_height);
		$this->new_width=$r_w;
		$this->new_height=$r_h;
	}
	
	function make_borders()
	{
		$add=0;
		foreach ($this->borders as $i => $key)
		{
			$add+=$key['width'];
		}
		$new_img=imagecreatetruecolor ($this->new_width+$add*2, $this->new_height+$add*2);
	
		$nw=$this->new_width+2*$add-1;
		$nh=$this->new_height+2*$add-1;
		$offset=0;
		foreach ($this->borders as $i => $key)
		{
			$c=imagecolorallocate($new_img,$key['color'][0],$key['color'][1],$key['color'][2]);
			for ($j=0;$j<$key['width'];$j++)
			{
				imagerectangle ($new_img,$offset,$offset,$nw-$offset,$nh-$offset,$c);
				$offset+=1;
			}
		}
		imagecopymerge ($new_img,$this->new_img,$offset,$offset,0,0,$this->new_width,$this->new_height,100);
		$this->new_img=$new_img;
		$this->new_width=$nw+1;
		$this->new_height=$nh+1;
	}
	
	/**
	* подкладывает под картинку тень (прямоугольник заданного цвета)
	* int width - толщина тени
	* array color - цвет тени (с альфа каналом)
	**/
	function make_shadow($width,$color)
	{
		$img=imagecreatetruecolor ($this->new_width+$width, $this->new_height+$width);
		$bg=imagecolorallocatealpha($img,255,255,255,127);
		$c=imagecolorallocatealpha($img,$color[0],$color[1],$color[2],$color[3]);
		imagealphablending($img, false);
		imagesavealpha($img, true);
		$nw=$this->new_width+2*$width;
		$nh=$this->new_height+2*$width;
		imagefilledrectangle ($img,$width,$width,$nw,$nh,$c);
		$offset=0;
		for ($i=0;$i<$width;$i++)
		{
			imageline ($img,$i,0,$i,$this->new_height+$width,$bg);
			imageline ($img,0,$i,$this->new_width+$width,$i,$bg);
		}
		imagecopy($img, $this->new_img, 0, 0, 0, 0, $this->new_width, $this->new_height);
		$this->new_img=$img;
		$this->new_width=$nw;
		$this->new_height=$nh;
	}
	
	
	function make_corner($pos,$r)
	{
		$img=imagecreatetruecolor ($this->new_width, $this->new_height);
		$bgc=array(255,255,255,127);
		$bg=imagecolorallocatealpha($img,$bgc[0],$bgc[1],$bgc[2],$bgc[3]);
		imagealphablending($img, false);
		imagesavealpha($img, true);
		imagecopy($img, $this->new_img, 0, 0, 0, 0, $this->new_width, $this->new_height);
		if ($pos==0)
		{
			$x=$r;
			$y=$r;
			for ($i=0;$i<=$r;$i++)
			{
				for ($j=0;$j<=$r;$j++)
				{
					$lx=($x-$i);
					$ly=($y-$j);
					$l=sqrt($lx*$lx+$ly*$ly);
					//printf("x:%d y:%d  L:%.1f fl:%d<br>",$i,$j,$l,floor($l));
					if ($l>$r)
					{
						imagesetpixel($img, $i, $j, $bg);
					}
					elseif (floor($l+0.5)==$r)
					{
						$c =imagecolorsforindex($img,imagecolorat($img, $i, $j));
						$a=ceil(64*$l/$r);
						$rc=imagecolorallocatealpha($img,$c['red'],$c['green'],$c['blue'],$a);
						imagesetpixel($img, $i, $j, $rc);
					}
				}
			}
		}
		if ($pos==1)
		{
			$x=$r;
			$y=$r;
			for ($i=0;$i<=$r;$i++)
			{
				for ($j=0;$j<=$r;$j++)
				{
					$lx=($x-$i);
					$ly=($y-$j);
					$l=sqrt($lx*$lx+$ly*$ly);
					if ($l>$r)
					{
						imagesetpixel($img, $this->new_width-$i-1, $j, $bg);
					}
					elseif (floor($l+0.5)==$r)
					{
						$c =imagecolorsforindex($img,imagecolorat($img, $i, $j));
						$a=ceil(64*$l/$r);
						$rc=imagecolorallocatealpha($img,$c['red'],$c['green'],$c['blue'],$a);
						imagesetpixel($img, $this->new_width-$i-1, $j, $rc);
					}
				}
			}
		}
		if ($pos==2)
		{
			$x=$r;
			$y=$r;
			for ($i=0;$i<=$r;$i++)
			{
				for ($j=0;$j<=$r;$j++)
				{
					$lx=($x-$i);
					$ly=($y-$j);
					$l=sqrt($lx*$lx+$ly*$ly);
					if ($l>$r)
					{
						imagesetpixel($img, $this->new_width-$i-1, $this->new_height-$j-1, $bg);
					}
					elseif (floor($l+0.5)==$r)
					{
						$c =imagecolorsforindex($img,imagecolorat($img, $i, $j));
						$a=ceil(64*$l/$r);
						$rc=imagecolorallocatealpha($img,$c['red'],$c['green'],$c['blue'],$a);
						imagesetpixel($img, $this->new_width-$i-1, $this->new_height-$j-1, $rc);
					}
				}
			}
		}
		if ($pos==3)
		{
			$x=$r;
			$y=$r;
			for ($i=0;$i<=$r;$i++)
			{
				for ($j=0;$j<=$r;$j++)
				{
					$lx=($x-$i);
					$ly=($y-$j);
					$l=sqrt($lx*$lx+$ly*$ly);
					if ($l>$r)
					{
						imagesetpixel($img, $i, $this->new_height-$j-1, $bg);
					}
					elseif (floor($l+0.5)==$r)
					{
						$c =imagecolorsforindex($img,imagecolorat($img, $i, $j));
						$a=ceil(64*$l/$r);
						$rc=imagecolorallocatealpha($img,$c['red'],$c['green'],$c['blue'],$a);
						imagesetpixel($img, $i, $this->new_height-$j-1, $rc);
					}
				}
			}
		}

		$this->new_img=$img;
	}
	
	/**
	* Функция реализующая эффект мокрого пола
	* array color - массив RGB цвета фона, в который будет уводиться градиент
	* float height_p - коэфициент увеличения картинки по высоте (то есть 1 - оригинальный размер - пол не делается, >1 - конечный размер будет - исходный * height_p)
	* int alpha_d - число, на которое делим PI (Sin(pi/alpha_d)*127) - если =2 то маскимальная альфа будет - Sin(pi/2)*127 - то есть максимум.
	* int distance - расстояние между картинкой и ее отражением
	* int compress - во сколько раз сжимать картинку в отражении
	**/
	function wet_floor($color,$height_p=1.3,$alpha_d=2,$distance=0,$compress=3)
	{
		$img=imagecreatetruecolor($this->new_width,$this->new_height*$height_p+$distance);
		imagealphablending($this->new_img, false);
		imagesavealpha($this->new_img, true);
		$c=imagecolorallocate($img, $color[0],$color[1],$color[2]);
		imagefill($img, 0, 0, $c);
		imagecopy($img, $this->new_img, 0, 0, 0, 0, $this->new_width, $this->new_height);
		$pi2=pi()/$alpha_d;
		$mh=ceil($this->new_height*($height_p-1));
		$h=$this->new_height-$this->new_height*($height_p-1)*$compress;
		for ($i=0;$i<$mh;$i++)
		{
			$alpha=ceil((1-sin($i/$mh*$pi2))*127);
			for ($j=0;$j<$this->new_width;$j++)
			{
				$rgba = imagecolorat($img, $j, $i*$compress+$h);
				$rgba = imagecolorsforindex($img, $rgba);
				$rgba = imagecolorallocatealpha($img, $rgba['red'], $rgba['green'], $rgba['blue'], 0);
				imagesetpixel($img, $j, $this->new_height*$height_p+$distance-$i, $rgba);
				$rgba = imagecolorallocatealpha($img, $color[0],$color[1],$color[2], 127-$alpha);
				imagesetpixel($img, $j, $this->new_height*$height_p+$distance-$i, $rgba);
			}
		}
		$img=$this->filter9($img,1,0,$this->new_height,$this->new_width,$mh+$distance-1,$color);
		$this->new_img=$img;
	}
	
	/**
	* Накладывает на картинку градиент
	* int mode - тип градиента
	* int degrees - угол поворота градиента в градусах
	* array4 sc - начальный цвет  (red,green,blue,alpha)
	* array4 ec - конечный цвет  (red,green,blue,alpha)
	* int x - x-координата заливаемой области
	* int y - y-координата заливаемой области
	* int w - ширина заливаемой области
	* int h - высота заливаемой области
	**/
	function gradient($mode,$degrees,$sc,$ec,$x,$y,$w,$h)
	{
		$img=imagecreatetruecolor($this->new_width,$this->new_height);
		imagealphablending($this->new_img, false);
		imagesavealpha($this->new_img, true);
		imagecopy($img, $this->new_img, 0, 0, 0, 0, $this->new_width, $this->new_height);
		$pi2=pi()/2;
		for ($i=$x;$i<$x+$w;$i++)
		{
			for ($j=$y;$j<$y+$h;$j++)
			{
				$sin=sin($j/$h*$pi2);
				$alpha=$sc[3]+ceil($sin*($ec[3]-$sc[3]));
				$red=$sc[0]+ceil($sin*($ec[0]-$sc[0]));
				$green=$sc[1]+ceil($sin*($ec[1]-$sc[1]));
				$blue=$sc[2]+ceil($sin*($ec[2]-$sc[2]));
				$rgba = imagecolorat($img, $i, $j);
				$rgba = imagecolorsforindex($img, $rgba);
				//imagesetpixel($img, $i, $j, $rgba);
				$rgba = imagecolorallocatealpha($img, $red, $green, $blue, $alpha);
				imagesetpixel($img, $i, $j, $rgba);
			}
		}
		//imagecopymerge ($this->new_img,$img,$x,$y,0,0,$w,$h,100);
		$this->new_img=$img;
	}
	
	function new_image($w,$h)
	{
		$this->new_width=$w;
		$this->new_height=$h;
		$this->new_img=imagecreatetruecolor($w,$h);
	}
	
	function fill($x,$y,$color)
	{
		imagesavealpha($this->new_img, true);
		$color[3]=!isset($color[3]) ? 0 : $color[3];
		$c=imagecolorallocatealpha($this->new_img, $color[0],$color[1],$color[2],$color[3]);
		imagefill($this->new_img, $x, $y, $c);
	}
	
	function text($size,$angle, $x, $y, $color, $font_file, $text)
	{
		$color[3]=!isset($color[3]) ? 0 : $color[3];
		$c = imagecolorallocatealpha($this->new_img, $color[0],  $color[1], $color[2],  $color[3]);
		//$c = imagecolorallocate($this->new_img, $color[0],  $color[1], $color[2]);
		imagefttext($this->new_img,$size, $angle, $x,$y,$c,$font_file,$text);
		//exit();
	}
	
	function set_border($filename)
	{
		$img=imagecreatefromstring(file_get_contents($filename));
		imagecopymerge ($this->new_img,$img,0,0,0,0,$this->new_width,$this->new_height,100);
	}
	
	/**
	* читаем ман по imagefilter (нужен пятый пых)
	**/
	function filter($filtertype,$arg1=null)
	{
		imagefilter ($this->new_img,$filtertype,$arg1);
	}
	
	function filter5($img,$mode,$x,$y,$w,$h,$color)
	{
		$color[3]=isset($color[3]) ? $color[3] : 0;
		$cl=array('red'=>$color[0],'green'=>$color[1],'blue'=>$color[2]);

		for ($i=$x;$i<$x+$w;$i++)
		{
			for ($j=$y;$j<$y+$h;$j++)
			{
				$c1 = imagecolorsforindex($img,imagecolorat($img, $i, $j));
				$c2 =($i+$mode<$x+$w) ? imagecolorsforindex($img,imagecolorat($img, $i+$mode, $j)) : $cl;
				$c3 =($j+$mode<$y+$h) ? imagecolorsforindex($img,imagecolorat($img, $i, $j+$mode)) : $cl;
				$c4 =($j-$mode>=$y) ? imagecolorsforindex($img,imagecolorat($img, $i, $j-$mode)) : $cl;
				$c5 =($i-$mode>=$x) ? imagecolorsforindex($img,imagecolorat($img, $mode, $j)) : $cl;
				
				$cr=ceil(($c1['red']+$c2['red']+$c3['red']+$c4['red']+$c5['red'])/5);
				$cg=ceil(($c1['green']+$c2['green']+$c3['green']+$c4['green']+$c5['green'])/5);
				$cb=ceil(($c1['blue']+$c2['blue']+$c3['blue']+$c4['blue']+$c5['blue'])/5);
				//$c=imagecolorallocatealpha($img, $cr,$cg,$cb,$color[3]);
				$c=imagecolorallocate($img, $cr,$cg,$cb);
				imagesetpixel($img, $i, $j, $c);
			}
		}
		return $img;
	}
	
	function filter9($img,$mode,$x,$y,$w,$h,$color)
	{
		$cl=array('red'=>$color[0],'green'=>$color[1],'blue'=>$color[2]);
		for ($i=$x;$i<$x+$w;$i++)
		{
			for ($j=$y;$j<$y+$h;$j++)
			{
				$c1 = imagecolorsforindex($img,imagecolorat($img, $i, $j));
				$c2 =($i+$mode<$x+$w) ? imagecolorsforindex($img,imagecolorat($img, $i+$mode, $j)) : $cl;
				$c3 =($j+$mode<$y+$h) ? imagecolorsforindex($img,imagecolorat($img, $i, $j+$mode)) : $cl;
				$c4 =($j-$mode>=$y) ? imagecolorsforindex($img,imagecolorat($img, $i, $j-$mode)) : $cl;
				$c5 =($i-$mode>=$x) ? imagecolorsforindex($img,imagecolorat($img, $mode, $j)) : $cl;
				$c6 =($j+$mode<$y+$h && $i+$mode<$x+$w) ? imagecolorsforindex($img,imagecolorat($img, $i+$mode, $j+$mode)) : $cl;
				$c7 =($j+$mode<$y+$h && $i-$mode>=$x) ? imagecolorsforindex($img,imagecolorat($img, $i-$mode, $j+$mode)) : $cl;
				$c8 =($j-$mode>=$y && $i+$mode<$x+$w) ? imagecolorsforindex($img,imagecolorat($img, $i+$mode, $j-$mode)) : $cl;
				$c9 =($j-$mode>=$y && $i-$mode>=$x) ? imagecolorsforindex($img,imagecolorat($img, $i-$mode, $j-$mode)) : $cl;
				
				$cr=ceil(($c1['red']+$c2['red']+$c3['red']+$c4['red']+$c5['red']+$c6['red']+$c7['red']+$c8['red']+$c9['red'])/9);
				$cg=ceil(($c1['green']+$c2['green']+$c3['green']+$c4['green']+$c5['green']+$c6['green']+$c7['green']+$c8['green']+$c9['green'])/9);
				$cb=ceil(($c1['blue']+$c2['blue']+$c3['blue']+$c4['blue']+$c5['blue']+$c6['blue']+$c7['blue']+$c8['blue']+$c9['blue'])/9);
				$c=imagecolorallocate($img, $cr,$cg,$cb);
				imagesetpixel($img, $i, $j, $c);
			}
		}
		return $img;
	}
	
	function show()
	{
		header("Content-type: image/jpeg");
		$img=($this->new_img) ? $this->new_img : $this->old_img;
		imagejpeg($img,null,80);
	}
	
	function save($filename,$quality=0)
	{
		imagejpeg($this->new_img,$filename,$quality);
	}

}
?>