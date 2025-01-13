import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useModulaState } from '../state/use-modula-state';
import { setSelectedType } from '../state/actions';

export default function AccessSelector() {
	const { state, dispatch } = useModulaState();
	const { selectedType } = state;

	const options = [
		{ label: __( 'Not Locked', 'text-domain' ), value: 'not_locked' },
		{ label: __( 'Members Only', 'text-domain' ), value: 'members_only' },
		{ label: __( 'Active Subscription', 'text-domain' ), value: 'active_subscription' },
	];

	const handleChange = ( selectedValue ) => {
		dispatch( setSelectedType( selectedValue ) );
	};

	return (
		<div>
			<SelectControl
				label={ __( 'Access Level', 'text-domain' ) }
				name="wpchill_kb_access_type"
				value={ selectedType || options[ 0 ].value }
				options={ options }
				onChange={ handleChange }
			/>
		</div>
	);
}
