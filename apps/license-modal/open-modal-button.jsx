import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import LicenseTableModal from './components/license-table-modal';
import { toggleModal } from './state/actions';
import { useModulaState } from './state/use-modula-state';
import { useCallback } from '@wordpress/element';

export default function InstagramConnector() {
	const { state, dispatch } = useModulaState();

	const handleClick = useCallback( ( evt ) => {
		evt.preventDefault();
		dispatch( toggleModal( true ) );
	}, [ dispatch ] );

	return (
		<>
			<Button
				variant={ 'primary' }
				onClick={ handleClick }
			>
				{ __( 'See Options', 'wpchill-kb' ) }
			</Button>
			{ state.isModalOpen && <LicenseTableModal /> }
		</>
	);
}
