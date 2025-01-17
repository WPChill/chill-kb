import { SelectControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useModulaState } from '../state/use-modula-state';
import { setSelectedType } from '../state/actions';
import { useAccessProducts } from '../query/useAccessProducts';
import styles from './lock-select.module.scss';

export default function AccessSelector() {
	const { state, dispatch } = useModulaState();
	const { selectedType, postId } = state;

	const options = [
		{ label: __( 'Not Locked', 'wpchill-kb' ), value: 'not_locked' },
		{ label: __( 'Members Only', 'wpchill-kb' ), value: 'members_only' },
	];

	const handleChange = ( selectedValue ) => {
		dispatch( setSelectedType( selectedValue ) );
	};

	const { data, isLoading, error } = useAccessProducts( postId );

	if ( isLoading ) {
		return (
			<div className={ styles.spinnerWrapper }>
				<Spinner className={ styles.spinner } />
			</div>
		);
	}

	if ( ! error && data ) {
		options.push( {
			label: __( 'Active Subscription', 'wpchill-kb' ),
			value: 'active_subscription',
		} );
	}

	return (
		<div>
			<SelectControl
				label={ __( 'Access Level', 'wpchill-kb' ) }
				name="wpchill_kb_access_type"
				value={ selectedType || options[ 0 ].value }
				options={ options }
				onChange={ handleChange }
			/>
		</div>
	);
}
