import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import { useModalLicenses } from '../query/useModalLicenses';
import styles from './licenses-modal.module.scss';
import { Markup } from 'interweave';

export default function LicenseTableModal() {
	const { state } = useKnowledgeBaseState();
	const { postId } = state;

	const { data, isLoading, error } = useModalLicenses( postId );

	if ( isLoading ) {
		return (
			<div className={ styles.spinnerWrapper }>
				<Spinner className={ styles.spinner } />
			</div>
		);
	}

	if ( error || ! data || ! data.modal || data.modal.length === 0 ) {
		return (
			<div className={ styles.errorWrapper }>
				<p className={ styles.errorMessage }>
					{ __( 'Could not find any upgrade options.', 'wpchill-kb' ) }
				</p>
			</div>
		);
	}

	const hasLicenseKeys = data.modal.some( ( license ) => license.key );

	return (
		<div>
			<table className={ styles.tableModern }>
				<thead>
					<tr>
						<th>{ __( 'Current Plan', 'wpchill-kb' ) }</th>
						{ hasLicenseKeys && <th>{ __( 'License Key', 'wpchill-kb' ) }</th> }
						<th>{ __( 'Price', 'wpchill-kb' ) }</th>
						<th>{ __( 'Action', 'wpchill-kb' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ data.modal.map( ( license, index ) => (
						<tr key={ index }>
							<td>{ license.title }</td>
							{ hasLicenseKeys && <td className={ styles.key }>{ license.key }</td> }
							<td><Markup content={ license.price } /></td>
							<td className={ styles.action }>
								<Button
									variant={ 'primary' }
									href={ license.url }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ license.type }
								</Button>
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}
