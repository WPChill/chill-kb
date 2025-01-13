import { useModulaState } from '../state/use-modula-state';
import AccessSelector from './access-selector';
import ProductSelector from './product-selector';

export default function MetaboxContent() {
	const { state } = useModulaState();
	const { selectedType } = state;

	return (
		<>
			<AccessSelector />
			{ selectedType === 'active_subscription' && <ProductSelector /> }
		</>
	);
}
