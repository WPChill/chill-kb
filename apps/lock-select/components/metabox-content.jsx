import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import AccessSelector from './access-selector';
import ProductSelector from './product-selector';

export default function MetaboxContent() {
	const { state } = useKnowledgeBaseState();
	const { selectedType } = state;

	return (
		<>
			<AccessSelector />
			{ selectedType === 'active_subscription' && <ProductSelector /> }
		</>
	);
}
