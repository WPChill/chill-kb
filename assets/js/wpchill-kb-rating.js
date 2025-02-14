document.addEventListener(
	'DOMContentLoaded',
	function() {
		const ratingContainer = document.querySelector( '.wpchill-kb-rating' );

		if ( ratingContainer ) {
			const ratingButtons = ratingContainer.querySelectorAll( '.wpchill-kb-rating-button' );
			const postId = ratingContainer.dataset.postId;

			ratingButtons.forEach(
				( button ) => {
					button.addEventListener(
						'click',
						function( e ) {
							e.preventDefault();
							const rating = this.dataset.rating;

							const xhr = new XMLHttpRequest();
							xhr.open( 'POST', wpchillKbRating.ajax_url, true );
							xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );

							xhr.onload = function() {
								if ( xhr.status === 200 ) {
									let response;
									try {
										response = JSON.parse( xhr.responseText );
									} catch ( e ) {
										console.error( 'Error parsing JSON:', e );
										console.log( 'Raw response:', xhr.responseText );
										return;
									}

									if ( response && response.success ) {
										const ratingRight = ratingContainer.querySelector( '.wpchill-kb-rating-right' );

										if ( typeof response.data === 'string' ) {
											ratingRight.innerHTML = response.data;
										} else if ( typeof response.data === 'object' ) {
											ratingRight.innerHTML = `
											<span class = "wpchill-kb-likes" > ${ response.data.likes } Yes </span>
											<span class = "wpchill-kb-dislikes" > ${ response.data.dislikes } No </span>
											`;
										} else {
											console.error( 'Unexpected data format:', response.data );
											return;
										}

										// Hide the buttons after voting
										const buttonsContainer = ratingContainer.querySelector( '.wpchill-kb-rating-buttons' );
										if ( buttonsContainer ) {
											buttonsContainer.style.display = 'none';
										}

										// Update the question to a "Thank you" message
										const questionElement = ratingContainer.querySelector( '.wpchill-kb-rating-question' );
										if ( questionElement ) {
											questionElement.textContent = 'Thank you for your feedback!';
										}
									} else {
										console.error( 'Error in response:', response );
									}
								} else {
									console.error( 'Error: ' + xhr.status );
								}
							};

							xhr.onerror = function() {
								console.error( 'Request failed. Please try again later.' );
							};

							const data = 'action=wpchill_kb_rate_article' +
							'&security=' + encodeURIComponent( wpchillKbRating.nonce ) +
							'&post_id=' + encodeURIComponent( postId ) +
							'&rating=' + encodeURIComponent( rating );

							xhr.send( data );
						},
					);
				},
			);
		}
	},
);
