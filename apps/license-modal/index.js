import { createRoot } from '@wordpress/element';
import { StateProvider } from './state/state';
import OpenModalButton from './open-modal-button';
import './index.scss';

document.addEventListener( 'DOMContentLoaded', async () => {
	const wrapp = document.getElementById( 'wpchill-kb-license-actions' );
	const licenses = wrapp?.dataset?.licenses || false;

	if ( ! licenses ) {
		return;
	}
	const rootEl = createRoot( wrapp );

	rootEl.render(
		<StateProvider licenses={ licenses }>
			<OpenModalButton />
		</StateProvider> );
} );
