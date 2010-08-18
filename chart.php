<?php
/**
 * Chart class
 *
 * @author Han Lin Yap < http://zencodez.net/ >
 * @copyright 2010 zencodez.net
 * @license http://creativecommons.org/licenses/by-sa/3.0/
 * @package pGChart
 * @version 1.0 - 2010-08-14
 */
class zc_gchart {
	/**
	 * All values
	 * @var array
	 */
	public $data;
	/**
	 * Chart title
	 * @var string
	 */
	public $title = false;
	
	function __construct($data) {
		// Makes to a multidimention array
		if (is_array($data) && !is_array($data[0])) {
			for ($i = 0; $i < count($data); $i++) {
				$data[$i] = array($i, $data[$i]);
			}
		}
		$this->data = $data;
	}

	/**
	 * Factory
	 * @param string $classname optional chart class to initialize
	 * @param mixed $args optional arguments of the class constructor
	 * @return instance of chart class
	 * @since 1.0
	 */
	public static function factory() {
		$args = func_get_args();
		$class = array_shift($args);
		$reflection = new ReflectionClass(__CLASS__ . '_' . $class);
		return $reflection->newInstanceArgs($args);
	}
	
	/**
	 * Chart title
	 * @param string $color optional hex color
	 * @param integer $font_size optional Title size
	 * @return url parameter for title
	 * @since 1.0
	 */
	public function title($color=false, $font_size=12) {
		if ($this->title !== false) {
			$s = '&chtt=' . $this->title;
			if ($color) {
				$s .= '&chts=' . $color . ',' . $font_size;
			}
			return $s;
		}
		return false;
	}
}
/**
 * Bar class
 *
 * Example usage
 * <code>
 * $data = array(1,2,3);
 * $bar = zc_gchart::factory('bar', $data);
 * echo $bar->render(2, true, 'title', 'X-axis label');
 * </code>
 
 * @author Han Lin Yap < http://zencodez.net/ >
 * @copyright 2010 zencodez.net
 * @license http://creativecommons.org/licenses/by-sa/3.0/
 * @package pGChart
 * @version 1.0 - 2010-08-14
 */
class zc_gchart_bar extends zc_gchart {
	
	/**
	 * Render bar chart
	 * @param integer|boolean $my_value optional Highlight column from the users value in the bar chart
	 * @param integer|boolean $round optional Round labels numbers, set true to auto detect best possible round value
	 * @param string $title optional Title of bar chart
	 * @param string $label_x optional Label in X-axis
	 * @param integer $splits optional Number of columns
	 * @param string $extra optional custom url parameter
	 * @return string HTML image code with Google chart url
	 */
	public function render($my_value=false, $round=100, $title = false, $label_x="", $splits=10, $extra=false) {
		$this->title = $title;
		
		$x = array();
		$y = array();
		foreach($this->data AS $v) {
			$x[] = $v[0];
			$y[] = $v[1];
		}
		
		// Get highest and lowest value
		$highest = max($y);
		$lowest = min($y);
		
		// auto round
		if ($round === true) {
			$ten = 1;
			do {
				if (pow(10,$ten) > ($highest - $lowest) / 10) {
					$round = pow(10,$ten-1);
					break;
				}
			} while($ten++);
		}
		
		// Label X
		$number_x = array();
		for($i = 0; $i < $splits; $i++) {
			$number_x[] = round(((($highest - $lowest) / $splits) * $i + $lowest + ($highest - $lowest) / ($splits*2)) / $round) * $round;
		}
		
		// Fill bar data
		$bar_data = array_fill(0,$splits,0);
		foreach($y AS $v) {
			$position = $this->_find_split_position($v, $number_x);
			
			if ($position!==false)
				$bar_data[$position]++;
		}
		
		// Set my color
		$color = array_fill(0,$splits,'C6D9FD');
		if ($my_value!==false)
			$my_position = $this->_find_split_position($my_value, $number_x);
			if ($my_position!==false)
				$color[$my_position] = '4D89F9';
		
		// Get max Y-value
		$y_max = max($bar_data);
				
		// Label Y
		if ($y_max > 120) {
			$step = 40;
		} elseif ($y_max > 30) {
			$step = 10;
		} elseif ($y_max > 15) {
			$step = 5;
		} elseif ($y_max > 6) {
			$step = 2;
		} else {
			$step = 1;
		}
		
		return '<img src="http://chart.apis.google.com/chart
			?cht=bvs
			&chxt=x,x,y,y
			&chxl=0:|' . implode('|', $number_x) . '|1:|' . $label_x . '|3:|No
			&chxp=1,50|3,50
			&chbh=a
			&chs=500x230
			' . $this->title('FF0000') . '
			&chd=t:' . implode(',', $bar_data) . '
			&chco=' . implode('|', $color) . '
			&chxr=2,0,' . ($y_max + 2) . ',' . $step . '
			&chds=0,' . ($y_max + 2) . '
			' . $extra . '
			" />';
		
	}
	
	/**
	 * Find what position the value belongs to in the splitted sections.
	 * @param integer $v Value to search
	 * @param array $splitted Sections
	 * @return integer Position in section
	 */
	private function _find_split_position($v, $splits) {
		for ($i = 0 ; $i < count($splits) ; $i++) {
			if ($i == 0) {
				if ($v < ($splits[$i+1] + $splits[$i]) / 2) {
					return $i;
				}
			} elseif ($i == count($splits)-1) {
				if ($v > ($splits[$i] + $splits[$i-1]) / 2) {
					return $i;
				}
			} else {
				if ($v >= ($splits[$i] + $splits[$i-1]) / 2 && $v <= ($splits[$i+1] + $splits[$i]) / 2) {
					return $i;
				}
			}
		}
		return false;
	}
}

/**
 * Formula class
 *
 * Example usage 1
 * <code>
 * $data = array(array($x1, $y1), array($x2, $y2));
 * $formula = zc_gchart::factory('formula', $data);
 * echo $formula->render('x');
 * </code>
 * 
 * Example usage 2 - with zc_math_linear class
 * <code>
 * $data = array(array($x1, $y1), array($x2, $y2));
 * $linear = new zc_math::factory('linear', $data);
 * $linear->calculate();
 * $formula = zc_gchart::factory('formula', $data);
 * // y = k*x + m
 * echo $formula->render($linear->k . '*x%2b' . $linear->m); 
 * </code>
 *
 * Example usage 3 - with zc_math_polynomial class
 * <code>
 * $data = array(array($x1, $y1), array($x2, $y2));
 * $polynomial = new zc_math::factory('polynomial', $data);
 * $polynomial->calculate_coefficients();
 * $formula = zc_gchart::factory('formula', $data);
 * echo $formula->render($polynomial->render_formula('text')); 
 * </code>
 *
 * @author Han Lin Yap < http://zencodez.net/ >
 * @copyright 2010 zencodez.net
 * @license http://creativecommons.org/licenses/by-sa/3.0/
 * @package pGChart
 * @version 1.0 - 2010-08-14
 */
class zc_gchart_formula extends zc_gchart {
	/**
	 * Render Google chart by a formula with data and ranges
	 * @param string $formula The formula in url form.
	 * @param array $range_x optional contain min and max of X-axis
	 * @param array $range_y optional contain min and max of Y-axis
	 * @param string $extra optional custom url parameter
	 * @return string image tag with Google chart url.
	 */
	public function render($formula, $range_x=false, $range_y=false, $extra=false) {
		$x = array();
		$y = array();
		foreach($this->data AS $v) {
			$x[] = $v[0];
			$y[] = $v[1];
		}
		
		if ($range_x === false)
			$range_x = array(min(0,min($x)), max($x));
		
		if ($range_y === false)
			$range_y = array(min(0,min($y)), max($y));
	
	
		return '<img src="http://chart.apis.google.com/chart?cht=lxy&chs=500x230&chxt=x,y
		' . $this->title() . '
		&chco=333333,DDDDDD,DDDDDD,AAAAAA
		&chd=t:' . implode(',', $x) . '|' . implode(',', $y) . '|-1|-1|-1|-1|-1|-1
		&chxr=0,' . $range_x[0] . ',' . $range_x[1] . ',' . (($range_x[1] - $range_x[0]) / 10) . '|1,' . $range_y[0] . ',' . $range_y[1] . ',' . (($range_y[1] - $range_y[0]) / 10) . '|2,' . $range_x[0] . ',' . $range_x[1] . ',' . (($range_x[1] - $range_x[0]) / 10) . '|3,' . $range_y[0] . ',' . $range_y[1] . ',' . (($range_y[1] - $range_y[0]) / 10) . '
		&chds=' . $range_x[0] . ',' . $range_x[1] . ',' . $range_y[0] . ',' . $range_y[1] . ',' . $range_x[0] . ',' . $range_x[1] . ',' . $range_y[0] . ',' . $range_y[1] . '
		&chfd=3,x,' . $range_y[0] . ',' . $range_y[1] . ',' . ($range_y[1] - $range_y[0]) . ',x|2,x,0,1,1,0|5,x,0,1,1,0|7,x,' . $range_x[0] . ',' . $range_x[1] . ',1,' . $formula . '
		
		' . $extra . '
		" />';
	}
}
?>