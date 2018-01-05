<?php
class FormBuilder {
	public $settings = array(
		'name' => 'FormBuilder',
		'description' => 'This allows other modules to generate forms programmatically.',
	);
	/*
	    * Useful.. but not implemented anywhere
	   function check_input_error($code) {
	       global $billic;
	       if (array_key_exists($code, $billic->errors)) {
	           return false;
	       }
	       return true;
	   }
	*/
	function check_everything($array) {
		global $billic, $db;
		foreach ($array['form'] as $key => $opts) {
			if ($key == 'captcha') {
				if (empty($_POST['captcha']) || $_SESSION['captcha'] != $_POST['captcha']) {
					unset($_SESSION['captcha']);
					$billic->error('Captcha code invalid, please try again', 'captcha');
				}
			} else if ($opts['requirement'] == 'required' && empty($_POST[$key])) {
				$billic->error($opts['label'] . ' is required', $key);
			} else if ($opts['requirement'] == 'alphanumeric' && !ctype_alnum($_POST[$key])) {
				$billic->error($opts['label'] . ' must be alphanumeric', $key);
			} else if ($opts['requirement'] == 'email' && !valid_email($_POST[$key])) {
				$billic->error('Email is invalid', $key);
			} else if ($opts['type'] != 'textarea' && strlen($_POST[$key]) > 255) {
				$billic->error($key . ' > 255', $key);
			} else if ($opts['type'] == 'dropdown_country') {
				if (!array_key_exists($_POST[$key], $GLOBALS['countries'])) {
					$billic->error('Country is invalid', $key);
				}
			} else if ($opts['type'] == 'slider' && ($_POST[$key] % $opts['step'] != 0)) {
				$billic->error('Invalid Step', $key);
			} else if ($opts['type'] == 'slider' && $_POST[$key] < $opts['min']) {
				$billic->error('Minimum of ' . $opts['min'], $key);
			} else if ($opts['type'] == 'slider' && $_POST[$key] > $opts['max']) {
				$billic->error('Maximum of ' . $opts['max'], $key);
			}
		}
	}
	function output($array) {
		global $billic, $db;
		if (array_key_exists('button', $array) || array_key_exists('id', $array)) {
			echo '<form method="POST"';
			if (array_key_exists('id', $array)) {
				echo ' id="' . safe($array['id']) . '"';
			}
			echo '><table class="table table-striped">';
		}
		if (array_key_exists('title', $array)) {
			echo '<tr><th colspan="3">' . $array['title'] . '</th></tr>';
		}
		foreach ($array['form'] as $key => $opts) {
			if ($key == 'captcha') {
				echo '<tr><td' . $billic->highlight('captcha') . ' colspan="2"><div style="float:left;padding-right:20px"><img src="/Captcha/' . time() . '" width="150" height="75" alt="CAPTCHA"></div><br>Enter the number you see<br><input type="text" class="form-control" name="captcha" size="6" style="text-align:center;width:150px" value="' . (empty($billic->errors['captcha']) ? safe($_POST['captcha']) : '') . '"></td></tr>';
				continue;
			}
			if ($opts['type'] == 'hidden') {
				echo '<input type="hidden" name="' . safe($key) . '" value="' . safe($opts['value']) . '">';
				continue;
			}
			echo '<tr style="' . ($opts['requirement'] == 'required' ? 'font-weight:bold' : 'opacity:0.8') . '" title="' . safe($opts['label']) . '"><td' . $billic->highlight($key) . $billic->highlight($opts['label']) . ' style="width:1%;max-width:400px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">' . (empty($opts['img']) ? '' : '<img src="/i/orderformitems/' . $opts['img'] . '" style="vertical-align:middle">&nbsp;') . $opts['label'] . '</td><td>';
			if ($opts['type'] == 'text' || $opts['type'] == 'email') {
				echo '<input type="text" class="form-control" name="' . $key . '" value="' . safe($_POST[$key]) . '"' . (empty($opts['placeholder']) ? '' : ' placeholder="' . safe($opts['placeholder'])) . '">';
			} else if ($opts['type'] == 'password') {
				echo '<input type="password" class="form-control" name="' . $key . '" value="' . safe($_POST[$key]) . '"' . (empty($opts['placeholder']) ? '' : ' placeholder="' . safe($opts['placeholder'])) . '">';
			} else if ($opts['type'] == 'textarea') {
				echo '<textarea name="' . $key . '" class="form-control">' . safe($_POST[$key]) . '</textarea>';
			} else if ($opts['type'] == 'slider') {
				if (!defined('formbuilder_slider_js')) {
					$billic->add_script('/Modules/Core/bootstrap/bootstrap-slider.min.js');
					echo '<link rel="stylesheet" href="/Modules/Core/bootstrap/bootstrap-slider.min.css">';
					define('included: formbuilder_slider_js', true);
				}
				if (empty($_POST[$key]) || !is_numeric($_POST[$key])) {
					$_POST[$key] = $opts['min'];
				}
				echo '<input type="text" name="' . $key . '" id="slider' . $key . '" data-slider-min="' . $opts['min'] . '" data-slider-max="' . $opts['max'] . '" data-slider-step="' . $opts['step'] . '" data-slider-value="' . $_POST[$key] . '"><span id="slider' . $key . 'html" style="margin-left:20px">' . $_POST[$key] . '</span>';
				echo '<script>addLoadEvent(function() { var slider = new Slider("#slider' . $key . '", {tooltip: "hide"}); slider.on("slide", function(slideEvt) { $("#slider' . $key . 'html").html(slideEvt); $("#slider' . $key . '").val(slideEvt); });});</script>';
			} else if ($opts['type'] == 'checkbox') {
				echo '<input type="checkbox" name="' . $key . '" value="1"' . ($_POST[$key] == 1 ? ' checked' : '') . '> ' . $opts['description'];
			} else if ($opts['type'] == 'dropdown_country') {
				echo '<select class="form-control" name="' . $key . '">';
				if (empty($_POST[$key])) {
					$_POST[$key] = $opts['default'];
					if (empty($_POST[$key])) {
						$_POST[$key] = 'US';
					}
				}
				foreach ($GLOBALS['countries'] as $countrycode => $country) {
					echo '<option value="' . $countrycode . '"' . ($countrycode == $_POST[$key] ? ' selected="1"' : '') . '>' . $country . '</option>';
				}
				echo '</select>';
			} else if ($opts['type'] == 'dropdown') {
				echo '<select class="form-control" name="' . $key . '">';
				if (empty($_POST[$key])) {
					$_POST[$key] = $opts['default'];
					if (empty($_POST[$key])) {
						$_POST[$key] = 'US';
					}
				}
				foreach ($opts['options'] as $k => $v) {
					echo '<option value="' . $k . '"' . ($k == $_POST[$key] ? ' selected="1"' : '') . '>' . $v . '</option>';
				}
				echo '</select>';
			}
			echo '</td></tr>';
		}
		if (array_key_exists('button', $array)) {
			echo '<tr><td colspan="3" align="center"><input type="submit" class="btn btn-success" name="' . $array['button'] . '" value="' . $array['button'] . ' &raquo;"></td></tr>';
		}
		if (array_key_exists('button', $array) || array_key_exists('id', $array)) {
			echo '</table></form>';
		}
	}
}
