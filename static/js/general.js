jQuery(document).ready(function ($) {

	var $items = {
		'single_select': $('.fw-tt'),
		'multiple_select': $('#fw-option-translate-to')
	};
	var renderItems = function (item, escape) {
		return '<div data-value="' + item.value + '" class="item">' +
			'<img src="' + item.src + '">&nbsp;&nbsp;'
			+ escape(item.text) + '</div>';
	}

	$items.single_select.selectize({
		onChange: function (value) {
			multipleSelectInstance[0].selectize.addOption(_.values(this.options));
			multipleSelectInstance[0].selectize.removeOption(value);
		},
		render: {
			option: renderItems,
			item: renderItems
		}
	});

	var multipleSelectInstance = $items.multiple_select.selectize({
		render: {
			option: renderItems,
			item: renderItems
		}
	});

});