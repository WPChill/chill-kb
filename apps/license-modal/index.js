import { createRoot } from '@wordpress/element';
import { StateProvider } from './state/state';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import OpenModalButton from './open-modal-button';
import './index.scss';

document.addEventListener( 'DOMContentLoaded', async () => {
	const wrapp = document.getElementById( 'wpchill-kb-license-actions' );
	const postId = wrapp?.dataset?.postid || 0;

	if ( 0 === postId ) {
		return;
	}
	const rootEl = createRoot( wrapp );

	rootEl.render(
		<QueryClientProvider client={ queryClient }>
			<StateProvider postId={ postId }>
				<OpenModalButton />
			</StateProvider>
		</QueryClientProvider> );
} );
