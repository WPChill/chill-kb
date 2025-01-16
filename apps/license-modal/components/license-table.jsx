import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useModulaState } from '../state/use-modula-state';
import styles from './licenses-modal.module.scss';

export default function LicenseTableModal() {
	const { state } = useModulaState();
	const { licensesData } = state;

	const hasLicenseKeys = licensesData.some( ( license ) => license.key );

	return (
		<div>
			<table className={ styles.tableModern }>
				<thead>
					<tr>
						<th>{ __( 'Product Name', 'wpchill-kb' ) }</th>
						{ hasLicenseKeys && <th>{ __( 'License Key', 'wpchill-kb' ) }</th> }
						<th>{ __( 'Action', 'wpchill-kb' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ licensesData.map( ( license, index ) => (
						<tr key={ index }>
							<td>{ license.title }</td>
							{ hasLicenseKeys && <td className={ styles.key }>{ license.key }</td> }
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
