/**
 * @package     hubzero-cms
 * @file        core/plugins/projects/assets/js/connections.js
 * @copyright   Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license     http://opensource.org/licenses/MIT MIT
 */

if (!jq) {
	var jq = $;
}

jQuery(document).ready(function (jq) {
	var $ = jq;

	$('.layout-control').click(function (e) {
		e.preventDefault();

		var target     = $(e.target),
			connection = $('.connection'),
			classname  = target.data('class');

		connection.removeClass('large-icon small-icon list').addClass(classname);

		$('.layout-control').removeClass('active');
		$(this).addClass('active');
	});

	$('.connection-type').change(function (e) {
		var form = $(this).parents('form');

		form.submit();
	});
});