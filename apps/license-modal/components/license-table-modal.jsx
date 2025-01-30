import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import { toggleModal } from '../state/actions';
import LicenseTable from './license-table';

export default function LicenseTableModal() {
	const { dispatch } = useKnowledgeBaseState();

	const closeModal = () => {
		dispatch( toggleModal( false ) );
	};

	return <Modal
		title={ __( 'Choose the license you want to upgrade', 'wpchill-kb' ) }
		onRequestClose={ closeModal }
		isFullScreen={ true }
		isBusy={ true }
		className="licenseTableModal"
		closeButtonLabel={ __( 'Close', 'wpchill-kb' ) }>
		<LicenseTable />
	</Modal>;
}
