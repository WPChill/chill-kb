(function ($) {
	'use strict';

	$( document ).ready(
		function () {
			$( '#wpchill-kb-search-input' ).autocomplete(
				{
					source: function (request, response) {
						$.ajax(
							{
								url: wpchill_kb_search.ajax_url,
								type: 'POST',
								dataType: 'json',
								data: {
									action: 'wpchill_kb_search',
									security: wpchill_kb_search.security,
									search: request.term,
									kb_hp_check: $( 'input[name="kb_hp_check"]' ).val()
								},
								success: function (data) {
									if (data.success === false) {
										console.error( 'Search error:', data.data.message, 'Code:', data.data.code );
										response( [{ label: data.data.message || 'An error occurred. Please try again.', value: '' }] );
									} else {
										response(
											$.map(
												data.data,
												function (item) {
													return {
														label: item.title,
														value: item.title,
														url: item.url
													};
												}
											)
										);
									}
								},
								error: function (jqXHR, textStatus, errorThrown) {
									console.error( 'AJAX error:', textStatus, errorThrown );
									response( [{ label: 'A server error occurred. Please try again later.', value: '' }] );
								}
							}
						);
					},
					minLength: 2,
					select: function (event, ui) {
						if (ui.item.value) {
							// Track search selection with Umami
							if (typeof umami !== 'undefined') {
								umami.track( 'KB Search', { query: ui.item.value } );
							}
							window.location.href = ui.item.url;
						}
					}
				}
			);

			// Track search when form is submitted
			$( '.wpchill-kb-search-form' ).on(
				'submit',
				function (e) {
					var searchQuery = $( '#wpchill-kb-search-input' ).val();
					if (typeof umami !== 'undefined') {
						umami.track( 'KB Search', { query: searchQuery } );
					}
				}
			);
		}
	);
})( jQuery );