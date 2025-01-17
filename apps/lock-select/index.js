import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './query/client';
import { StateProvider } from './state/state';
import MetaboxContent from './components/metabox-content';

document.addEventListener( 'DOMContentLoaded', async () => {
	const wrapp = document.getElementById( 'lock-article-metabox' );
	const postId = wrapp?.dataset?.postid || false;
	const type = wrapp?.dataset?.type || 'not-locked';
	const selected = wrapp?.dataset?.selected || [];

	if ( ! postId ) {
		return;
	}
	const rootEl = createRoot( wrapp );

	rootEl.render(
		<QueryClientProvider client={ queryClient }>
			<StateProvider postId={ postId } type={ type } selected={ selected }>
				<MetaboxContent />
			</StateProvider>
		</QueryClientProvider> );
} );
