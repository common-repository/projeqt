<?php
/**
	Plugin Name: Projeqt
	Plugin URI: http://wordpress.org/extend/plugins/projeqt/
	Description: Projeqt Wordpress Plugin that adds oEmbed and Shortcode support
	Author: Projeqt
	Version: 1.3
	Author URI: http://www.projeqt.com
	License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define("PROJEQT_EMBED_URL", "http://www.projeqt.com/embed/");
define("PROJEQT_SMALL_EMBED_WIDTH",  425);
define("PROJEQT_SMALL_EMBED_HEIGHT", 325);
define("PROJEQT_LARGE_EMBED_WIDTH",  770);
define("PROJEQT_LARGE_EMBED_HEIGHT", 620);

/**
 *	Projeqt oEmbed support converting Projeqt urls to small embeds
 *	http://projeqt.com/{username}/{projeqt}
 */
function wp_embed_handler_projeqt($matches, $attr, $url, $rawattr)
{
	$username = esc_attr($matches[2]); // get username
	$deeplink = projeqt_parse_deeplink(esc_attr($matches[4]));
	$projeqt = $deeplink['projeqt']; // get projeqt name
	$slide = $deeplink['slide']; // get slide
	$iframe = projeqt_iframe($username, $projeqt, $slide); // get small embed code

	return apply_filters('embed_projeqt', $iframe, $matches, $attr, $url, $rawattr);
}

wp_embed_register_handler( 'projeqt', '#http://(www\.)?projeqt\.com/([a-zA-Z0-9-_]{3,50})(/([a-zA-Z0-9-_/]{3,50}))*#i', 'wp_embed_handler_projeqt' );


/**
 *	Projeqt shortcode
 *	[projeqt username="about"]
 *	[projeqt username="about" size="large"]
 *  [projeqt username="about" projeqt="about-projeqt"]
 */
function projeqt_shortcode($atts)
{
	$small_size = array("width" => PROJEQT_SMALL_EMBED_WIDTH, "height" => PROJEQT_SMALL_EMBED_HEIGHT); // SMALL EMBED
	$large_size = array("width" => PROJEQT_LARGE_EMBED_WIDTH, "height" => PROJEQT_LARGE_EMBED_HEIGHT); // LARGE EMBED

	$default_size = 'small';
	$default_username = 'about';
	$default_projeqt = '';
	$default_slide = '';

	$default_width = ${$default_size . '_size'}['width'];
	$default_height = ${$default_size . '_size'}['height'];

	// extract attributes in shortcode
	extract(shortcode_atts(array(
		'size'	=> $default_size,
		'width' => $default_width,
		'height' => $default_height,
		'username' => $default_username,
		'projeqt' => $default_projeqt,
		'slide' => $default_slide
	), $atts ) );


	// require username
	if(!empty($username))
	{
		// Set the width and height for the large emebed
		if($size == 'large')
		{
			$width = $large_size['width'];
			$height = $large_size['height'];
		}

		// If the width or height are empty for some reason then we will default to the small embed
		if(empty($width) || empty($height))
		{
			$width = $small_size['width'];
			$height = $small_size['height'];
		}

		$deeplink = projeqt_parse_deeplink($projeqt);
		$projeqt = $deeplink['projeqt'];
		$slide = empty($slide) ? $deeplink['slide'] : $slide;

		return projeqt_iframe($username, $projeqt, $slide, $width, $height);
	}

	return false;
}
add_shortcode('projeqt', 'projeqt_shortcode');

/**
 * Projeqt iframe embed code
 */
function projeqt_iframe($username, $projeqt, $slide='', $width=PROJEQT_SMALL_EMBED_WIDTH, $height=PROJEQT_SMALL_EMBED_HEIGHT)
{
	$iframe_src = PROJEQT_EMBED_URL . $username . '/' . $projeqt . '/' . $slide;

	if(!empty($projeqt))
	{
		$lookup = projeqt_embed_lookup($username, $projeqt);

		// do embed v2
		if($lookup->error === FALSE && !empty($lookup->unique_id))
		{
			$iframe_src = PROJEQT_EMBED_URL . 'v2/' . $lookup->unique_id . '/' . $slide;
		}

		// error, do not embed
		if($lookup->error === TRUE)
		{
			return '';
		}
	}

	return '<iframe src="' . $iframe_src . '" width="'. $width . '" height="' . $height . '" frameborder="0" style="border:1px solid #d3d3d3"></iframe>';
}

/**
 * {projeqt}/{stack|slide}
 */
function projeqt_parse_deeplink($deeplink)
{
	$url_parts = explode('/', $deeplink);
	$projeqt = array_shift($url_parts);
	$slide = count($url_parts) > 0 ? implode('/', $url_parts) : '';

	return array('projeqt' => $projeqt, 'slide' => $slide);
}

/**
 * Projeqt embed key lookup
 */
function projeqt_embed_lookup($username, $projeqt)
{
	$request = new WP_Http;
	$result = $request->request('http://www.projeqt.com/api/projeqt/get_key/' . $username . '/' . $projeqt . '/');
	return json_decode($result['body']);
}
?>