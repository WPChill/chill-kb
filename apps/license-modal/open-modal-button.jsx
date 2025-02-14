import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import LicenseTableModal from './components/license-table-modal';
import { toggleModal } from './state/actions';
import { useKnowledgeBaseState } from './state/use-knowledge-base-state';
import { useCallback } from '@wordpress/element';

export default function OpenModalButton() {
	const { state, dispatch } = useKnowledgeBaseState();

	const handleClick = useCallback( ( evt ) => {
		evt.preventDefault();
		dispatch( toggleModal( true ) );
	}, [ dispatch ] );

	return (
		<>
			<Button
				className="kbLicensesOptionsButton"
				onClick={ handleClick }
			>
				{ __( 'Upgrade Your Plan', 'wpchill-kb' ) }
			</Button>
			{ state.isModalOpen && <LicenseTableModal /> }
		</>
	);
}
