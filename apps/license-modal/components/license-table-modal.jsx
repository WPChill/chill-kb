import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useModulaState } from '../state/use-modula-state';
import { toggleModal } from '../state/actions';
import LicenseTable from './license-table';

export default function LicenseTableModal() {
	const { dispatch } = useModulaState();

	const closeModal = () => {
		dispatch( toggleModal( false ) );
	};

	return <Modal
		title={ __( 'Select license action', 'modula-instagram' ) }
		onRequestClose={ closeModal }
		isFullScreen={ true }
		isBusy={ true }
		className="licenseTableModal"
		closeButtonLabel={ __( 'Close', 'modula-instagram' ) }>
		<LicenseTable />
	</Modal>;
}
